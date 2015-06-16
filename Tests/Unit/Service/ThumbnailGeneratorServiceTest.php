<?php
namespace Recognize\FilemanagerBundle\Tests\Service;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Service\ThumbnailGeneratorService;
use Recognize\FilemanagerBundle\Tests\TestFixtures\TestJPEG;
use Recognize\FilemanagerBundle\Tests\TestFixtures\TestPNG;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Tests\FilesystemTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ThumbnailGeneratorServiceTest extends FilesystemTestCase {

    protected function getThumbnailGeneratorService(){
        return new ThumbnailGeneratorService( array("thumbnail_directory" => $this->workspace ) );
    }

    public function testNullReference(){
        $thumbnailgen = $this->getThumbnailGeneratorService();

        $this->assertEquals("", $thumbnailgen->generateThumbnailForFile(null) );
    }

    public function testNonimage(){
        $thumbnailgen = $this->getThumbnailGeneratorService();

        $fileref = new FileReference();
        $fileref->setFileName("notanimage.txt");
        $fileref->setMimetype("text/plain");

        $this->assertEquals("", $thumbnailgen->generateThumbnailForFile( $fileref ) );
    }

    public function testSvgGeneratesNoThumbnail(){
        $thumbnailgen = $this->getThumbnailGeneratorService();

        $fileref = new FileReference();
        $fileref->setFileName("notanimage.svg");
        $fileref->setMimetype("image/svg");

        $this->assertEquals("", $thumbnailgen->generateThumbnailForFile( $fileref ) );
    }

    public function testNonexistingImage(){
        $thumbnailgen = $this->getThumbnailGeneratorService();

        $fileref = new FileReference();
        $fileref->setFileName("image.png");
        $fileref->setMimetype("image/png");

        $this->assertEquals("", $thumbnailgen->generateThumbnailForFile( $fileref ) );
    }

    public function testExistingPNGImageGeneratesThumbnail(){
        $thumbnailgen = $this->getThumbnailGeneratorService();

        $testimage = new TestPNG();
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR .  'testing.png', $testimage->getContents() );

        $fileref = new FileReference();
        $fileref->setFileName("testing.png");
        $fileref->setMimetype("image/png");
        $dir = new Directory();
        $dir->setWorkingDirectory( $this->workspace . DIRECTORY_SEPARATOR );
        $fileref->setParentDirectory( $dir );

        $thumbnail = $thumbnailgen->generateThumbnailForFile( $fileref );
        $this->assertNotEquals( "", $thumbnail );

        $fs = new Filesystem();
        $this->assertTrue( $fs->exists( $thumbnail ) );

        list($newwidth, $newheight ) = getimagesize( $thumbnail );
        $this->assertEquals( $newwidth, 50);
        $this->assertEquals( $newheight, 50);
    }

    public function testExistingJPGImageGeneratesThumbnail(){
        $thumbnailgen = $this->getThumbnailGeneratorService();

        $testimage = new TestJPEG();
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR .  'testing.jpg', $testimage->getContents() );

        $fileref = new FileReference();
        $fileref->setFileName("testing.jpg");
        $fileref->setMimetype("image/jpg");
        $dir = new Directory();
        $dir->setWorkingDirectory( $this->workspace . DIRECTORY_SEPARATOR );
        $fileref->setParentDirectory( $dir );

        $thumbnail = $thumbnailgen->generateThumbnailForFile( $fileref );
        $this->assertNotEquals( "", $thumbnail );

        $fs = new Filesystem();
        $this->assertTrue( $fs->exists( $thumbnail ) );

        list($newwidth, $newheight ) = getimagesize( $thumbnail );
        $this->assertEquals( $newwidth, 50);
        $this->assertEquals( $newheight, 50);
    }


}