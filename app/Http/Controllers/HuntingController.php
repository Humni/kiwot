<?php

namespace App\Http\Controllers;


use App\HuntingAreas;
use Illuminate\Http\Request;
use geoPHP;

class HuntingController extends Controller
{

    public function test(Request $request)
    {

        $lat = (float)$request->input('lat');
        $long = (float)$request->input('long');


        $userLocation = geoPHP::load("POINT(" . $lat . " " . $long . ")", "wkt");


        $allLocations = HuntingAreas::all();

        $loop = 0;


        if (geoPHP::geosInstalled()) {
            return response("We can't work anything out at the moment!", 200);

        }


        foreach ($allLocations as $location) {


            $geo = geoPHP::load($location->WKT, 'wkt');

            if ($geo->contains($userLocation)) {
                echo "location";
                die();

            }

            $geo = null;
           // echo "Not in " . $location->HuntBlockName . PHP_EOL;


            // 169.102510387641786 -45.320920379371287
        }


        return response("You can't hunt here!", 200);
    }


}
