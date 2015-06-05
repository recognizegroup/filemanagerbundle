<?php
namespace Recognize\FilemanagerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Repository\DirectoryRepository;
use Recognize\FilemanagerBundle\Repository\FileRepository;
use Recognize\FilemanagerBundle\Response\FileChanges;
use Recognize\FilemanagerBundle\Utils\PathUtils;
use Symfony\Component\Finder\SplFileInfo;

class FiledataSynchronizer implements FiledataSynchronizerInterface {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var DirectoryRepository
     */
    private $directoryRepository;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * @var FileACLManagerService
     */
    private $aclservice;

    public function __construct( EntityManagerInterface $em, DirectoryRepository $directoryRepository, FileRepository $fileRepository, FileACLManagerService $aclservice ){
        $this->em = $em;
        $this->directoryRepository = $directoryRepository;
        $this->aclservice = $aclservice;
        $this->fileRepository = $fileRepository;
    }

    /**
     * Synchronise the changes done to the filesystem with the database
     *
     * @param FileChanges $changes
     */
    public function synchronize( FileChanges $changes, $working_directory ){

        switch( $changes->getType() ){
            case "create":
                $this->create( $changes, $working_directory );
                break;
            case "move":
            case "rename":
                $this->update( $changes, $working_directory );
                break;
            case "delete":
                $this->delete( $changes, $working_directory );
                break;
        }
    }

    /**
     * Create a file or a directory in the database
     *
     * @param FileChanges $changes
     * @param $working_directory
     */
    protected function create( FileChanges $changes, $working_directory ){
        $changes_array = $changes->toArray();
        $file = $changes_array['file'];

        $this->em->beginTransaction();
        if( $file['type'] == "dir" ){

            // Create a new directory
            $directory = new Directory();
            $directory->setWorkingDirectory( $working_directory );
            $directory->setRelativePath( $file['directory'] );
            $directory->setDirectoryName( $file['name'] );

            $this->em->persist( $directory );
        } else if( $file['type'] == "file") {

            // Find the directory in the database
            $dir = $this->loadDirectoryForFilepath( $working_directory,  $file['directory'], true );

            // Only save the file in the database if the directory has been found
            if( $dir !== null ) {
                $fileref = new FileReference();
                $fileref->setFilename( $file['name'] );
                $fileref->setParentDirectory( $dir );

                $finfo = @finfo_open( FILEINFO_MIME_TYPE );
                $mimetype = @finfo_file( $finfo, PathUtils::addTrailingSlash( $working_directory ) . $file['path'] );
                @finfo_close( $finfo );

                if( $mimetype == false ){
                    $mimetype = "";
                }
                $fileref->setMimetype( $mimetype );


                $this->em->persist( $fileref );
            }
        }

        $this->em->commit();
        $this->em->flush();
    }

