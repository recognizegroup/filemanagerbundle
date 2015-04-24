<?php
namespace Recognize\FilemanagerBundle\Service;

use InvalidArgumentException;
use Recognize\FilemanagerBundle\Exception\ConflictException;
use Recognize\FilemanagerBundle\Response\FileChanges;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FilemanagerService {

    private $working_directory;
    protected $current_directory;

    public function __construct( array $configuration ){
        if( isset( $configuration['default_directory'] ) ){
            $this->working_directory = $configuration['default_directory'];
        } else {
            throw new \RuntimeException( "Default upload and file management directory should be set! " );
        }

        $this->current_directory = $this->working_directory;
    }

    /**
     * Set the directory from which files will be retrieved - Keeping the relative path the same as the working directory
     *
     * @param string $relative_path                    The path after the default directory
     */
    public function goToDeeperDirectory( $relative_path ){
        $formatted_path = ltrim( rtrim($relative_path, '/'), '/' );
        $this->current_directory = $this->working_directory . DIRECTORY_SEPARATOR . $formatted_path;
    }

    /**
     * Get the contents as SplFileInfo objects
     *
     * @param string $directory_path            The relative path from the working directory
     * @param int $depth                        The depth level of the directories to get
     *
     * @throws InvalidArgumentException         When the directory or file does not exist
     * @return SplFileInfo[] files
     */
    public function getDirectoryContents( $directory_path = "", $depth = 0 ){
        $finder = new Finder();
        $path = $this->current_directory . DIRECTORY_SEPARATOR . $directory_path;

        // We have to prepend the less than sign to get all the contents from the nested directories
        if( $depth !== 0 ){
            $depth = "<" . $depth;
        }

        try {
            $finder->depth( $depth )->in( $path );
            return $this->finderToFilesArray( $finder );

        } catch( InvalidArgumentException $e ){
            $path_from_workingdir = substr( $this->current_directory, strlen( $this->working_directory )  );

            throw new InvalidArgumentException("Directory '" . $path_from_workingdir . $directory_path . "' does not exist");
        }
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
        $path = $this->current_directory . DIRECTORY_SEPARATOR . $directory_path;

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
            $files[] = $this->transformFileRelativePath( $file );
        }

        return $files;
    }

    /**
     * Because the file returned from the Finder doesn't have the correct relative path from the working directory,
     * We need this function to implement the actual relative path
     *
     * @param SplFileInfo $file
     * @return SplFileInfo
     */
    protected function transformFileRelativePath( $file ){
        $path = $file->getPath() . DIRECTORY_SEPARATOR;
        $absolutepath = $path . $file->getFilename();

        $count = 1;
        $relativePath = str_replace($this->working_directory . DIRECTORY_SEPARATOR, "", $path, $count );
        return new SplFileInfo( $absolutepath, $relativePath, $file->getFilename() );
    }

    /**
     * Get the first file that is collected in the finder
     *
     * @param Finder $finder
     * @return SplFileInfo
     */
    protected function getFirstFileInFinder( Finder $finder ){
        $iterator = $finder->getIterator();
        $iterator->next();
        return $this->transformFileRelativePath( $iterator->current() );
    }

    /**
     * Renames a directory or a file
     *
     * @param string $relative_path_to_file            The relative path from the working directory to the file
     * @param string $new_name                         The new file name
     *
     * @throws FileNotFoundException                   When the file or directory to be renamed does not exist
     * @throws IOException                             When target file or directory already exists
     * @throws IOException                             When origin cannot be renamed
     */
    public function rename( $relative_path_to_file, $new_name ){
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->in($this->current_directory)->path("/^" . $relative_path_to_file . "$/" );

        if( $finder->count() > 0 ){

            $filepath = $this->current_directory . DIRECTORY_SEPARATOR . $relative_path_to_file;
            $oldfile = $this->getFirstFileInFinder( $finder );

            $newfilepath = $oldfile->getPath() . DIRECTORY_SEPARATOR . $new_name;
            $newfile = new SplFileInfo( $newfilepath, $oldfile->getRelativePath(), $new_name );

            // Prevent files from being overwritten
            if( $fs->exists( $newfilepath ) ){
                throw new ConflictException();
            }

            $filechanges = new FileChanges("rename", $oldfile);
            $filechanges->setFileAfterChanges( $newfile );
            $fs->rename( $filepath, $newfilepath );

            return $filechanges;
        } else {
            throw new FileNotFoundException("The file or directory that should be renamed doesn't exist");
        }
    }

    /**
     * Creates a new directory in the current working directory
     *
     * @param UploadedFile $file
     */
    public function createDirectory( $directory_name ){
        $fs = new Filesystem();

        $absolute_directory_path = $this->current_directory . DIRECTORY_SEPARATOR . $directory_name;
        if( $fs->exists( $absolute_directory_path ) == false ){
            try {
                $fs->mkdir( $absolute_directory_path, 0755 );

                $finder = new Finder();
                $finder->in($this->current_directory)->path("/^" . $directory_name . "$/" );
                if( $finder->count() > 0 ){
                    $created_directory = $this->getFirstFileInFinder( $finder );
                    $filechanges = new FileChanges("create", $created_directory);

                    return $filechanges;
                } else {
                    throw new IOException( "Failed to create directory " . $directory_name );
                }

            } catch( IOException $e ){
                throw new IOException("Failed to create directory " . $directory_name );
            }
        } else {
            throw new ConflictException();
        }
    }

    /**
     * Saves the uploaded file into the current working directory
     *
     * @param UploadedFile $file
     * @param string $new_filename                  The name of the new file without the extension
     */
    public function saveUploadedFile( UploadedFile $file, $new_filename ){
        $fs = new Filesystem();

        $absolute_path = $this->current_directory . DIRECTORY_SEPARATOR . $new_filename;
        if( $fs->exists( $absolute_path ) == false ){
            try {
                $file->move( $this->current_directory, $new_filename );

                $finder = new Finder();
                $finder->in( $this->current_directory )->path("/^" . $new_filename . "$/");
                if( $finder->count() > 0 ){
                    $movedfile = $this->getFirstFileInFinder( $finder );
                    return new FileChanges( "create", $movedfile );
                } else {
                    throw new FileException("File not created");
                }
            } catch( FileException $e ){
                throw new FileException("File not created");
            }
        } else {
            throw new ConflictException();
        }
    }
}