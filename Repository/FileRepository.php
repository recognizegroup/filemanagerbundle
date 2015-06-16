<?php

namespace Recognize\FilemanagerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;
use SplFileInfo;


/**
 * Class FileRepositor
 */
class FileRepository extends EntityRepository {

    /**
     * Get all the files underneath this directory one level deep
     *
     * @param Directory $directory
     * @return array FileReference
     */
    public function getFilesInDirectory( Directory $directory ){

        $qb = $this->createQueryBuilder('d');
        $qb->where("d.directory_id = :directory_id")
            ->setParameters(
                array(
                    "directory_id" => $directory->getId(),
                )
            );

        $query = $qb->getQuery();
        $results = $query->getResult();
        return $results;
    }

    /**
     * Get all the files underneath this directory one level deep
     *
     * @param Directory $directory
     * @param string $filename
     * @return FileReference or null
     */
    public function getFile( Directory $directory, $filename ){

        $qb = $this->createQueryBuilder('d');
        $qb->where("d.directory_id = :directory_id AND d.filename = :filename")
            ->setParameters(
                array(
                    "directory_id" => $directory->getId(),
                    "filename" => $filename
                )
            );

        $query = $qb->getQuery();
        $results = $query->getResult();
        if( count($results) > 0 ){
            return $results[0];
        } else {
            return null;
        }
    }

    /**
     * Check if there are filereferences below this path in the database
     *
     * @param string $working_directory
     * @param string $relative_path
     * @return bool
     */
    public function referencesExistBelowPath($working_directory, $relative_path){
        $qb = $this->createQueryBuilder('d');

        // The root dir
        if( $relative_path == "" ) {
            $qb->where("d.working_directory = :working_directory")
                ->setParameters(
                    array(
                        "working_directory" => $working_directory,
                    )
                );
        // The dirs below the root dir
        } else {
            $qb->where("d.working_directory = :working_directory AND ( d.relative_path = :relative_path OR d.relative_path LIKE :relative_path_percent )")
                ->setParameters(
                    array(
                        "working_directory" => $working_directory,
                        "relative_path" => $relative_path,
                        "relative_path_percent" => $relative_path . "%"
                    )
                );
        }
        $qb->setMaxResults(1);


        $query = $qb->getQuery();
        $results = $query->getResult();
        return count($results) > 0;
    }

    /**
     * Get all the indexed image files
     *
     * @return array
     */
    public function getAllImageFiles( ){
        $qb = $this->createQueryBuilder('d');
        $qb->where("d.mimetype LIKE 'image%'");

        return $qb->getQuery()->getResult();
    }
}
