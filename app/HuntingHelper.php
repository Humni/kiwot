<?php
namespace App;

use geoPHP;

class HuntingHelper
{


    public static function hunt(Request $request)
    {


        $lat = (float)$request->input('lat');
        $long = (float)$request->input('long');
        $message = $request->input('message');


        $userLocation = geoPHP::load("POINT(" . $lat . " " . $long . ")", "wkt");


        $allLocations = HuntingAreas::where('minx', '>', $lat)->where('maxx', '<', $lat)->get();


        if (geoPHP::geosInstalled()) {
            return response("We can't work anything out at the moment!", 200);

        }



        foreach ($allLocations as $location) {


            $geo = geoPHP::load($location->WKT, 'wkt');




            if ($geo->contains($userLocation)) {
                echo "location";

                return "You can hunt here! You are in the " . $location->HuntBlockName . ' area.  Remember though - you will need a permit first. ';

                break;

            }

            $geo = null;
            $location = null;
            $code = null;

            return "You are not in any DOC hunting areas, which means you will need to ask the land owner first if you can hunt in your current location. ';


        }
    }

}