    /**
     * Update the filename a file or a directory in the database
     *
     * @param FileChanges $changes
     * @param $working_directory
     */
    protected function update( FileChanges $changes, $working_directory ){
        $changes_array = $changes->toArray();
        $file = $changes_array['file'];

        $this->em->beginTransaction();
        if( $file['type'] == "dir" ){

            $old_relative_path = PathUtils::addTrailingSlash( $changes_array['file']['directory'] );
            $old_relative_path .= PathUtils::addTrailingSlash( $changes_array['file']['name'] );
            $new_relative_path = PathUtils::addTrailingSlash( $changes_array['updatedfile']['directory'] );
            $new_relative_path .= PathUtils::addTrailingSlash( $changes_array['updatedfile']['name'] );


            // Get the directory that is renamed in the database
            $directories = $this->directoryRepository->findDirectoryByLocation( $working_directory, $file['directory'], $file['name'] );
            for( $i = 0, $length = count($directories); $i < $length; $i++ ){

                /** @var Directory $directory */
                $directory = $directories[ $i ];
                $directory->setRelativePath( PathUtils::addTrailingSlash( $changes_array['updatedfile']['directory'] ) );
                $directory->setDirectoryName( $changes_array['updatedfile']['name'] );
                $this->em->persist( $directory );

                // Update the path variables of the child files
                /** @var FileReference[] $files */
                $files = $this->fileRepository->getFilesInDirectory( $directory );
                for( $j = 0, $jlength = count($files); $j < $jlength; $j++ ){
                    $files[ $j ]->setParentDirectory( $directory );
                    $this->em->persist( $files[ $j ] );
                }
            }

            // Get all the child directories
            // and update their relative paths to match the updated parent directory
            $childdirectories = $this->directoryRepository->findDirectoryChildrenByLocation( $working_directory, $old_relative_path, "");
            for( $i = 0, $length = count($childdirectories); $i < $length; $i++ ){

                /** @var Directory $childdirectory */
                $childdirectory = $childdirectories[ $i ];

                // Just add the new relative path in front of the old path if we are in the root
                $updated_relative_path = $childdirectory->getRelativePath();
                if( $old_relative_path == ""){
                    $updated_relative_path = $new_relative_path . $old_relative_path;
                } else {
                    // Check if the old relative path is in the child directories path
                    $pos = strpos($childdirectory->getRelativePath(), $old_relative_path);
                    if ($pos === 0) {
                        $updated_relative_path = substr_replace($childdirectory->getRelativePath(),
                            $new_relative_path, 0, strlen($old_relative_path));

                    }
                }

                $childdirectory->setRelativePath( $updated_relative_path );

                $this->em->persist( $childdirectory );

                // Update the path variables of the files
                /** @var FileReference[] $files */
                $files = $this->fileRepository->getFilesInDirectory( $childdirectory );
                for( $j = 0, $jlength = count($files); $j < $jlength; $j++ ){
                    $files[ $j ]->setParentDirectory( $childdirectory );
                    $this->em->persist( $files[ $j ] );
                }

            }
        } else if ( $file['type'] == "file" ){

            // Update the parent directory of the file
            /** @var FileReference $current_file */
            $current_file = null;
            $dir = $this->loadDirectoryForFilepath( $working_directory,  $file['directory'] );
            if( $dir !== null ){
                $current_file = $this->fileRepository->getFile( $dir, $file['name'] );
            }

            if( $current_file !== null ){
                $newdir = $this->loadDirectoryForFilepath( $working_directory,  $changes_array['updatedfile']['directory'], true );
                if($newdir !== null ){

                    $current_file->setFileName( $changes_array['updatedfile']['name']);
                    $current_file->setParentDirectory( $newdir );
                    $this->em->persist( $current_file );
                }
            }
        }

        $this->em->commit();
        $this->em->flush();
    }


    /**
     * Delete a file or a directory in the database
     *
     * @param FileChanges $changes
     * @param $working_directory
     */
    protected function delete( FileChanges $changes, $working_directory ){
        $changes_array = $changes->toArray();
        $file = $changes_array['file'];

        $this->em->beginTransaction();
        if( $file['type'] == "dir" ){
            // Get all the directories that match this pattern
            $directories = $this->directoryRepository->findDirectoryByLocation( $working_directory, $file['directory'], $file['name'] );
            for( $i = 0, $length = count($directories); $i < $length; $i++ ){
                $this->aclservice->clearAccessRightsForDirectory( $directories[$i] );

                // Remove the file references underneath this directory
                $files = $this->fileRepository->getFilesInDirectory( $directories[ $i ]);
                for( $j = 0, $jlength = count( $files ); $j < $jlength; $j++ ){
                    $this->em->remove( $files[ $j ] );
                }

                $this->em->remove( $directories[$i] );
            }

            // Get all the directories below the current directory
            $childdirectories = $this->directoryRepository->findDirectoryChildrenByLocation( $working_directory, $file['directory'], $file['name']);
            for( $i = 0, $length = count($childdirectories); $i < $length; $i++ ){
                $this->aclservice->clearAccessRightsForDirectory( $childdirectories[$i] );

                // Remove the file references underneath this directory
                $files = $this->fileRepository->getFilesInDirectory( $childdirectories[ $i ]);
                for( $j = 0, $jlength = count( $files ); $j < $jlength; $j++ ){
                    $this->em->remove( $files[ $j ] );
                }

                $this->em->remove( $childdirectories[$i] );
            }
        } else if ( $file['type'] == "file"){

            // Delete the file
            $dir = $this->loadDirectoryForFilepath( $working_directory,  $file['directory'] );
            if( $dir !== null ){
                $deletedfile = $this->fileRepository->getFile( $dir, $file['name'] );
                if( $deletedfile !== null ){
                    $this->em->remove( $deletedfile );
                }
            }

        }

        $this->em->commit();
        $this->em->flush();
    }

