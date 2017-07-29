<?php
namespace App;

use App\FacebookMessage;
use stdClass;
use App\HuntingHelper;

class TextHelper {


    public static function returnQuestionsText($array)
    {


        $output = 'You can ask me things like ';

        foreach ($array as $k => $p) {
            $output .= " '" . $p . "' ~ ";

        }


        $output .= ' Go on - give it a go!';

    }
    public static function readMessage($inMessage) {


        $message = new stdClass();




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
                $message->text = "HUNTING";

                $message->text = HuntingHelper::hunt();

            } else if ($type == "SWIM") {
                $message->text = "SWIMMING";

            } else if ($type == 'FISH') {
                $message->text = "FISHING";

            } else {
                //$message = FacebookMessage::create();
                $message->text = "I don't know the answer to that question. " . TextHelper::returnQuestionsText($array);


            }




        } else if ($mode == "thank") {
            //$message = FacebookMessage::create();
            $message->text = "You're welcome.";


        } else if ($mode == "um") {

          //  $message = FacebookMessage::create();
            $message->text = "Yikes! I'm not sure what you mean by that...";


        } else {
         //   $message = FacebookMessage::create();
            $message->text = "Yikes! I'm not sure what you mean.";

        }

         var_dump ($message);



    }

}

