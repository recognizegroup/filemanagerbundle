<?php
namespace Recognize\FilemanagerBundle\Tests\Service;

use Doctrine\ORM\EntityManager;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Response\FileChanges;
use Recognize\FilemanagerBundle\Service\FiledataSynchronizer;
use Recognize\FilemanagerBundle\Tests\MockSplFileInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

class FiledataSynchronizerTest extends KernelTestCase {

    /** @var FiledataSynchronizer */
    private $synchronizer;

    private $rootdir;

    private $dir_repository;
    private $file_repository;
    private $container;

    public function setUp(){
        self::bootKernel();
        $this->container = static::$kernel->getContainer();

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        $this->file_repository = $em->getRepository("RecognizeFilemanagerBundle:FileReference");
        $this->dir_repository = $em->getRepository("RecognizeFilemanagerBundle:Directory");
        $this->rootdir = $this->dir_repository->findOneBy( array(
            "working_directory" => "testroot/",
            "relative_path" => "",
            "name" => ""
            ));

        $this->synchronizer = new FiledataSynchronizer( $em, $this->dir_repository, $this->file_repository,
            $this->container->get("recognize.file_acl_manager") );
    }

    public function testGetExistingFile(){
        $file = $this->synchronizer->loadFileReference("testroot", "testfile.txt");

        $this->assertEquals( $this->getExpectedFileReference(), $file );
    }

