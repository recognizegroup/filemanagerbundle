<?php

namespace Recognize\FilemanagerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;


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

}
