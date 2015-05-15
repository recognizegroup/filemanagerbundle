<?php
namespace Recognize\FilemanagerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Repository\DirectoryRepository;
use Recognize\FilemanagerBundle\Response\FileChanges;
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

    public function __construct( EntityManagerInterface $em, DirectoryRepository $directoryRepository ){
        $this->em = $em;
        $this->directoryRepository = $directoryRepository;
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
            $directory->setDirectoryName( $file['name'] );
            $directory->setRelativePath( $file['directory'] );

            $this->em->persist( $directory );
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

            $old_relative_path = $this->addTrailingSlash( $changes_array['file']['directory'] );
            $old_relative_path .= $this->addTrailingSlash( $changes_array['file']['name'] );
            $new_relative_path = $this->addTrailingSlash( $changes_array['updatedfile']['directory'] );
            $new_relative_path .= $this->addTrailingSlash( $changes_array['updatedfile']['name'] );


            // Get the directory that is renamed in the database
            $directories = $this->directoryRepository->findDirectoryByLocation( $working_directory, $file['directory'], $file['name'] );
            for( $i = 0, $length = count($directories); $i < $length; $i++ ){

                /** @var Directory $directory */
                $directory = $directories[ $i ];
                $directory->setRelativePath( $this->addTrailingSlash( $changes_array['updatedfile']['directory'] ) );
                $directory->setDirectoryName( $changes_array['updatedfile']['name'] );
                $this->em->persist( $directory );
            }

            // Get all the child directories
            // and update their relative paths to match the updated parent directory
            $childdirectories = $this->directoryRepository->findDirectoryChildrenByLocation( $working_directory, $file['directory'], $file['name']);
            for( $i = 0, $length = count($childdirectories); $i < $length; $i++ ){

                /** @var Directory $childdirectory */
                $childdirectory = $childdirectories[ $i ];

                // Check if the old relative path is in the child directories path
                $pos = strpos($childdirectory->getRelativePath(), $old_relative_path);
                if ($pos === 0) {

                    $updated_relative_path = substr_replace($childdirectory->getRelativePath(),
                        $new_relative_path, 0, strlen($old_relative_path) );

                    $childdirectory->setRelativePath( $updated_relative_path );

                    $this->em->persist( $childdirectory );
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
                $this->em->remove( $directories[$i] );
            }

            // Get all the directories below the current directory
            $childdirectories = $this->directoryRepository->findDirectoryChildrenByLocation( $working_directory, $file['directory'], $file['name']);
            for( $i = 0, $length = count($childdirectories); $i < $length; $i++ ){
                $this->em->remove( $childdirectories[$i] );
            }

        }

        $this->em->commit();
        $this->em->flush();
    }

    /**
     * Add a trailing slash to a path
     *
     * @param $path
     * @return string
     */
    protected function addTrailingSlash( $path ){
        if( $path != "" &&  substr( $path, -1, 1) !== DIRECTORY_SEPARATOR){
            $path .= DIRECTORY_SEPARATOR;
        }

        return $path;
    }


}