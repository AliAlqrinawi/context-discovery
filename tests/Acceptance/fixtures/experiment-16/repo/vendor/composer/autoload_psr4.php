<?php

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Illuminate\\' => array($vendorDir . '/laravel/framework/src/Illuminate'),
    'Tests\\' => array($baseDir . '/tests'),
    'App\\' => array($baseDir . '/app'),
);
