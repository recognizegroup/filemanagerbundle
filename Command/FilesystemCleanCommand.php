<?php
namespace Recognize\FilemanagerBundle\Command;

use Recognize\FilemanagerBundle\Service\FiledataSynchronizer;
use Recognize\FilemanagerBundle\Utils\PathUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FilesystemCleanCommand extends Command implements ContainerAwareInterface {

    private $container = null;

    protected function configure() {
        $this
            ->setName('filemanager:filesystem:clean')
            ->setDescription('Clean up the filesystem from files and directories that aren\'t indexed in the database')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output) {

        /** @var FileDataSynchronizer $synchronizer */
        $synchronizer = $this->container->get("recognize.filedata_synchronizer");

        $filesystem = new Filesystem();
        $directoryrep =  $this->container->get("recognize.filemanager_directory_repository");
        $filereferencerep =  $this->container->get("recognize.filemanager_file_repository");

        // Get all the possible working directories
        $config = $this->container->getParameter('recognize_filemanager.config');
        $directories = array();
        $files = array();

        // Loop through all the available working directories
        if( is_array($config['directories']) ){
            $working_directories = array_values( $config['directories'] );
            for( $i = 0, $length = count( $working_directories ); $i < $length; $i++ ){

                $finder = new Finder();
                $finder->in($working_directories[$i]);
                foreach( $finder as $file ){

                    /** @var SplFileInfo $file */
                    if( $file->isFile() ){
                        if( $filereferencerep->findOneBy(
                                array('working_directory' => PathUtils::addTrailingSlash( $working_directories[$i] ),
                                    'relative_path' => $this->getRelativePathForSplFileinfo(
                                        $working_directories[$i], $file),
                                    'filename' => $file->getFilename()))
                            == null){
                            $files[] = $file->__toString();

                            if( OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity() ) {
                                $output->writeLn($file->__toString());
                            }
                        }
                    } else if( $file->isDir() ){
                        if( $synchronizer->canDirectoryBeDeletedFromTheFilesystem( $working_directories[$i], $file->__toString() )) {
                            $directories[] = $file->__toString();

                            if( OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity() ) {
                                $output->writeLn( $file->__toString() );
                            }
                        }
                    }
                }
            }
        }

        $output->writeLn( count( $files ) . " unindexed files and " . count( $directories ) . " unindexed directories found\n" );
        if( count( $files ) + count( $directories) > 0 ){
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to delete these files and directories from the filesystem? (Y/N):');
            if (!$helper->ask($input, $output, $question)) {
                return;
            } else {
                for( $i = 0, $length = count( $files ); $i < $length; $i++ ){
                    $file = $files[$i];
                    $output->write("Deleting file '" . $file . "' from the filesystem...");
                    try {
                        $filesystem->remove($file);
                        $output->write("<fg=white;bg=green>DONE</fg=white;bg=green>\n");
                    } catch( \RuntimeException $e ){
                        $output->write("<fg=white;bg=red>FAILED: " . $e->getMessage() . "</fg=white;bg=red>\n");
                    }
                }

                for( $i = 0, $length = count( $directories ); $i < $length; $i++ ){
                    $directory = $directories[$i];
                    $output->write("Deleting directory '" . $directory . "' from the filesystem...");
                    try {
                        $filesystem->remove( $directory );
                        $output->write("<fg=white;bg=green>DONE</fg=white;bg=green>\n");
                    } catch( \RuntimeException $e ){
                        $output->write("<fg=white;bg=red>FAILED: " . $e->getMessage() . "</fg=white;bg=red>\n");
                    }
                }

            }
        } else {
            $output->writeLn("<fg=black;bg=green> No unindexed files found </fg=black;bg=green>\n" );
        }
    }


    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null){
        $this->container = $container;
    }

    /**
     * @param $working_directory
     * @param $file
     * @return mixed
     */
    protected function getRelativePathForSplFileinfo( $working_directory, $file ){
        return PathUtils::removeFirstSlash( PathUtils::moveUpPath( PathUtils::stripWorkingDirectoryFromAbsolutePath(
            $working_directory, $file->__toString() ) ) );
    }
}