<?php

// Make sure the bootstrapping process is only run once
if( defined('IS_TEST_BOOTED') == false ) {
    define('IS_TEST_BOOTED', true);

    function loadAutoLoad($level = 0, $path = __FILE__) {
        $path = dirname($path);
        $dependencyfile = $path . '/app/autoload.php';
        if (!file_exists($dependencyfile)) {
            if ($level < 20) {
                return loadAutoLoad($level + 1, $path);
            } else {
                throw new RuntimeException('Autoload not found');
            }
        } else {
            return $path;
        }
    }

    $rootpath = loadAutoLoad();
    $autoload = require_once($rootpath . '/app/autoload.php');
    $_SERVER['KERNEL_DIR'] = $rootpath . '/app/';

    $verbose = false;
    for( $i = 0, $length = count( $_SERVER['argv']); $i < $length; $i++ ) {
        if( $_SERVER['argv'][$i] == "--verbose" ){
            $verbose = true;
        }
    }

    for( $i = 0, $length = count( $_SERVER['argv']); $i < $length; $i++ ){
        if( $_SERVER['argv'][$i] == "--testsuite" ){
            if( $_SERVER['argv'][$i + 1] == "functional" || $_SERVER['argv'][$i + 1] == "all"){

                require_once( $rootpath . '/app/AppKernel.php');
                $booter = new Recognize\FilemanagerBundle\Tests\TestUtils\Database\DatabaseBooter( $rootpath . '/vendor/recognize/' );
                $booter->clearDatabase( $verbose );
                $booter->createAndAlterDatabase( $verbose );
                $booter->fillDatabaseWithFixtures( $verbose );
            }
        }
    }
}