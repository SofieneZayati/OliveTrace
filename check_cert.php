<?php

$cafile = 'C:\Users\jenhani\scoop\apps\php\current\extras\ssl\cacert.pem';
var_dump(file_exists($cafile));
var_dump(filesize($cafile));
$content = file_get_contents($cafile);
echo 'First 100 chars: '.substr($content, 0, 100)."\n";
