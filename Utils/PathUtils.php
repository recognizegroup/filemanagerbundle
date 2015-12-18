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

            $path = self::removeFirstSlash( $path );
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

    /**
     * Remove the first slash from a path
     *
     * @param $path
     * @return mixed
     */
    public static function removeFirstSlash( $path ){
        if( substr($path, 0, 1) == DIRECTORY_SEPARATOR){
            $path = substr( $path, 1 );
        }

        return $path;
    }

    /**
     * Add a copy string to a filepath
     *
     * For example: directory/file.txt will become directory/file(1).txt
     *
     * @param string $filename                     The filename to add the number to
     * @param string $add_number                   The number to add at the end of the file
     */
    public static function addCopyNumber($filename, $add_number){
        $pathnodes = explode(".", $filename);

        $copystring = "(" . $add_number . ")";
        $nodenumber = 0;
        if( count( $pathnodes ) == 1 ){
            $nodenumber = 0;
        } else {
            $nodenumber = count( $pathnodes ) - 2;
        }

        if( preg_match( "/\([0-9]+\)/", $pathnodes[ $nodenumber ] ) ){
            $pathnodes[ $nodenumber ] = preg_replace( "/\([0-9]+\)/", $copystring, $pathnodes[ $nodenumber ] );
        } else {
            $pathnodes[ $nodenumber ] .= $copystring;
        }

        $copyfilename = join(".", $pathnodes);
        return $copyfilename;
    }

    /**
     * Remove the working directory from the absolute path if it exists, turning the path into a relative path
     * from the working directory
     *
     * @param $working_directory
     * @param $path
     * @return mixed
     */
    public static function stripWorkingDirectoryFromAbsolutePath($working_directory, $path){
        $count = 1;
        return str_replace(self::addTrailingSlash( $working_directory ), "", $path, $count );
    }
}