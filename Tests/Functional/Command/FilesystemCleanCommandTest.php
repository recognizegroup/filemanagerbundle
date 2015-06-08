<?php
namespace Recognize\FilemanagerBundle\Tests\Command;

use Recognize\FilemanagerBundle\Command\FilesystemCleanCommand;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Recognize\FilemanagerBundle\Tests\MockFiledataSynchronizer;
use Recognize\FilemanagerBundle\Tests\MockFileSecurityContext;
use Recognize\FilemanagerBundle\Tests\TestUtils\KernelAndFilesystemTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Finder\Finder;

class FilesystemCleanCommandTest extends KernelAndFilesystemTestCase {

    private $application;

    public function setUp(){
        parent::setUp();
        static::bootKernel();

        $application = new Application( static::$kernel );
        $application->add( $this->getCommand() );
        $this->application = $application;
    }

    public function testCommandWithoutErrors() {
        $command = $this->application->find("filemanager:filesystem:clean");
        $command->setContainer( $this->getContainer() );
        $commandTester = new CommandTester($command);

        $question = $command->getHelper('question');
        $question->setInputStream($this->getInputStream("N\\n"));
        $commandTester->execute(array('command' => $command->getName()));
        $output = $commandTester->getDisplay();

        $this->assertTrue( strpos($output, "[InvalidArgumentException]") === false );
    }

    public function testVerboseOutputIsLongerThanNonverboseOutput(){
        $this->fillTempDir();

        $command = $this->application->find("filemanager:filesystem:clean");
        $command->setContainer( $this->getContainer() );
        $commandTester = new CommandTester($command);

        $question = $command->getHelper('question');
        $question->setInputStream($this->getInputStream("N\\n"));
        $commandTester->execute(array('command' => $command->getName()));
        $output = $commandTester->getDisplay();
        $withoutverbosity = strlen($output);

        $command->setContainer( $this->getContainer() );
        $commandTester = new CommandTester($command);

        $question = $command->getHelper('question');
        $question->setInputStream($this->getInputStream("N\\n"));
        $commandTester->execute(array('command' => $command->getName()), array('verbosity' => OutputInterface::VERBOSITY_VERBOSE ));
        $output = $commandTester->getDisplay();
        $withverbosity = strlen($output);

        $this->assertTrue( $withverbosity > $withoutverbosity );
    }


    public function testActualDeletingWithoutIndexedFiles(){
        $this->fillTempDir();
        $finder = new Finder();
        $finder->in($this->workspace);
        $oldfiles = array();
        foreach( $finder as $file ){
            $oldfiles[] = $file;
        }


        $command = $this->application->find("filemanager:filesystem:clean");

        $command->setContainer( $this->getContainer() );
        $commandTester = new CommandTester($command);

        $question = $command->getHelper('question');
        $question->setInputStream($this->getInputStream("Y\\n"));
        $commandTester->execute(array('command' => $command->getName()), array('verbosity' => OutputInterface::VERBOSITY_VERBOSE ));
        $output = $commandTester->getDisplay();

        $files = array();
        foreach( $finder as $file ){
            $files[] = $file;
        }

        $this->assertEquals( count( $oldfiles ), 4 );
        $this->assertEquals( count( $files ), 0);
    }


    // ---------------------------------------------- UTIL METHODS

    protected function getContainer(){
        $container = new Container();
        $em = static::$kernel->getContainer()->get("doctrine")->getManager();
        $container->set("recognize.filedata_synchronizer", new MockFiledataSynchronizer() );
        $container->set("recognize.filemanager_directory_repository", $em->getRepository("RecognizeFilemanagerBundle:Directory") );
        $container->set("recognize.filemanager_file_repository", $em->getRepository("RecognizeFilemanagerBundle:FileReference") );
        $container->setParameter("recognize_filemanager.config", array("directories" => array("default" => $this->workspace)));
        $container->set("recognize.file_manager", new FilemanagerService(
            array("directories" => array("default" => $this->workspace) ),
            new MockFileSecurityContext(),
            new MockFiledataSynchronizer()
            ));
        return $container;
    }

    protected function fillTempDir(){
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "level2", 0777, true);
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "testing2" , 0777);
        file_put_contents( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "test.txt", "testcontents");
    }

    protected function getCommand(){
        $command = new FilesystemCleanCommand();

        $container = $this->getContainer();
        $command->setContainer( $container );

        return $command;
    }

    protected function getInputStream($input) {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}