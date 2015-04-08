<?php
namespace Recognize\FilemanagerBundle\Model;

class Directory extends File {

    protected $filter_function = false;

    /**
     * An object representing a directory
     *
     * @param string $path                     The path to the directory
     * @param string $directoryname            The name of the directory
     * @param int $hydratelevel                The nesting level to gather the directories contents
     *                                         Boolean true will gather all the files
     */
    public function __construct( $path, $directoryname, $hydratelevel = 0 ){
        parent::__construct( $path, $directoryname );

        if( $hydratelevel == true ){
            if( is_int( $hydratelevel ) ){
                $hydratelevel--;
            }

            $this->hydrateDirectory( $hydratelevel );
        }
    }

    public function getFiletype() {
        return "dir";
    }

    /**
     * Hydrate the directory until the
     *
     * @param $hydratelevel
     */
    protected function hydrateDirectory( $hydratelevel ){
        $children = array();

        $directorypath = $this->path . DIRECTORY_SEPARATOR . $this->filename;
        if( is_dir( $directorypath ) ){

            // Set the filtering function
            $filter_function = function(){ return true; };
            if( $this->filter_function !== false ){
                $filter_function = $this->filter_function;
            }

            $directoryhandle = opendir( $directorypath );
            if($directoryhandle == true){
                while(($filename = readdir( $directoryhandle)) !== false){

                    // Make sure the relative directories aren't included
                    if( $filename === "." || $filename === ".." ){
                        continue;
                    }

                    $file = false;
                    if( $filename == "file" ){
                        $file = new File( $directorypath, $filename );
                    } else if ( $filename == "dir" ) {
                        $file = new Directory( $directorypath, $filename, $hydratelevel );
                    }

                    // Filters the directories and files that aren't allowed in
                    if( $filter_function( $file ) == true ){
                        $children[ $filename ] = $file;
                    }
                }

                closedir( $directoryhandle);
            }
        }
    }
}