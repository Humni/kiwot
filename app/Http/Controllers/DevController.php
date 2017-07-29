<?php

namespace App\Http\Controllers;

use Log;
use geoPHP;
use Illuminate\Http\Request;

class DevController extends Controller
{
    public function geophp(Request $request)
    {
        $geometry = geoPHP::load('MULTILINESTRING((10 10,20 20,10 40))','wkt');

        return response("Test reduce" . PHP_EOL . json_encode($geometry), 200);
    }

    public function geophp(Request $request)
    {
        if (geoPHP::geosInstalled()) {
            return response("We can't work anything out at the moment!", 200);
        }
        return response("GeoPHP is not installed", 400);
    }
}
