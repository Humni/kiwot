<?php

namespace App\Http\Controllers;

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

    public function __construct()
    {
        $this->token = env('BOT_VERIFY_TOKEN');
        Log::debug("Token: " . $this->token);
    }

    /**
     * Verify the token from Messenger. This helps verify your bot.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function verify_token(Request $request)
    {
        $mode  = $request->input('hub.mode');
        $token = $request->input('hub.verify_token');

        Log::debug("External Token: " . $token);
        Log::debug("External Mode: " . $mode);
        Log::debug("External All: " . $request->all(););
        if ($mode === "subscribe" && $this->token and $token === $this->token) {
            return response($request->input('hub.challenge'));
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
        $entry = $request->get('entry');

        $sender  = array_get($entry, '0.messaging.0.sender.id');
        // $message = array_get($entry, '0.messaging.0.message.text');

        $this->dispatchResponse($sender, 'Hello world. You can customise my response.');

        return response('', 200);
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
        $access_token = env('BOT_PAGE_ACCESS_TOKEN');
        $url = "https://graph.facebook.com/v2.6/me/messages?access_token={$access_token}";

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
