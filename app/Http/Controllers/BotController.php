<?php

namespace App\Http\Controllers;

use App\Models\Conversations;
use App\Models\Drowning;
use App\Models\Messages;
use App\TextHelper;
use Carbon\Carbon;
use Log;
use geoPHP;
use Illuminate\Http\Request;

class BotController extends Controller
{
    /**
     * The verification token for Facebook
     *
     * @var string
     */
    protected $token;

    protected $current_message;

    public function __construct()
    {
        $this->token = env('BOT_VERIFY_TOKEN');
    }

    /**
     * Verify the token from Messenger. This helps verify your bot.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function verify_token(Request $request)
    {
        $mode  = $_GET['hub_mode'];
        $token = $_GET['hub_verify_token'];

        Log::debug("$mode: " . json_encode($mode));
        Log::debug("$token: " . json_encode($token));

        if ($mode === "subscribe" && $this->token and $token === $this->token) {
            return response($_GET['hub_challenge']);
        }

        return response("Invalid token!", 400);
    }

    /**
     * Handle the query sent to the bot.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function handle_query(Request $request)
    {
        Log::debug($request->getContent());
        $request = json_decode($request->getContent());

        $entry = $request->entry;
        $sender  = $entry[0]->messaging[0]->sender->id;
        $message = $entry[0]->messaging[0]->message;
        $message_text = "";

        $this->dispatchTyping($sender);


        //log the messages
        $message_log = new Messages([
            'user_id' => $sender
        ]);

        $message_log->save();

        $this->current_message = $message_log;

        /**
         * First things first, we need the users location!
         */
        $conversation = Conversations::where('user_id', $sender)->first();
        if(empty($conversation)){
            //create a new conversation
            $conversation = new Conversations([
                'user_id' => $sender,
                'last_active' => Carbon::now()
            ]);

            //send welcome messages
            $this->dispatchResponse($sender, "Ahoy Matey, I am Captain Jack!");
            $this->dispatchResponse($sender, "My areas of expertise include hunting, fishing and swimming.");

            $conversation->save();
        }

        /**
         * Lets first parse the incoming message data (e.g. if there is location data sent)
         */
        if(isset($message->text)){
            $message_text = $message->text;
            $message_log->received = $message_text;
            $message_log->save();

            Log::debug("Entry: " . json_encode($entry));
            Log::debug("Sender id: " . $sender);
            Log::debug("Incoming message: " . $message_text);
        } else if(isset($message->attachments)){
            if($message->attachments[0]->type == "location"){
                //update the users location
                $location = $message->attachments[0]->payload->coordinates;

                $conversation->lat = $location->lat;
                $conversation->lon = $location->long;
                $conversation->last_active = Carbon::now();
                $conversation->save();

                $this->dispatchResponse($sender, "Arr, you're the best! Now what do you want?");
                $this->dispatchResponse($sender, "Ask me something like 'Can I hunt here?'");
            } else {
                $this->dispatchResponse($sender, "Arr, that is pretty cool!");
            }
            return response('', 200);
        } else {
            //unknown response
            $this->dispatchResponse($sender, "Arr! We don't know what you just sent us!");
        }
        /**
         * Finished parsing input data
         */


        /**
         * Now lets look at what to reply
         */
        if (empty($conversation->lat) || empty($conversation->lon) || Carbon::parse($conversation->last_active)->addDays(1) < Carbon::now()) {
            //looks like we need the users location
            $this->dispatchLocationRequest($sender);
        } else {
            //otherwise, try reply with some useful information
            $conversation->last_active = Carbon::now();
            $conversation->save();

            //get the appropriate reply
            $reply = TextHelper::readMessage($message_text, $conversation->lat, $conversation->lon);

            //send the reply
            $this->dispatchResponse($sender, $reply);
        }
        /**
         * Reply should have been sent by now
         */

        return response('', 200);
    }


    /**
     * Post a message to the Facebook messenger API.
     *
     * @param  integer $id
     * @param  string  $response
     * @return bool
     */
    protected function dispatchTyping($id)
    {
        $access_token = env('BOT_PAGE_ACCESS_TOKEN');
        $url = "https://graph.facebook.com/v2.10/me/messages?access_token={$access_token}";

        $data = json_encode([
            'recipient' => ['id' => $id],
            "sender_action" => "typing_on"
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    protected function dispatchLocationRequest($id)
    {
        $this->current_message->replied = "Please share your location";
        $this->current_message->save();

        $access_token = env('BOT_PAGE_ACCESS_TOKEN');
        $url = "https://graph.facebook.com/v2.10/me/messages?access_token={$access_token}";

        $data = json_encode([
            'recipient' => ['id' => $id],
            "message" => [
                "text" => "Arrrh! It looks like we don't have yer location! Please send us yer location",
                "quick_replies" => [
                    [
                        "content_type" => "location",
                    ]
                ]
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Post a message to the Facebook messenger API.
     *
     * @param  integer $id
     * @param  string  $response
     * @return bool
     */
    protected function dispatchResponse($id, $response)
    {
        $this->current_message->replied = $response;
        $this->current_message->save();

        $access_token = env('BOT_PAGE_ACCESS_TOKEN');
        $url = "https://graph.facebook.com/v2.10/me/messages?access_token={$access_token}";

        $data = json_encode([
            'recipient' => ['id' => $id],
            'message'   => ['text' => $response]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
