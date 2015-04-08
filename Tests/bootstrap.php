<?php

// Get the autoload if this is a dependency
$dependencyfile = dirname(dirname(dirname(dirname(__FILE__)))) . '/vendor/autoload.php';
$file = $dependencyfile;

if (!file_exists($dependencyfile)) {

    // Get the autoload if it's in the src folder
    $sourcefile = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/vendor/autoload.php';
    if(file_exists($sourcefile) ){
        $file = $sourcefile;
    } else {
        throw new RuntimeException('Install dependencies to run test suite.');
    }
}
$autoload = require_once $file;