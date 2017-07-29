<?php

namespace App\Http\Controllers;

use App\Models\Conversations;
use App\Models\Messages;
use App\TextHelper;
use Carbon\Carbon;
use Log;
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
        $message = $entry[0]->messaging[0]->message->text;

        Log::debug("Entry: " . json_encode($entry));
        Log::debug("Sender id: " . $sender);
        Log::debug("Incoming message: " . $message);

        $this->dispatchTyping($sender);

        //Do all the processing in here

        //log the messages
        $message_log = new Messages([
            'user_id' => $sender,
            'received' => $message
        ]);

        $message_log->save();

        $this->current_message = $message_log;

        //TODO check for location info response


        //check for existing conversation with the user_id
        $conversation = Conversations::where('user_id', $sender)->first();
        if(empty($conversation)){
            //create a new conversation
            $conversation = new Conversations([
                'user_id' => $sender,
                'last_active' => Carbon::now()
            ]);

            $conversation->save();

            //prompt the user with the first interactions
            $this->dispatchLocationRequest($sender);
        } else if (empty($conversation->lat) || empty($conversation->lon) || $conversation->last_active->addDays(1) < Carbon::now()) {
            //prompt the user with the first interactions
            $this->dispatchLocationRequest($sender);
        } else {
            $conversation->last_active = Carbon::now();
            $conversation->save();

            //get the appropriate reply
            $reply = TextHelper::readMessage($message);

            //send the reply
            $this->dispatchResponse($sender, $reply);
        }

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
