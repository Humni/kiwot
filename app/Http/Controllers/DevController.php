<?php

namespace App\Http\Controllers;

use Log;
use geoPHP;
use Illuminate\Http\Request;

class DevController extends Controller
{
    public function geophp(Request $request)
    {
        if(geoPHP::geosInstalled()) {
            return response("We can't work anything out at the moment!", 200);
        }
        return response("GeoPHP installed: " . json_encode(geoPHP::geosInstalled()), 400);
    }
}
