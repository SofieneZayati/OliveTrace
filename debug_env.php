<?php

$file = __DIR__.'/.env';
$content = file_get_contents($file);

// Check every byte
echo 'File size: '.filesize($file)."\n";
echo 'Content length: '.strlen($content)."\n";

// Check first 200 bytes in detail
for ($i = 0; $i < min(200, strlen($content)); $i++) {
    $byte = ord($content[$i]);
    $char = $content[$i];
    if ($byte === 0) {
        echo "NULL byte at position $i\n";
    }
    if ($byte < 32 && $byte !== 10 && $byte !== 13) {
        echo "Control char at $i: byte=$byte char='".($char === "\n" ? '\\n' : ($char === "\r" ? '\\r' : '?'))."'\n";
    }
}

// Check if file has UTF-16 LE encoding (every other byte is 0)
$hasNullBytes = false;
for ($i = 0; $i < min(200, strlen($content)); $i++) {
    if (ord($content[$i]) === 0) {
        $hasNullBytes = true;
        break;
    }
}
echo 'Has null bytes in first 200: '.($hasNullBytes ? 'YES' : 'NO')."\n";

// Check if file is actually UTF-16
$encoding = mb_detect_encoding($content, 'UTF-8, UTF-16LE, UTF-16BE, ASCII', true);
echo 'Detected encoding: '.($encoding ?: 'unknown')."\n";

// Try to read as UTF-16LE
$utf16 = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
echo 'As UTF-16LE first 100 chars: '.substr($utf16, 0, 100)."\n";
