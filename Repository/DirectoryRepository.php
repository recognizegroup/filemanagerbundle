<?php

namespace Recognize\FilemanagerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Utils\PathUtils;


/**
 * Class DirectoryRepositor
 */
class DirectoryRepository extends EntityRepository {

    /**
     * Get the parent of the directory using its working directory and relative path
     *
     * @param $working_directory
     * @param $relative_path
     * @return array
     */
    public function findParentDirectory( $working_directory, $relative_path ){

        if( $relative_path == false ){
            $relative_path = "";
        }

        $parent_relativepath = PathUtils::removeFirstSlash( PathUtils::moveUpPath( $relative_path ) );
        $parent_name = PathUtils::getLastNode( $relative_path );

        $qb = $this->createQueryBuilder('d');
        $qb->where("d.working_directory = :working_directory AND d.relative_path = :relative_path AND d.name = :name")
            ->setParameters(
                array(
                    "working_directory" => PathUtils::addTrailingSlash( $working_directory ),
                    "relative_path" => $parent_relativepath,
                    "name" => $parent_name
                )
            );

        $query = $qb->getQuery();
        $results = $query->getResult();
        if( count($results ) == 0 ){
            return $this->getEmptyDirectory( $working_directory, $parent_relativepath, $parent_name );
        } else {
            return $results[ 0 ];
        }
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
                    "working_directory" => PathUtils::addTrailingSlash( $working_directory ),
                    "relative_path" => PathUtils::addTrailingSlash( $relative_path ),
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
        if( $relative_path !== "" && $relative_path !== ""){
            $children_path = PathUtils::addTrailingSlash( $relative_path ) . $name;
            $children_path = PathUtils::addTrailingSlash( $children_path );

            $qb->where("(d.working_directory = :working_directory AND d.relative_path LIKE :relative_path) OR
            (d.working_directory = :working_directory AND d.relative_path = :real_relative_path)" )
                ->setParameters(
                    array(
                        "working_directory" => PathUtils::addTrailingSlash( $working_directory ),
                        "relative_path" => $children_path . "%",
                        "real_relative_path" => $children_path
                    )
                );

        // Root folder
        } else {
            $qb->where("d.working_directory = :working_directory AND d.name <> :name")
                ->setParameters(
                    array(
                        "working_directory" => PathUtils::addTrailingSlash( $working_directory ),
                        "name" => ""
                    )
                );
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Make a directory without an ID using the paths
     *
     * @param $working_directory                The working directory
     * @param $relative_path                    The relative path from the working directory
     * @param $name                             The name of the directory
     */
    public function getEmptyDirectory( $working_directory, $relative_path, $name ){
        $directory = new Directory();
        $directory->setWorkingDirectory( $working_directory );
        $directory->setRelativePath( $relative_path );
        $directory->setDirectoryName( $name );

        return $directory;
    }
}