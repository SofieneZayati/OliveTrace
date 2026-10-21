<?php

$file = __DIR__.'/.env';
$content = file_get_contents($file);
echo 'Length: '.strlen($content)."\n";
echo 'First 100 bytes (hex): '.bin2hex(substr($content, 0, 100))."\n";
echo "First 200 chars:\n".substr($content, 0, 200)."\n";