    /**
     * Get a directory object from the database or create it if it doesn't exist
     *
     * @param string $working_directory            The working directory
     * @param string $relative_path                The relative path of the file without the filename
     * @param bool $flush                          Whether to persist the dir or not
     */
    protected function loadDirectoryForFilepath( $working_directory, $relative_path, $flush = false ){

        // Find the directory in the database
        $relpath = PathUtils::removeFirstSlash( PathUtils::moveUpPath( $relative_path ) );
        $dirname = PathUtils::getLastNode( $relative_path );

        if( $relpath == false ){
            $relpath = "";
        }
        $dirs = $this->directoryRepository->findDirectoryByLocation( $working_directory, $relpath, $dirname );

        // If the directory does not exist yet, create it
        if( count($dirs) == 0 ){
            $new_directory = $this->directoryRepository->getEmptyDirectory($working_directory, $relpath, $dirname);
            $this->em->persist( $new_directory );
            if( $flush ){
                $this->em->flush();
            }

            $dirs = $this->directoryRepository->findDirectoryByLocation( $working_directory, $relpath, $dirname );
        }

        return $dirs[0];
    }


    /**
     * Get a FileReference object for a file on the filesystem
     * Or create it if it doesn't exist
     *
     * @param string $working_directory            The working directory
     * @param string $relative_path                The relative path of the file with the filename
     * @return FileReference | null
     */
    public function loadFileReference( $working_directory, $relative_path ){

        // Find the directory in the database
        $dir = $this->loadDirectoryForFilepath( $working_directory,  PathUtils::moveUpPath( $relative_path ), true );
        $this->em->flush();

        $fileref = $this->fileRepository->getFile( $dir, PathUtils::getLastNode( $relative_path ) );
        if( $fileref == null ){

            $fileref = new FileReference();
            $fileref->setFilename( PathUtils::getLastNode( $relative_path ) );
            $fileref->setParentDirectory( $dir );

            $finfo = @finfo_open( FILEINFO_MIME_TYPE );
            $mimetype = @finfo_file( $finfo, PathUtils::addTrailingSlash( $working_directory ) . $relative_path );
            @finfo_close( $finfo );
            if( $mimetype == false ) {
                $mimetype = "";
            }

            $fileref->setMimetype( $mimetype );

            $this->em->persist( $fileref );
            $this->em->flush();
        }

        return $fileref;
    }

    /**
     * Delete a file reference
     *
     * @param FileReference $fileReference
     */
    public function deleteFileReference( FileReference $fileReference ){
        $this->em->remove( $fileReference );
        $this->em->flush();
    }

    /**
     * Delete a directory from the database including its children
     *
     * @param Directory $directory
     */
    public function deleteDirectory( Directory $directory ){
        $this->aclservice->clearAccessRightsForDirectory( $directory );

        // Remove the file references underneath this directory
        $files = $this->fileRepository->getFilesInDirectory( $directory );
        for( $i = 0, $length = count( $files ); $i < $length; $i++ ){
            $this->em->remove( $files[ $i ] );
        }

        // Get all the directories below the current directory
        $childdirectories = $this->directoryRepository->findDirectoryChildrenByLocation(
            $directory->getWorkingDirectory(), $directory->getRelativePath(), $directory->getDirectoryName());
        for( $i = 0, $length = count($childdirectories); $i < $length; $i++ ){
            $this->aclservice->clearAccessRightsForDirectory( $childdirectories[$i] );

            // Remove the file references underneath this directory
            $files = $this->fileRepository->getFilesInDirectory( $childdirectories[ $i ]);
            for( $j = 0, $jlength = count( $files ); $j < $jlength; $j++ ){
                $this->em->remove( $files[ $j ] );
            }

            $this->em->remove( $childdirectories[$i] );
        }

        $this->em->remove( $directory );
        $this->em->flush();
    }

    /**
     * Check if the directory can be removed from the filesystem because it:
     * Doesn't have any filereferences below it or
     * Doesn't have any directories below it or
     * Doesn't exist in the database
     *
     * @return bool
     */
    public function canDirectoryBeDeletedFromTheFilesystem( $working_directory, $absolute_path ){
        $path = PathUtils::stripWorkingDirectoryFromAbsolutePath($working_directory, $absolute_path );
        $relative_path = PathUtils::removeFirstSlash( PathUtils::addTrailingSlash( PathUtils::moveUpPath( $path ) ) );
        $name = PathUtils::getLastNode( $path );

        if( $this->fileRepository->referencesExistBelowPath( $working_directory, PathUtils::addTrailingSlash( $path ) ) == true
         || count( $this->directoryRepository->findDirectoryChildrenByLocation($working_directory, $relative_path, $name) ) > 0
         || count( $this->directoryRepository->findDirectoryByLocation($working_directory, $relative_path, $name ) ) > 0 ){
            return false;
        } else {
            return true;
        }
    }
}