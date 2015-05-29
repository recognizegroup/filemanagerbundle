<?php
namespace Recognize\FilemanagerBundle\Form\DataTransformer;

use Recognize\FilemanagerBundle\Entity\FileReference;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileToPathTransformer implements DataTransformerInterface {

    /**
     * @param $value
     * @return mixed
     */
    public function transform($value) {
        if ( $value instanceof FileReference ){
            $value = $value->getFilename();
        }

        return $value;
    }

    public function reverseTransform($value) {
        return $value;
    }
}