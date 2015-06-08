<?php
namespace Recognize\FilemanagerBundle\Tests\TestUtils;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Bundle\FrameworkBundle\Client;

/**
* Base class for testing the CLI tools.
*
* @author Alexandre Salomé <alexandre.salome@gmail.com>
*/
abstract class CommandTestCase extends WebTestCase {

    protected function getInputStream($input) {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

}