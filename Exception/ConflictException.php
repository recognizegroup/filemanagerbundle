<?php
namespace Recognize\FilemanagerBundle\Exception;

use RuntimeException;

class ConflictException extends RuntimeException {

    protected $message = "File or folder already exists";

}