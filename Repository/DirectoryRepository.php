<?php

namespace Recognize\FilemanagerBundle\Repository;

use Doctrine\ORM\EntityRepository;


/**
 * Class DirectoryRepositor
 */
class DirectoryRepository extends EntityRepository {

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



    /**
     * Get a directory using its working directory, relative path and name
     *
     * @param $working_directory
     * @param $relative_path
     * @param $name
     * @return array
     */
    public function findDirectoryByLocation( $working_directory, $relative_path, $name ){

        $qb = $this->createQueryBuilder('d');
        $qb->where("d.working_directory = :working_directory AND d.relative_path = :relative_path AND d.name = :name")
            ->setParameters(
                array(
                    "working_directory" => $this->addTrailingSlash( $working_directory ),
                    "relative_path" => $this->addTrailingSlash( $relative_path ),
                    "name" => $name
                )
            );

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Get the child directories using the parents working directory, relative path and name
     *
     * @param $working_directory
     * @param $relative_path
     * @param $name
     * @return array
     */
    public function findDirectoryChildrenByLocation($working_directory, $relative_path, $name) {

        $qb = $this->createQueryBuilder('d');
        $qb->where("d.working_directory = :working_directory AND d.relative_path LIKE :relative_path")
            ->setParameters(
                array(
                    "working_directory" => $this->addTrailingSlash( $working_directory ),
                    "relative_path" => $this->addTrailingSlash( $relative_path ) . $name . DIRECTORY_SEPARATOR . "%"
                )
            );

        $query = $qb->getQuery();


        return $query->getResult();
    }


}