<?php
namespace Recognize\FilemanagerBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FilemanagerService {

    private $finder;
    private $filesystem;
    private $working_directory;

    public function __construct( array $configuration ){
        $this->finder = new Finder();
        $this->filesystem = new Filesystem();

        if( isset( $configuration['default_directory'] ) ){
            $this->working_directory = $configuration['default_directory'];
        } else {
            throw new \RuntimeException( "Default upload and file management directory should be set! " );
        }
    }

    /**
     * Get the contents as SplFileInfo objects
     *
     * @param string $directory_path            The relative path from the working directory
     * @param int $depth                        The depth level of the directories to get
     * @return SplFileInfo[] files
     */
    public function getDirectoryContents( $directory_path = "", $depth = 0 ){
        $finder = new Finder();
        $path = $this->working_directory . DIRECTORY_SEPARATOR . $directory_path;

        // We have to prepend the less than sign to get all the contents from the nested directories
        if( $depth !== 0 ){
            $depth = "<" . $depth;
        }

        $finder->depth( $depth )->in( $path );

        return $this->finderToFilesArray( $finder );
    }

    /**
     * Search the matching filenames inside the directory
     *
     * @param string $directory_path            The relative path from the working directory
     * @param $search_value                     A Regular expression to match the filenames with
     * @param $current_directory_only           Only search in this directory, not inside the nested directories
     * @return array                            The files that match the search value
     */
    public function searchDirectoryContents( $directory_path = "", $search_value, $current_directory_only = false ){
        $finder = new Finder();
        $path = $this->working_directory . DIRECTORY_SEPARATOR . $directory_path;

        $search_filter = function( SplFileInfo $file ) use ($search_value) {
            if( preg_match($search_value, $file->getFilename()) ){
                return true;
            } else {
                return false;
            }
        };

        $finder->in( $path );
        if( $current_directory_only !== false ){
            $finder->depth( 0 );
        }
        $finder->filter( $search_filter );

        return $this->finderToFilesArray( $finder );
    }

    /**
     * Turn the contents of the Finder object into an array
     *
     * @param Finder $finder
     * @return array
     */
    protected function finderToFilesArray( Finder $finder ){
        $files = array();
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $files[] = $file;
        }

        return $files;
    }
}