    /**
     * Creates a directory structure
     *
     * Virtual filesystem state:
     * /test/workingdirectory
     */
    public function testCreateDirectory(){
        $dir = new MockSplFileInfo( "/test/workingdirectory", "", "" );
        $dir->setAsDir();
        $dir->setFilename("testdir");
        $filechanges = new FileChanges("create", $dir);

        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");
        $this->assertTrue( count( $this->dir_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "",
                    "name" => "testdir"
                )
            ) ) > 0 );
    }

    /**
     * @depends testCreateDirectory
     *
     * Virtual filesystem state:
     * /test/workingdirectory
     * /test/workingdirectory/testdir.txt
     */
    public function testCreateFile(){
        $file = new MockSplFileInfo( "/test/workingdirectory/testdir.txt", "", "" );
        $file->setFilename( "testdir.txt" );
        $file->setAsFile();
        $filechanges = new FileChanges("create", $file);

        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");
        $this->assertTrue( count( $this->file_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "",
                    "filename" => "testdir.txt"
                )
            ) ) > 0 );
    }

    /**
     * @depends testCreateFile
     *
     * Virtual filesystem state:
     * /test/workingdirectory
     * /test/workingdirectory/testdir.txt
     * /test/workingdirectory/example.txt
     */
    public function testCreateFileFromReference(){
        $this->synchronizer->loadFileReference("/test/workingdirectory/", "example.txt" );

        $this->assertTrue( count( $this->file_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "",
                    "filename" => "example.txt"
                )
            ) ) > 0 );
    }

    /**
     * @depends testCreateFile
     *
     * Virtual filesystem state:
     * /test/workingdirectory
     * /test/workingdirectory/testdir2.txt
     * /test/workingdirectory/example.txt
     */
    public function testRenameFile(){
        $file = new MockSplFileInfo( "/test/workingdirectory/testdir.txt", "", "" );
        $file->setFilename( "testdir.txt" );
        $file->setAsFile();
        $filechanges = new FileChanges("rename", $file);

        $updatedfile = new MockSplFileInfo( "/test/workingdirectory/testdir2.txt", "", "" );
        $updatedfile->setFilename( "testdir2.txt" );
        $updatedfile->setAsFile();

        $filechanges->setFileAfterChanges( $updatedfile );

        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");
        $this->assertTrue( count( $this->file_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "",
                    "filename" => "testdir2.txt"
                )
            ) ) > 0 );
    }

    /**
     * @depends testRenameFile
     *
     * Virtual filesystem state:
     * /test/workingdirectory/subfolder
     * /test/workingdirectory/subfolder/testdir2.txt
     * /test/workingdirectory/subfolder/example.txt
     */
    public function testMoveRootDirectory(){
        $file = new MockSplFileInfo( "/test/workingdirectory/testdir.txt", "", "" );
        $file->setFilename( "" );
        $file->setAsDir();
        $filechanges = new FileChanges("move", $file);

        $updatedfile = new MockSplFileInfo( "/test/workingdirectory/subfolder", "", "subfolder" );
        $updatedfile->setFilename( "subfolder" );
        $updatedfile->setAsDir();

        $filechanges->setFileAfterChanges( $updatedfile );

        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");

        $actualfile = "not_updated";
        $actualdir = "not_updated";
        if( count( $this->dir_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "",
                    "name" => "subfolder"
                )
            ) ) > 0 ){
            $actualdir = "directory_updated";
        }

        if( count( $this->file_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "subfolder/",
                    "filename" => "testdir2.txt"
                )
            ) ) > 0 ){
            $actualfile = "file_updated";
        }

        $this->assertEquals( "directory_updated", $actualdir);
        $this->assertEquals( "file_updated", $actualfile);
    }

    /**
     * @depends testMoveRootDirectory
     *
     * Virtual filesystem state:
     * /test/workingdirectory/extrafolder2
     * /test/workingdirectory/extrafolder2/deepfolder
     * /test/workingdirectory/extrafolder2/deepfolder/deepestfolder
     */
    public function testMoveParentDirectory(){

        // Create the filestructure
        $file = new MockSplFileInfo( "/test/workingdirectory/", "", "" );
        $file->setFilename( "extrafolder" );
        $file->setAsDir();
        $filechanges = new FileChanges("create", $file);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");

        $deepfolder = new MockSplFileInfo( "/test/workingdirectory/extrafolder/deepfolder", "extrafolder/", "" );
        $deepfolder->setFilename( "deepfolder" );
        $deepfolder->setAsDir();
        $filechanges = new FileChanges("create", $deepfolder);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");

        $deepestfolder = new MockSplFileInfo( "/test/workingdirectory/extrafolder/deepfolder/deepestfolder",
            "extrafolder/deepfolder", "" );
        $deepestfolder->setFilename( "deepestfolder" );
        $deepestfolder->setAsDir();
        $filechanges = new FileChanges("create", $deepestfolder);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");

        $deepestfile = new MockSplFileInfo( "/test/workingdirectory/extrafolder/deepfolder/deepestfolder.txt",
            "extrafolder/deepfolder", "" );
        $deepestfile->setFilename( "deepestfolder.txt" );
        $deepestfile->setAsFile();
        $filechanges = new FileChanges("create", $deepestfile);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");


        // Change the extrafolder
        $extrafile = new MockSplFileInfo( "/test/workingdirectory/", "", "" );
        $extrafile->setFilename( "extrafolder" );
        $extrafile->setAsDir();

        $updateddir = new MockSplFileInfo("/test/workingdirectory/", "", "extrafolder2" );
        $updateddir->setFilename("extrafolder2");
        $updateddir->setAsDir();
        $newfilechanges = new FileChanges("move", $extrafile);
        $newfilechanges->setFileAfterChanges( $updateddir );
        $this->synchronizer->synchronize($newfilechanges, "/test/workingdirectory/");
        $manager = $this->container->get('doctrine')->getManager();

        $childdir = "not_updated";
        $directory = $this->dir_repository->findOneBy(
            array(
                "relative_path" => "extrafolder2/",
                "name" => "deepfolder",
            )
        );

        if( count( $directory ) > 0 ){
            $childdir = "child_directory_updated";
        }

        $nestedchilddir = "not_updated";

        $childdirectory = $this->dir_repository->findOneBy(
            array(
                "relative_path" => "extrafolder2/deepfolder/",
                "name" => "deepestfolder",
            )
        );


        if( count( $childdirectory ) > 0 ){
            $nestedchilddir = "nested_child_directory_updated";
        }

        $changednestedfile = "not_updated";
        $nestedfile = $this->file_repository->findOneBy(
            array(
                "relative_path" => "extrafolder2/deepfolder/",
                "filename" => "deepestfile.txt",
            )
        );

        if( count( $changednestedfile ) > 0 ){
            $changednestedfile = "Nested file updated";
        }

        $this->assertEquals( "child_directory_updated", $childdir);
        $this->assertEquals( "nested_child_directory_updated", $nestedchilddir);
        $this->assertEquals( "Nested file updated", $changednestedfile);
    }


    /**
     * @depends testMoveParentDirectory
     *
     * Virtual filesystem state:
     * /test/workingdirectory/subfolder
     * /test/workingdirectory/subfolder/example.txt
     */
    public function testDeleteFile(){
        $file = new MockSplFileInfo( "/test/workingdirectory/subfolder/testdir.txt", "subfolder/", "" );
        $file->setFilename( "testdir2.txt" );
        $file->setAsFile();
        $filechanges = new FileChanges("delete", $file);

        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");

        $actualfile = "not_deleted";

        if( count( $this->file_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "subfolder/",
                    "filename" => "testdir2.txt"
                )
            ) ) == 0 ){
            $actualfile = "file_deleted";
        }

        $this->assertEquals( "file_deleted", $actualfile);
    }

    /**
     * @depends testDeleteFile
     *
     * Virtual filesystem state:
     */
    public function testDeleteDirectory(){
        $file = new MockSplFileInfo( "/test/workingdirectory/subfolder", "", "subfolder" );
        $file->setFilename( "subfolder" );
        $file->setAsDir();
        $filechanges = new FileChanges("delete", $file);

        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory/");

        $actualdir = "not_deleted";
        if( count( $this->dir_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "",
                    "name" => "subfolder"
                )
            ) ) == 0 ){
            $actualdir   = "directory_deleted";
        }

        $this->assertEquals( "directory_deleted", $actualdir);

        $actualfile = "not_deleted";
        if( count( $this->file_repository->findOneBy(
                array(
                    "working_directory" => "/test/workingdirectory/",
                    "relative_path" => "subfolder/",
                    "filename" => "example.txt"
                )
            ) ) == 0 ){
            $actualfile = "child_file_deleted";
        }

        $this->assertEquals( "child_file_deleted", $actualfile);

    }

    /**
     * @depends testDeleteDirectory
     *
     * Virtual filesystem before deleting
     * /test/workingdirectory/testdir
     * /test/workingdirectory/testdir/file.txt
     * /test/workingdirectory/testdir/test/
     * /test/workingdirectory/testdir/test/file.txt
     */
    public function testDeleteDirectoryMethod(){
        $dir = new MockSplFileInfo( "/test/workingdirectory", "", "" );
        $dir->setAsDir();
        $dir->setFilename("testdir");
        $filechanges = new FileChanges("create", $dir);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");

        $dir = new MockSplFileInfo( "/test/workingdirectory/testdir", "", "" );
        $dir->setAsDir();
        $dir->setFilename("test");
        $filechanges = new FileChanges("create", $dir);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");

        $file = new MockSplFileInfo( "/test/workingdirectory/testdir/file.txt", "testdir/", "" );
        $file->setFilename( "file.txt" );
        $file->setAsFile();
        $filechanges = new FileChanges("create", $file);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");

        $file = new MockSplFileInfo( "/test/workingdirectory/testdir/test/file.txt", "testdir/test/", "" );
        $file->setFilename( "file.txt" );
        $file->setAsFile();
        $filechanges = new FileChanges("create", $file);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");


        // EXECUTE THE METHOD
        $directory = $this->dir_repository->findOneBy( array(
            "working_directory" => "/test/workingdirectory/",
            "relative_path" => "",
            "name" => "testdir"
        ));
        $this->synchronizer->deleteDirectory( $directory );

        // TEST THE OUTCOME
        $deleteddirectory = $this->dir_repository->findOneBy( array(
            "working_directory" => "/test/workingdirectory/",
            "relative_path" => "",
            "name" => "testdir"
        ));

        $directorydeleted = "Directory not deleted";
        if( $deleteddirectory == null ){
            $directorydeleted = "Directory deleted!";
        }

        $deletedfile = $this->file_repository->findOneBy( array(
            "working_directory" => "/test/workingdirectory/",
            "relative_path" => "testdir/",
            "filename" => "file.txt"
        ));


        $filedeleted = "Child file not deleted";
        if( $deletedfile == null ){
            $filedeleted = "Child file deleted!";
        }

        $this->assertEquals( $directorydeleted, "Directory deleted!" );
        $this->assertEquals( $filedeleted, "Child file deleted!" );
    }

    /**
     * @depends testDeleteDirectoryMethod
     *
     * Virtual filesystem state:
     */
    public function testDeleteFileReferenceMethod(){
        $file = new MockSplFileInfo( "/test/workingdirectory/testdir.txt", "", "" );
        $file->setFilename( "testdir.txt" );
        $file->setAsFile();
        $filechanges = new FileChanges("create", $file);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");

        $reference = $this->file_repository->findOneBy( array(
            "working_directory" => "/test/workingdirectory/",
            "relative_path" => "",
            "filename" => "testdir.txt"
        ));


        $this->synchronizer->deleteFileReference( $reference );
        $deletedreference = $this->file_repository->findOneBy( array(
            "working_directory" => "/test/workingdirectory/",
            "relative_path" => "",
            "filename" => "testdir.txt"
        ));

        $this->assertTrue( $deletedreference == null );

    }

    /**
     * @depends testDeleteFileReferenceMethod
     *
     * Virtual filesystem state:
     */
    public function testNonindexedDirectoryDeleteCheck(){
        $this->assertTrue( $this->synchronizer->canDirectoryBeDeletedFromTheFilesystem( "/test/workingdirectory/",
            "/test/workingdirectory/test" ) );
    }

    /**
     * @depends testNonindexedDirectoryDeleteCheck
     *
     * Virtual filesystem state:
     */
    public function testNonindexedDirectoryWithIndexedFilesDeleteCheck(){
        $file = new MockSplFileInfo( "/test/workingdirectory/file.txt", "", "" );
        $file->setFilename( "fil.txt" );
        $file->setAsFile();
        $filechanges = new FileChanges("create", $file);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");

        $this->assertFalse( $this->synchronizer->canDirectoryBeDeletedFromTheFilesystem( "/test/workingdirectory/",
            "/test/workingdirectory/" ) );

        $filechanges = new FileChanges("delete", $file);
        $this->synchronizer->synchronize( $filechanges, "/test/workingdirectory/" );
    }

    /**
     * @depends testNonindexedDirectoryDeleteCheck
     *
     * Virtual filesystem state:
     */
    public function testNonindexedDirectoryWithIndexedDirectoriesDeleteCheck(){
        $file = new MockSplFileInfo( "/test/workingdirectory/test", "", "" );
        $file->setFilename( "test" );
        $file->setAsDir();
        $filechanges = new FileChanges("create", $file);
        $this->synchronizer->synchronize($filechanges, "/test/workingdirectory");

        $this->assertFalse( $this->synchronizer->canDirectoryBeDeletedFromTheFilesystem( "/test/workingdirectory/",
            "/test/workingdirectory/" ) );

        $filechanges = new FileChanges("delete", $file);
        $this->synchronizer->synchronize( $filechanges, "/test/workingdirectory/" );
    }

    public function getExpectedFileReference(){
        $file = new FileReference();
        $file->setId( 1 );
        $file->setParentDirectory( $this->rootdir );
        $file->setDirectoryObject( null );
        $file->setFileName("testfile.txt");
        $file->setMimetype("text/plain");

        return $file;
    }

}