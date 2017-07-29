<?php

namespace App\Http\Controllers;

use Log;
use geoPHP;
use Illuminate\Http\Request;

class DevController extends Controller
{
    /**
     * Verify the token from Messenger. This helps verify your bot.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function geophp(Request $request)
    {
        $geometry = geoPHP::load('MULTILINESTRING((10 10,20 20,10 40))','wkt');

        return response("Test reduce" . PHP_EOL . json_encode($geometry), 200);
    }
}
