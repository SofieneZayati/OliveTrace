<?php

$file = __DIR__.'/.env';
$handle = fopen($file, 'rb');
$firstBytes = fread($handle, 10);
fclose($handle);

echo 'First 10 bytes hex: '.bin2hex($firstBytes)."\n";
echo 'First 10 bytes as chars: ';
for ($i = 0; $i < strlen($firstBytes); $i++) {
    echo ord($firstBytes[$i]).' ';
}
echo "\n";

// Read first 200 bytes and check for any null bytes
$content = file_get_contents($file);
$nullPositions = [];
for ($i = 0; $i < strlen($content); $i++) {
    if (ord($content[$i]) === 0) {
        echo "Null byte at position $i\n";
    }
}
echo 'Total null bytes: '.substr_count($content, chr(0))."\n";

// Check if file is being read differently by different methods
$content1 = file_get_contents($file);
$handle = fopen($file, 'r');
$content2 = fread($handle, filesize($file));
fclose($handle);

echo 'file_get_contents length: '.strlen($content1)."\n";
echo 'fread length: '.strlen($content2)."\n";
echo 'Contents equal: '.($content1 === $content2 ? 'YES' : 'NO')."\n";

// Check if there's a hidden BOM
$bom = substr($content1, 0, 3);
echo 'First 3 bytes hex: '.bin2hex($bom)."\n";

// Try reading with different encodings
$utf8 = mb_convert_encoding($content1, 'UTF-8', 'UTF-8');
echo 'After UTF-8 conversion: '.substr($utf8, 0, 50)."\n";
