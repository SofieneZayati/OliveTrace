<?php

require 'vendor/autoload.php';

use Composer\Config;
use Composer\IO\NullIO;

$io = new NullIO;
$config = new Config;
$config->set('cafile', 'C:/Users/jenhani/scoop/apps/php/current/extras/ssl/cacert.pem');
echo "Config set\n";
