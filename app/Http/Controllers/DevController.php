<?php

namespace App\Http\Controllers;

use Log;
use geoPHP;
use Illuminate\Http\Request;
use App\Models\HuntingAreas;
use App\Models\Drowning;
use NlpTools\Documents\DocumentInterface;
use NlpTools\Tokenizers\WhitespaceTokenizer;
use NlpTools\Models\FeatureBasedNB;
use NlpTools\Documents\TrainingSet;
use NlpTools\Documents\TokensDocument;
use NlpTools\FeatureFactories\DataAsFeatures;
use NlpTools\Classifiers\MultinomialNBClassifier;
use SebastianBergmann\CodeCoverage\Report\PHP;


// Latitude is Y
class DevController extends Controller
{
    /**
     * Checks if GEOS is installed correctly
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
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
        } else if ($type == "fish") {
            return DevController::handleFish($lat, $long);

        } else if ($type == "drown") {
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

    public function handleFish($lat, $long) {

    }


    /**
     * Triggers the training process for the Natural Language Processing
     *
     * @param Request $request
     */
    public function train_nlp(Request $request){
        $training = array(
            array('fish',"can I fish here"),
            array('fish',"what can I fish"),
            array('fish',"can I fish"),
            array('fish',"fishing, am I allowed?"),
            array('fish',"am I allowed to fish here"),
            array('fish',"is fishing allowed here"),
            array('fish',"what fish can I catch"),
            array('fish',"what can I catch here?"),
            array('fish',"what is the fishing quotas here"),
            array('fish',"what are the fishing quotas here"),
            array('fish',"what are the bag limits here"),
            array('fish',"can I catch fish here"),
            array('fish',"i want to fish here"),
            array('fish',"who can fish here"),
            array('fish',"where can I fish"),
            array('fish',"fish"),

            array('hunt',"what permits do I need to hunt here"),
            array('hunt',"can I hunt here?"),
            array('hunt',"what can I hunt here"),
            array('hunt',"can I hunt with dogs here"),
            array('hunt',"am I allowed to hunt here"),
            array('hunt',"is hunting allowed"),
            array('hunt',"is hunting allowed here"),
            array('hunt',"can I trap here"),
            array('hunt',"can I shoot here"),
            array('hunt',"is stalking allowed here"),
            array('hunt',"is deer stalking allowed"),
            array('hunt',"can i come on this land to hunt"),
            array('hunt',"can i kill animals here"),
            array('hunt',"can i stalk here"),
            array('hunt',"where can I hunt"),
            array('hunt',"hunt"),

            array('swim',"can I swim here"),
            array('swim',"is swiming allowed here"),
            array('swim',"is it safe to swim here"),
            array('swim',"can I swim safely here"),
            array('swim',"what swimming dangers are there"),
            array('swim',"is the water safe to swim in"),
            array('swim',"is the water polluted here"),
            array('swim',"can I go for a dip?"),
            array('swim',"is it safe to go for a dip here"),
            array('swim',"have people drowned here"),
            array('swim',"is this a high drowning rate area"),
            array('swim',"do people drown here"),
            array('swim', "Have there been any drownings?"),
            array('swim',"could I drown here"),
            array('swim',"has anyone drowned here"),
            array('swim',"where can I swim"),
            array('swim',"swim"),
        );

        $testing = array(
            //we need some proper testing data...
        );

        $tset = new TrainingSet(); // will hold the training documents
        $tok = new WhitespaceTokenizer(); // will split into tokens
        $ff = new DataAsFeatures(); // see features in documentation

        // ---------- Training ----------------
        foreach ($training as $d)
        {
            $tset->addDocument(
                $d[0], // class
                new TokensDocument(
                    $tok->tokenize($d[1]) // The actual document
                )
            );
        }

        $model = new FeatureBasedNB(); // train a Naive Bayes model
        $model->train($ff,$tset);

        //dd($model);

        // ---------- Classification ----------------
        $cls = new MultinomialNBClassifier($ff,$model);
        $correct = 0;
        foreach ($testing as $d)
        {
            $tokens = $tok->tokenize($d[1]);
            $document = new TokensDocument(
                $tokens // The document
            );
            // predict if it is spam or ham
            $prediction = $cls->classify(
                array('hunt','fish', 'swim', 'other'), // all possible classes
                $document
            );

            $accuracy = $this->getConfidence($cls, $prediction, $document, count($tokens));
            printf("Confidence for '" . $d[1] . "' to be in '" . $prediction . "': %.2f\n", $accuracy);

            if ($prediction==$d[0])
                $correct ++;
        }

        printf("Accuracy: %.2f\n", 100*$correct / count($testing));
    }


    private function getConfidence(MultinomialNBClassifier $cls, $class, DocumentInterface $d, $tokenCount)
    {
//        $score = log($this->model->getPrior($class));
//        $features = $this->feature_factory->getFeatureArray($class, $d);
//        if (is_int(key($features)))
//            $features = array_count_values($features);
//        foreach ($features as $f => $fcnt) {
//            $score += $fcnt * log($this->model->getCondProb($f, $class));
//        }
////        return $score;
//        $score = log($cls->model->getPrior($class));
//        $features = $cls->feature_factory->getFeatureArray($class, $d);
//        if (is_int(key($features)))
//            $features = array_count_values($features);
//
//        foreach ($features as $f => $fcnt) {
//            $score += $fcnt * log($cls->model->getCondProb($f, $class));
//        }

        $score = $cls->getScore($class, $d);

        print($score.PHP_EOL);
//        print($tokenCount.PHP_EOL);
        return ($score/$tokenCount);
    }
}
