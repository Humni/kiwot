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
        return response("GEOS Version" . GEOSVersion() . PHP_EOL . "GeoPHP is not installed: " . geoPHP::geosInstalled(), 400);
    }
}
