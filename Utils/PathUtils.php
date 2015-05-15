<?php
namespace Recognize\FilemanagerBundle\Utils;

class PathUtils {

    /**
     * Add a trailing slash to a path
     *
     * @param string $path
     * @return string
     */
    public static function addTrailingSlash( $path ){
        if( $path != "" &&  substr( $path, -1, 1) !== DIRECTORY_SEPARATOR){
            $path .= DIRECTORY_SEPARATOR;
        }

        return $path;
    }

    /**
     * Remove the final pathnode and create a new path that links to the parent directory
     *
     * @param string $path
     * @return string
     */
    public static function moveUpPath( $path ){
        if( $path != ""){
            $pathnodes = explode(DIRECTORY_SEPARATOR, self::removeMultipleSlashes( $path ) );
            $pathnodes = array_filter( $pathnodes, function( $input ){
                return $input !== "";
            });

            // Reset the array indices
            $pathnodes = array_values( $pathnodes );

            $path = "/";
            for( $i = 0, $length = count( $pathnodes ) - 1; $i < $length; $i++ ){
                $path .= $pathnodes[$i] . "/";
            }
        }

        return $path;
    }

    /**
     * Remove the final pathnode and create a new path that links to the parent directory
     *
     * @param string $path
     * @return string
     */
    public static function getLastNode( $path ){
        if( $path != ""){
            $pathnodes = explode(DIRECTORY_SEPARATOR, self::removeMultipleSlashes( $path ) );

            $pathnodes = array_filter( $pathnodes, function( $input ){
                return $input !== "";
            });

            // Reset the array indices
            $pathnodes = array_values( $pathnodes );


            $length = count( $pathnodes );
            if( $length != 0){

                $path = $pathnodes[ $length - 1 ];
            } else {
                $path = "";
            }
        }

        return $path;
    }

    /**
     * Remove multiple consecutive slashes from a path
     *
     * @param $path
     * @return mixed
     */
    public static function removeMultipleSlashes( $path ){
        return preg_replace('~/+~', '/', $path);
    }

}