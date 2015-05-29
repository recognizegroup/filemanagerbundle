<?php
namespace Recognize\FilemanagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Recognize\FilemanagerBundle\Utils\PathUtils;


/**
 * Class FileReference
 * @package Recognize\FilemanagerBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Recognize\FilemanagerBundle\Repository\FileRepository")
 * @ORM\Table(name="recognize_filemanager_file")
 */

class FileReference {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=11)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", length=11, nullable=false)
     */
    protected $directory_id;


    /**
     * @var Directory
     */
    protected $directory;

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
    protected $filename;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $locale;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $mimetype;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $preview_url;

    public function setParentDirectory( Directory $directory){
        $this->directory_id = $directory->getId();
        $this->directory = $directory;

        $this->working_directory = $directory->getWorkingDirectory();
        $this->relative_path = PathUtils::addTrailingSlash( PathUtils::removeFirstSlash(
            $directory->getRelativePath() . $directory->getDirectoryName() ) );
    }

    public function setId( $id ){
        $this->id = $id;
    }

    public function setFileName( $name ){
        $this->filename = $name;
    }

    public function setMimetype( $mimetype ){
        $this->mimetype = $mimetype;
    }

    public function setPreviewUrl( $preview_url ){
        $this->preview_url = $preview_url;
    }

    public function setLocale( $locale ){
        $this->locale = $locale;
    }


    public function getId(){
        return $this->id;
    }

    public function getFilename(){
        return $this->filename;
    }

    public function getLocale(){
        return $this->locale;
    }

    public function getMimetype(){
        return $this->mimetype;
    }

    public function getPreviewUrl(){
        return $this->preview_url;
    }

    public function getWorkingDirectory(){
        return $this->working_directory;
    }

    public function getRelativePath(){
        return $this->relative_path;
    }

    public function getAbsolutePath(){
        return $this->working_directory . $this->relative_path . $this->filename;
    }

    public function getParentDirectory(){
        return $this->directory;
    }

}