<?php
namespace Recognize\FilemanagerBundle\Security;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class DirectoryMaskBuilder extends MaskBuilder {

    const OPEN = 1;           // 1 << 0
    const UPLOAD = 2;         // 1 << 1
    const CREATE = 4;           // 1 << 2
    const RENAME = 8;         // 1 << 3
    const MOVE = 16;      // 1 << 4
    const DELETE = 32;      // 1 << 5

}