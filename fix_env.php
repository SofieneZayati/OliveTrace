<?php

$file = __DIR__.'/.env';
$content = file_get_contents($file);
$content = str_replace("\r\n", "\n", $content);
$content = preg_replace('/\xEF\xBB\xBF/', '', $content); // Remove BOM if present
file_put_contents($file, $content);
echo "Fixed .env encoding\n";
