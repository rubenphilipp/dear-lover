<!doctype html>
<html>

    <head>
        <meta charset="utf-8">
        <title>dear lover</title>
        <meta name="description" content="dear lover">
        <meta name="author" content="Greta Gottschalk, Ruben Philipp">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main.css">

        <script src="js/jquery-3.7.1.min.js"></script>
        <script src="js/jquery.lazy/jquery.lazy.min.js"></script>
        <script type="text/javascript" src="js/jquery.lazy/plugins/jquery.lazy.av.min.js"></script>
        <script src="js/main.js"></script>
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
            echo "\n";

            echo '<video class="lazy" style="width:100%;" controls data-poster="'.$data["still"].'">';
            echo "\n";
            echo '<data-src src="' . MEDIA_DIR . $data["file"] . '" type="video/mp4"></data-src>';
            echo "\n";
            echo "</video>\n";
            echo '<p style="font-family: sans-serif; font-size: 10px;">' . $data["date"] . "</p>" . "";
            
            echo "</div>\n";
            
        }

        ?>


        <main>
            
            <?php 

            
            ////////////////////////////////////////
            ////////////////////////////////////////
            
            foreach($letters as $letter) {
                // just print existing letters
                if(file_exists(MEDIA_DIR . $letter["file"])){
                    doLetter($letter);
                }
            }
            
            ?>
            
        </main>
        
    </body>

</html>
