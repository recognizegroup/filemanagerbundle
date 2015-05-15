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

    /**
     * Turn a list of actions into a bitmask for the ACL system
     *
     * @param $values
     * @return int
     */
    public static function getMaskFromValues( $values ){
        $maskbuilder = new DirectoryMaskBuilder();
        for( $i = 0, $length = count( $values); $i < $length; $i++ ){
            switch( strtolower( $values[$i] ) ){
                case "open":
                    $maskbuilder->add( self::OPEN );
                    break;
                case "upload":
                    $maskbuilder->add( self::UPLOAD );
                    break;
                case "create":
                    $maskbuilder->add( self::CREATE );
                    break;
                case "rename":
                    $maskbuilder->add( self::RENAME );
                    break;
                case "move":
                    $maskbuilder->add( self::MOVE );
                    break;
                case "delete":
                    $maskbuilder->add( self::DELETE );
                    break;
                case "mask_owner":
                    $maskbuilder->add( MaskBuilder::MASK_OWNER );
                    break;
            }
        }

        return $maskbuilder->get();
    }

}