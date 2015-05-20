<?php
namespace Recognize\FilemanagerBundle\Exception;

use RuntimeException;

class UploadException extends RuntimeException {

    protected $message = "File could not be uploaded";

}