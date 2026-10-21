<?php

$file = __DIR__.'/.env';
$content = file_get_contents($file);

// Remove trailing null bytes
$content = rtrim($content, "\0");

// Also ensure no BOM
if (substr($content, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF)) {
    $content = substr($content, 3);
}

// Ensure LF line endings
$content = str_replace("\r\n", "\n", $content);
$content = str_replace("\r", "\n", $content);

file_put_contents($file, $content);
echo "Cleaned .env file\n";
echo 'New length: '.strlen(file_get_contents($file))."\n";
