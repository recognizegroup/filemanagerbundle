<?php
namespace Recognize\FilemanagerBundle\Command;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Security\FileSecurityContext;
use Recognize\FilemanagerBundle\Service\FiledataSynchronizer;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DatabaseCleanCommand extends Command implements ContainerAwareInterface {

    private $container = null;

    protected function configure() {
        $this
            ->setName('filemanager:database:clean')
            ->setDescription('Clean up the database from records that are no longer have proper references to the filesystem')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        /** @var FileDataSynchronizer $synchronizer */
        $synchronizer = $this->container->get("recognize.filedata_synchronizer");

        $filesystem = new Filesystem();
        $directoryrep =  $this->container->get("recognize.filemanager_directory_repository");
        $filereferencerep =  $this->container->get("recognize.filemanager_file_repository");


        /** @var Directory[] $unlinked_files */
        $unlinked_directories = array();
        $directories = $directoryrep->findAll();
        for( $i = 0, $length = count($directories); $i < $length; $i++ ){
            $path = $directories[$i]->getAbsolutePath();
            if( $filesystem->exists( $path ) == false ){
                $unlinked_directories[] = $directories[$i];

                if( OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity() ) {
                    $output->writeln($path);
                }
            }
        }

        /** @var FileReference[] $unlinked_files */
        $unlinked_files = array();
        $references = $filereferencerep->findAll();
        for( $i = 0, $length = count($references); $i < $length; $i++ ){
            $path = $references[$i]->getAbsolutePath();
            if( $filesystem->exists( $path ) == false ){
                $unlinked_files[] = $references[$i];

                if( OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity() ) {
                    $output->writeln($path);
                }
            }
        }

        $unlinked_count = ( count($unlinked_files) + count( $unlinked_directories ) );
        if( $unlinked_count == 0 ) {
            $output->writeln("<fg=black;bg=green> No unsynchronized files found! </fg=black;bg=green>");
            return;
        } else {
            $output->writeln($unlinked_count . " unsynchronized files found\n");
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Really delete these references from the database? (Y/N): ');

        if (!$helper->ask($input, $output, $question)) {
            return;
        } else {
            for( $i = 0, $length = count( $unlinked_files ); $i < $length; $i++ ) {
                $file = $unlinked_files[$i];
                if( OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity() ) {
                    $output->writeLn("<fg=white;bg=red>Deleting file reference <fg=white;bg=red;options=bold>'" . $file->getAbsolutePath() . "'</fg=white;bg=red;options=bold> with id " . $file->getId() . " ...</fg=white;bg=red>");
                }
                $synchronizer->deleteFileReference( $file );
            }

            for( $i = 0, $length = count( $unlinked_directories ); $i < $length; $i++ ) {
                $directory = $unlinked_directories[$i];
                if( OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity() ) {
                    $output->writeLn("<fg=white;bg=red>Deleting directory <fg=white;bg=red;options=bold>'" . $directory->getAbsolutePath() . "'</fg=white;bg=red;options=bold> with id " . $directory->getId() . " and its database children...</fg=white;bg=red>");
                }
                $synchronizer->deleteDirectory( $directory );
            }
        }
    }


    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }
}