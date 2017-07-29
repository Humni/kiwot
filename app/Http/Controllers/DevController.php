<?php

namespace App\Http\Controllers;

use Log;
use geoPHP;
use Illuminate\Http\Request;
use App\Models\HuntingAreas;


// Latitude is Y
class DevController extends Controller
{
    public function geophp(Request $request)
    {
        if(geoPHP::geosInstalled()) {
            return response("We can't work anything out at the moment!", 200);
        }
        return response("GeoPHP installed: " . json_encode(geoPHP::geosInstalled()), 400);
    }



    public function parseMessage(Request $request) {

        $type = $request->input('type');
        $lat = $request->input('lat');
        $long = $request->input('long');


        if ($type == "hunt") {
            return DevController::handleHunt($lat, $long);
        } else if ($type == "drown") {
            return DevController::handleFish($lat, $long);

        } else if ($type == "fish") {
            return DevController::handleDrown($lat, $long);

        } else {
            return "Whoops! ";

        }

    }

    public function handleHunt($lat, $long) {

        // Parse the user location into a geoPHO point
        $userLocation = geoPHP::load("POINT(" . $lat . " " . $long . ")", "wkt");


        // Do a basic prefilter on the latitude.
        $allLocations = HuntingAreas::where('miny', '<', $lat)->where('maxy', '>', $lat)->get();


        if (!geoPHP::geosInstalled()) {
            return "We can't work anything out at the moment!";
        }

        // Look through the possible locations, and see if the point is in the block.
        foreach ($allLocations as $location) {

            $geo = geoPHP::load($location->WKT, 'wkt');
            if ($geo->contains($userLocation)) {
                return "You can hunt here! You are in the " . $location->HuntBlockName . " area.  Remember though - you will need a permit first. ";
                break;
            }

            $geo = null;
            $location = null;
            $code = null;

        }

        return "You are not in any DOC hunting areas, which means you will need to ask the land owner first if you can hunt in your current location. ";


    }

    public function handleDrown($lat, $long) {



    }

    public function handleFish($lat, $long) {



    }



}
