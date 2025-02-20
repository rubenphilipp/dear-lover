<!doctype html>
<html>

    <head>
        <meta charset="utf-8">
        <title></title>
        <meta name="description" content="">
    </head>

    <body>

        <?php

        ////////////////////////////////////////
        // INCLUDES
        // -------------------------------------
        // global definitions
        include 'globals.php';
        ////////////////////////////////////////

        ////////////////////////////////////////
        // FUNCTIONS

        // This function takes an array with file names
        // as its first argument (e.g. retrieved via glob)
        // and returns new array with the data retrieved from
        // the files, assuming that the meta-data-YAML contains
        // the following mandatory fields:
        // - date: the date of the respective letter (formatted:
        //   YYYY-MM-DD HH-MM)
        // - file: the video file (relative to MEDIA_DIR)
        // - from: the sender name (either "greta" or "ruben")
        // - to: the recipient name (either "greta" or "ruben")
        // and OPTIONALLY:
        // - comment: a string as a comment
        // - still: path to a still image (relative to MEDIA_DIR)
        //
        // ADDITIONALLY:
        // - the resulting array also includes a "timestamp"
        //   field, which is a UNIX timestamp generated from the
        //   date value
        function parseMetaFiles($fileArray) {
            $res = array();
            foreach($fileArray as $key => $f) {
                $res[$key] = parseMetaFile($f);
            }
            return $res;
        }

        function parseMetaFile($file) {
            $res = array();
            
            $contents = file_get_contents($file);
            $parsed = yaml_parse($contents);

            // date
            $res["date"] = $parsed["date"];
            // file
            $res["file"] = $parsed["file"];
            $res["from"] = $parsed["from"];
            $res["to"] = $parsed["to"];
            if(isset($parsed["comment"])) {
                $res["comment"] = $parsed["comment"];
            }
            else {
                $res["comment"] = null;
            }
            $res["date"] = $parsed["date"];
            // timestamp
            $res["timestamp"] = tsFromStr($parsed["date"]);
            // still
            if(isset($parsed["still"])) {
                $res["still"] = $parsed["still"];
            }
            else {
                $res["still"] = null;
            }

            return $res;
        }

        function tsFromStr($s) {
            $dP = date_parse_from_format("Y-m-d H:i:s", $s);
            $ts = mktime($dP["hour"], $dP["minute"], $dP["second"], $dP["month"], $dP["day"], $dP["year"]);
            return $ts;
        }
        

        ////////////////////////////////////////

        // list all meta-data files:
        $files = glob(DATA_DIR . DATA_SUFFIX);

        $letters = parseMetaFiles($files);

        // sort letters
        uasort($letters,
               function($a, $b) {
                   return ($a["timestamp"] > $b["timestamp"]) ? -1 : 1;
        });

        ////////////////////////////////////////
        // LETTER functions (output):
        ////////////////////////////////////////

        function doLetter($data) {
            echo '<div class="letter">';

            echo '<video controls style="width:100%;" type="video/mp4" poster="'.$data["still"].'" src="' . MEDIA_DIR . $data["file"] . '">\n';
            echo MEDIA_DIR . $data["file"];
            echo "</video>";
            
            echo "</div>\n";
            
        }
        
        foreach($letters as $letter) {
            /* echo $letter["file"];
             * echo "<br>";
             * echo $letter["date"] . " " . $letter["timestamp"];
             * echo "<br>"; */

            doLetter($letter);
        }
        
        ?>

    </body>

</html>
