<!doctype html>
<html>

    <head>
        <meta charset="utf-8">
        <title></title>
        <meta name="description" content="">
    </head>

    <body>

        <?php

        $files = glob('data/*.txt');

        foreach($files as $file) {
            echo "<br>";

            $contents = file_get_contents($file);
            $parsed = yaml_parse($contents);
            
            echo $file . "<br>";
        }

        ?>

    </body>

</html>
