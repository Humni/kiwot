<?php
namespace App;

use App\FacebookMessage;
use App\Models\Drowning;
use App\Models\FishingAreas;
use App\Models\HuntingAreas;
use Illuminate\Support\Facades\Log;
use stdClass;
use App\HuntingHelper;
use geoPHP;

class TextHelper {


    /**
     * returns all the possible questions you can ask
     *
     * @param $array
     */
    public static function returnQuestionsText($array)
    {


        $output = 'You can ask me things like ';

        foreach ($array as $k => $p) {
            $output .= " '" . $p . "' ~ ";

        }


        $output .= ' Go on - give it a go!';

    }

    /**
     * Get the appropriate reply
     *
     * @param $inMessage
     * @return string
     */
    public static function readMessage($inMessage, $lat, $long)
    {
        $message = "Error! This wasn't supposed to happen";

        if (strpos(strtolower($inMessage), 'can') !== false) {
            $mode = "question";
        } else  if (strpos($inMessage, 'thanks') !== false) {
            $mode = "thank";
        } else {
            $mode = "um";
        }


        if ($mode == "question") {
            $type = "none";
            $array = array(
                'HUNT' => 'Can I hunt here?',
                'SWIM' => 'Can I swim here?',
                'FISH' => 'Can I fish here?',
            );

            foreach ($array as $k => $p) {
                if (strpos(strtolower($inMessage), strtolower($k)) !== false) {
                    $type = $k;
                }
            }


            if ($type == "HUNT") {
                $message = TextHelper::hunting($lat, $long);

                //$message = HuntingHelper::hunt();

            } else if ($type == "SWIM") {
                $message = TextHelper::drownings($lat, $long);

            } else if ($type == 'FISH') {
                $message = "FISHING";

            } else {
                //$message = FacebookMessage::create();
                $message = "I don't know the answer to that question. " . TextHelper::returnQuestionsText($array);
            }


        } else if ($mode == "thank") {
            //$message = FacebookMessage::create();
            $message = "You're welcome.";

        } else if ($mode == "um") {
          //  $message = FacebookMessage::create();
            $message = "Yikes! I'm not sure what you mean by that...";

        } else {
         //   $message = FacebookMessage::create();
            $message = "Yikes! I'm not sure what you mean.";
        }

         return $message;
    }


    /**
     * Drowning helper function
     */
    public static function drownings($lat, $long) {
        // Parse the user location into a geoPHO point
        $userLocation = geoPHP::load("POINT(" . $lat . " " . $long . ")", "wkt");

        $closeness = .01;

        $xSmall = $long - $closeness;
        $xLarge = $long + $closeness;

        $ySmall = $lat - $closeness;
        $yLarge = $lat + $closeness;

        // Do a basic prefilter on the latitude.
        $allLocations = Drowning::where('x', '>', $xSmall)->where('x', '<', $xLarge)->where('y', '>', $ySmall)->where('y', '<', $yLarge)->get();

        $output = "";
        // Look through the possible locations, and see if the point is in the block.
        foreach ($allLocations as $location) {

            $output .= "A drowning occurred from " . $location->Activity . " in " . $location->Year . " near here.  ";


        }

        if ($output != '') {
            return $output;
        }

        return "Great news - no drownings have taken place near you - however, you should always check for rips, and currents. You can learn more about water safety here: http://www.watersafety.org.nz/resources-and-safety-tips/ ";
    }


    /**
     * Drowning helper function
     */
    public static function hunting($lat, $long) {
        // Parse the user location into a geoPHO point
        $userLocation = geoPHP::load("POINT(" . $lat . " " . $long . ")", "wkt");

        // Do a basic pre-filter on the latitude.
        $allLocations = HuntingAreas::where('miny', '<', $lat)
            ->where('maxy', '>', $lat)
            ->where('minx', '<', $long)
            ->where('maxx', '>', $long)->get();

        // Look through the possible locations, and see if the point is in the block.
        foreach ($allLocations as $location) {

            $geo = geoPHP::load($location->WKT, 'wkt');
            //if ($geo->contains($userLocation)) {
            return "You can hunt here! You are in the " . $location->HuntBlockName . " area.  Remember though - you will need a permit first. ";
            //}
        }

        return "You are not in any DOC hunting areas, which means you will need to ask the land owner first if you can hunt in your current location. ";
    }


    /**
     * Drowning helper function
     */
    public static function fishing($lat, $long) {
        // Parse the user location into a geoPHO point
        $userLocation = geoPHP::load("POINT(" . $lat . " " . $long . ")", "wkt");

        // Do a basic pre-filter on the latitude.
        $allLocations = FishingAreas::where('miny', '<', $lat)
            ->where('maxy', '>', $lat)
            ->where('minx', '<', $long)
            ->where('maxx', '>', $long)->get();

        // Look through the possible locations, and see if the point is in the block.
        foreach ($allLocations as $location) {
            return "You can't fish here! You are in the " . $location->Name . ". ";
        }

        return "It doesn't look like you're near any marine reserves! You're free to fish, but make sure you stay within those catch limits!";
    }
}

