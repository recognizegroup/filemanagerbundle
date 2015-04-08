<?php
namespace Recognize\FilemanagerBundle\Exception;

class FileTooLargeException extends \RuntimeException {

    protected $message = "The uploaded file was too large";

}