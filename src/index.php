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
        <link rel="stylesheet" href="vendor/video-js-8/video-js.css" />

        <!-- <script src="js/jquery-3.7.1.min.js"></script>
             <script src="js/jquery.lazy/jquery.lazy.min.js"></script>
             <script type="text/javascript"
             src="js/jquery.lazy/plugins/jquery.lazy.av.min.js"></script>
             <script src="js/main.js"></script> -->
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
        // - file: the video file (relative to the letter subdirectory)
        // - from: the sender name (either "greta" or "ruben")
        // - to: the recipient name (either "greta" or "ruben")
        // and OPTIONALLY:
        // - comment: a string as a comment
        // - poster: path to a still image (relative to the letter subdirectory)
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

            $relativePath = $file;

            // get the path relative to the LETTERS_DIR
            if (strpos($file, LETTERS_DIR) === 0) {
                $relativePath = substr($file, strlen(LETTERS_DIR));
            }

            $dirname = dirname($relativePath) . "/";
            
            $contents = file_get_contents($file);
            $parsed = yaml_parse($contents);

            // date
            $res["date"] = $parsed["date"];
            // file
            $res["file"] = $dirname . $parsed["file"];
            $res["from"] = $parsed["from"];
            $res["to"] = $parsed["to"];
            if(isset($parsed["comment"])) {
                $res["comment"] = $parsed["comment"];
            }
            else {
                $res["comment"] = null;
            }
            if(isset($parsed["location"])) {
                // also lowercase date
                $res["location"] = strtolower($parsed["location"]);
            }
            else {
                $res["location"] = null;
            }
            $res["date"] = $parsed["date"];
            // timestamp
            $res["timestamp"] = tsFromStr($parsed["date"]);
            // poster
            if(isset($parsed["poster"])) {
                $res["poster"] = $dirname . $parsed["poster"];
            }
            else {
                $res["poster"] = null;
            }

            if(isset($parsed["width"]) && isset($parsed["height"])) {
                $res["width"] = $parsed["width"];
                $res["height"] = $parsed["height"];
            } else {
                $res["width"] = null;
                $res["height"] = null;
            }

            return $res;
        }

        function tsFromStr($s) {
            $dP = date_parse_from_format("Y-m-d H:i:s", $s);
            $ts = mktime($dP["hour"],
                         $dP["minute"],
                         $dP["second"],
                         $dP["month"],
                         $dP["day"],
                         $dP["year"]);
            return $ts;
        }
        

        ////////////////////////////////////////

        // list all meta-data files:
        $files = glob(LETTERS_DIR . "*/" . DATA_FILE);

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

            // Poster-Pfad bestimmen
            $posterPath = ($data["poster"]
                ? LETTERS_DIR . $data["poster"]
                         : generateBase64ColorImage($rndGray,$rndGray,$rndGray));

            // Datei-Pfad und Typ bestimmen
            $filePath = LETTERS_DIR . $data["file"];
            $extension = pathinfo($data["file"], PATHINFO_EXTENSION);

            $type = '';
            if ($extension === 'm3u8') {
                $type = 'application/x-mpegURL';
            } elseif ($extension === 'mp4') {
                $type = 'video/mp4';
            } elseif ($extension === 'mov') {
                $type = 'video/quicktime';
            }

            // --- NEUE VEREINFACHTE LOGIK ---
            $aspectRatio = null;
            $classString = "video-js vjs-default-skin"; // Basis-Klassen

            // Prüfen, ob wir gültige Abmessungen aus der YAML haben
            if (!empty($data["width"]) && !empty($data["height"]) && $data["height"] > 0) {
                // FALL 1: NEUES VIDEO (mit Maßen)
                // Wir berechnen das exakte Verhältnis
                $aspectRatio = $data["width"] . ":" . $data["height"];
                $dataSetup = '{ "fluid": true, "aspectRatio": "'.$aspectRatio.'" }';
                $classString .= " vjs-fluid"; // Immer fluid, Video.js regelt das

            } else {
                // FALL 2: ALTES VIDEO (ohne Maße)
                // Wir verwenden 16:9 als stabilen Fallback-Container.
                $dataSetup = '{ "fluid": true }';
                $classString .= " vjs-16-9"; // 16:9-Klasse als Fallback
            }
            // --- ENDE NEUE LOGIK ---

            // --- Angepasster video-js Block ---
            // Dieser Block verwendet jetzt die oben definierten $classString und $dataSetup
            echo '<video-js
                        id="video-'.basename($data["file"]).'"
                        class="'.$classString.'"
                        controls
                        preload="auto"
                        poster="'.$posterPath.'"
                        data-setup=\''.$dataSetup.'\'>
                    <source src="'.$filePath.'" '.($type ? 'type="'.$type.'"' : '').'>
                    <p class="vjs-no-js">
                        To view this video please enable JavaScript.
                    </p>
                </video-js>';

            ////////////////////////////////////////

            // alte Implementierung via <video> (2025-11-16):
            
            /* echo '<video class="lazy" controls data-poster="'
             *     .($data["poster"]
             *         ? LETTERS_DIR . $data["poster"]
             *     : generateBase64ColorImage($rndGray,$rndGray,$rndGray)).'">';
             * echo "\n";
             * echo '<data-src src="'
             *    . LETTERS_DIR
             *    . $data["file"]
             *    . '" type="video/mp4"></data-src>'; */

            /* echo "\n";
             * echo "</video>\n"; */

            ////////////////////////////////////////

            echo "<p style=\"text-align:right;\">\n";
            echo "an " . $data["to"] . "&emsp;\n";
            echo "von " . $data["from"] . "&emsp;\n";

            if ($data["location"]) echo $data["location"].",&nbsp;";
            
            $date = date_parse_from_format("Y-m-d H:i:s", $data["date"]);
            
            echo $date["day"] . "."
               . $date["month"] . "."
               . $date["year"] . "\n";

            //echo '<p>an:' . $data["to"] . '_' . $data["date"] . '</p>';

            //echo $data["comment"] ? nl2br($data["comment"]) : "";
            
            echo "</div>\n";
            
        }

        ?>
        <div class="main">

            <header>
                <h1>
                    <a style="cursor: e-resize;" href="manifesto">
                        dear lover
                    </a>
                </h1>
            </header>
            
            <main>

                <?php 

                
                ////////////////////////////////////////
                ////////////////////////////////////////
                
                foreach($letters as $letter) {
                    // just print existing letters
                    if(file_exists(LETTERS_DIR . $letter["file"])){
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

        <script src="vendor/video-js-8/video.min.js"></script>
        
    </body>

</html>
