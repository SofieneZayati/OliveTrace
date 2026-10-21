<?php

$c = file_get_contents('.env');
echo 'Has BOM: '.(substr($c, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF) ? 'YES' : 'NO')."\n";
echo 'Has UTF-16 LE BOM: '.(substr($c, 0, 2) === chr(0xFF).chr(0xFE) ? 'YES' : 'NO')."\n";
echo 'Has UTF-16 BE BOM: '.(substr($c, 0, 2) === chr(0xFE).chr(0xFF) ? 'YES' : 'NO')."\n";
echo 'First 20 bytes hex: '.bin2hex(substr($c, 0, 20))."\n";
echo 'Has null bytes in first 100: '.(strpos(substr($c, 0, 100), chr(0)) !== false ? 'YES' : 'NO')."\n";
