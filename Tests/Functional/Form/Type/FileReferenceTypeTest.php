<?php
namespace Recognize\FilemanagerBundle\Tests\Functional\Form\Type;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Form\Type\FileReferenceType;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Recognize\FilemanagerBundle\Tests\MockFiledataSynchronizer;
use Recognize\FilemanagerBundle\Tests\MockFileSecurityContext;
use Symfony\Component\Filesystem\Tests\FilesystemTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class FileReferenceTypeTest extends FilesystemTestCase {

    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    protected function setUp(){
        parent::setUp();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();
    }

    protected function getExtensions(){
        return array();
    }


    public function testCompile(){
        $form = $this->factory->create( $this->getFileReferenceType() );
    }

    public function testHasCorrectFormPresets(){
        $builder = $this->factory->createBuilder("form")
            ->add( "file", $this->getFileReferenceType(), array( "required" => false ) );
        $form = $builder->getForm();
        $form->submit( array( "file" => null ));

        $view = $form->createView();
        $this->assertTrue( isset( $view->vars['multipart'] ) );
    }


    public function testSubmitEmptyValue(){
        $builder = $this->factory->createBuilder("form")
            ->add( "file", $this->getFileReferenceType(), array( "required" => false ) );
        $form = $builder->getForm();

        $form->submit( array( "file" => null ));

        $errors = $form->getErrors();
        $this->assertTrue( $errors->count() == 0 );

        $submitteddata = $form->getData();
        $this->assertEquals( null, $submitteddata['file'] );
    }

    public function testSubmitExistingFile(){
        $filereference = new FileReference();
        $filereference->setId( 1 );

        $builder = $this->factory->createBuilder("form")
            ->add( "file", $this->getFileReferenceType( $filereference ), array( "required" => false ) );
        $form = $builder->getForm();

        $form->submit( array( "file" => "derp.txt" ));

        $errors = $form->getErrors();
        $this->assertTrue( $errors->count() == 0 );
        $submitteddata = $form->getData();

        $expectedfile = new FileReference();
        $expectedfile->setId( 1 );

        $this->assertEquals($expectedfile, $submitteddata['file'] );
    }

    public function testSubmitUploadedFile(){
        $filereference = new FileReference();
        $testdir = new Directory();
        $testdir->setId( 1000 );
        $testdir->setWorkingDirectory( $this->workspace );
        $testdir->setRelativePath("");
        $testdir->setDirectoryName("");
        $filereference->setParentDirectory( $testdir );
        $filereference->setId( 1 );

        $builder = $this->factory->createBuilder("form")
            ->add( "file", $this->getFileReferenceType( $filereference ), array( "required" => false ) );
        $form = $builder->getForm();

        $uploadedfile = new UploadedFile( $this->workspace . "/derp.txt", "derp2.txt", "text/plain", 0, null, true );
        $form->submit( array( "file" => $uploadedfile ));
        $submitteddata = $form->getData();

        $expectedfile = new FileReference();
        $expectedfile->setParentDirectory( $testdir );
        $expectedfile->setId( 1 );

        $this->assertEquals($expectedfile, $submitteddata['file'] );
    }

    public function testCorrectRenderingOfView(){
        $filereference = new FileReference();
        $testdir = new Directory();
        $testdir->setId( 1000 );
        $testdir->setWorkingDirectory( $this->workspace );
        $testdir->setRelativePath("");
        $testdir->setDirectoryName("");
        $filereference->setParentDirectory( $testdir );
        $filereference->setId( 1 );
        $filereference->setFileName("derp2.txt");

        $builder = $this->factory->createBuilder("form")
            ->add( "file", $this->getFileReferenceType( $filereference ), array( "required" => false ) );
        $form = $builder->getForm();

        $uploadedfile = new UploadedFile( $this->workspace . "/derp.txt", "derp2.txt", "text/plain", 0, null, true );
        $form->submit( array( "file" => $uploadedfile ));
        $submitteddata = $form->getData();

        $view = $form->createView();
        $childit = $view->getIterator();

        $filerefview = $childit->offsetGet("file");
        $this->assertTrue( isset( $filerefview->vars['preview'] ) );
        $this->assertEquals( "derp2.txt", $filerefview->vars['value'] );
    }



    // ------------------------------- UTILS

    /**
     * Get an initialized filereference type
     *
     * @return FileReferenceType
     */
    protected function getFileReferenceType( $filereference = null ){
        // Create existing file
        file_put_contents($this->workspace . "/derp.txt", "testcontents");

        $filemanagerservice = new FilemanagerService( array("directories" => array( "default" => $this->workspace ) ),
            new MockFileSecurityContext(), new MockFiledataSynchronizer() );
        $datasynchroniser = new MockFiledataSynchronizer( $filereference );


        return new FileReferenceType( $filemanagerservice, $datasynchroniser );
    }

}