<?php
namespace Recognize\FilemanagerBundle\Entity;

/**
 * Class Directory
 * @package Recognize\FilemanagerBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Recognize\FilemanagerBundle\Repository\DirectoryRepository")
 * @ORM\Table(name="recognize_filemanager_directory")
 */

class Directory {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=11)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     */
    protected $parent_id;


    protected $parent = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $working_directory;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $relative_path;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    public function setParentDirectory( Directory $parent){
        $this->parent = $parent;
        $this->parent_id = $parent->getId();
    }

    public function setParentId( $id = 0 ){
        $this->parent_id = $id;
    }

    public function setId( $id ){
        $this->id = $id;
    }

    /**
     * Set the absolute path from the filesystems root to the common working directory
     * for directories in the database
     *
     * @param $working_directory
     */
    public function setWorkingDirectory( $working_directory ){
        $this->working_directory = $working_directory;
    }

    /**
     * Set the relative path from the working directory to the location of the directory
     *
     * @param $path
     */
    public function setRelativePath( $path ){
        $this->relative_path = $path;
    }

    /**
     * Set the filename of the directory
     *
     * @param $name
     */
    public function setDirectoryName( $name ){
        $this->name = $name;
    }

    public function getId(){
        return $this->id;
    }

    public function getParentid(){
        return $this->parent_id;
    }

    public function getDirectoryName(){
        return $this->name;
    }

    public function getWorkingDirectory(){
        return $this->working_directory;
    }

    public function getRelativePath(){
        return $this->relative_path;
    }

    public function getAbsolutePath(){
        return $this->working_directory . DIRECTORY_SEPARATOR . $this->relative_path;
    }

    /**
     * Returns the parent directory or NULL if nothing was found
     *
     * @return null
     */
    public function getParentDirectory(){
        return $this->parent;
    }

}