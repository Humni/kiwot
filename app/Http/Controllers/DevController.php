<?php

namespace App\Http\Controllers;

use Log;
include_once('../../../vendor/phayes/geophp/geoPHP.inc');

use Illuminate\Http\Request;

class DevController extends Controller
{
    public function geophp(Request $request)
    {
        if(geoPHP::geosInstalled()) {
            return response("We can't work anything out at the moment!", 200);
        }
        return response("GeoPHP is not installed: " . geoPHP::geosInstalled(), 400);
    }
}
