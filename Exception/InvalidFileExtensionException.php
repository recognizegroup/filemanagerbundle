<?php
namespace Recognize\FilemanagerBundle\Exception;

class InvalidFileExtensionException extends \RuntimeException {

    protected $message = "The uploaded file has an unauthorized file extension";

}