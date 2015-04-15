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
     * @param string $directory_path            The relative path from the default directory
     * @param int $depth                        The depth level of the directories to get
     * @return SplFileInfo[] files
     */
    public function getDirectoryContents( $directory_path = "", $depth = 0 ){
        $finder = new Finder();
        $path = $this->working_directory . DIRECTORY_SEPARATOR . $directory_path;

        if( $depth !== 0 ){
            $depth = "<" . $depth;
        }

        $finder->depth( $depth )->in( $path );

        $files = array();
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $files[] = $file;
        }

        return $files;
    }


}