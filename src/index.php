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

        // set random seed
        srand(148723849200293);
        ////////////////////////////////////////

        ////////////////////////////////////////
        // FUNCTIONS


        // Generate a base64 color-string (e.g. for the video-posters).
        function generateBase64ColorImage($r, $g, $b, $width = 1, $height = 1) {
            // Create a blank image
            $image = imagecreatetruecolor($width, $height);
            // Allocate the color
            $color = imagecolorallocate($image, $r, $g, $b);
            // Fill the image with the color
            imagefill($image, 0, 0, $color);
            // Capture the output
            ob_start();
            imagepng($image);
            $imageData = ob_get_clean();
            // Destroy the image to free memory
            imagedestroy($image);
            // Encode the image data to base64
            return 'data:image/png;base64,' . base64_encode($imageData);
        }

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
        // - poster: path to a still image (relative to MEDIA_DIR)
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
            // poster
            if(isset($parsed["poster"])) {
                $res["poster"] = $parsed["poster"];
            }
            else {
                $res["poster"] = null;
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

            // random gray
            $rndGray = rand(50,200);
            echo '<video class="lazy" controls data-poster="'.($data["poster"] ? MEDIA_DIR . $data["poster"] : generateBase64ColorImage($rndGray,$rndGray,$rndGray)).'">';
            echo "\n";
            echo '<data-src src="' . MEDIA_DIR . $data["file"] . '" type="video/mp4"></data-src>';
            echo "\n";
            echo "</video>\n";
            echo "<p style=\"text-align:right;\">\n";
            echo "an " . $data["to"] . "&emsp;\n";
            echo "von " . $data["from"] . "&emsp;\n";
            $date = date_parse_from_format("Y-m-d H:i:s", $data["date"]);
            echo $date["day"] . "." . $date["month"] . "." . $date["year"] . "\n";
            
            //echo '<p>an:' . $data["to"] . '_' . $data["date"] . '</p>';

            //echo $data["comment"] ? nl2br($data["comment"]) : "";
            
            echo "</div>\n";
            
        }

        ?>
        <div class="main">
            
            <header>
                <h1><a style="cursor: e-resize;" href="manifesto">dear lover</a></h1>
            </header>
            
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

                ////////////////////////////////////////
                ////////////////////////////////////////
                
                ?>
                
            </main>

            <footer>
                <a href="imprint">imprint</a>
            </footer>

        </div>
        
    </body>

</html>
