<?php
namespace Recognize\FilemanagerBundle\Tests\TestFixtures;

class TestPNG {

    private $base64contents = "
iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3wYCDCQXcdSkdAAAAAxJREFUCNdjUFVVBQAA4gBw5NNMPwAAAABJRU5ErkJggg==
";

    public function getContents(){
        return base64_decode( $this->base64contents );
    }

}