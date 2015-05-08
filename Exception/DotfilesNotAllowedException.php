<?php
namespace Recognize\FilemanagerBundle\Exception;

use RuntimeException;

class DotfilesNotAllowedException extends RuntimeException {

    protected $message = "Single dots or double dots aren't allowed in the path";

}