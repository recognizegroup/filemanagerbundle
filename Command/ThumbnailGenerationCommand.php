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

class ThumbnailGenerationCommand extends Command implements ContainerAwareInterface {

    private $container = null;

    protected function configure() {
        $this
            ->setName('filemanager:thumbnails:generate')
            ->setDescription('Generate thumbnails for all the image files that are saved in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        /** @var FileDataSynchronizer $synchronizer */
        $synchronizer = $this->container->get("recognize.filedata_synchronizer");
        $em = $this->container->get('doctrine')->getManager();

        $fs = new FileSystem();

        $output->writeln("Generating thumbnails...");

        $files = $synchronizer->getAllImageFiles();

        // Allow previews of PDFs if imagick is installed
        if( extension_loaded('imagick') ){
            $pdfs = $synchronizer->getAllPDFFiles();
            for( $i = 0, $length = count( $pdfs); $i < $length; $i++ ){
                $files[] = $pdfs[$i];
            }
        }

        for( $i = 0, $length = count( $files ); $i < $length; $i++ ){
            $file = $files[$i];
            if( $fs->exists( $file->getAbsolutePath() ) && $file->getPreviewUrl() == "" || $fs->exists( $file->getPreviewUrl() ) == false ){
                $output->write("Generating thumbnail for " . $files[$i]->getAbsolutePath() . " - " );

                $file = $synchronizer->generateThumbnail( $file );
                if( $file->getPreviewUrl() !== ""){
                    $output->write( $file->getPreviewUrl() . "\n" );
                    $em->persist( $file );
                    $em->flush();

                } else {
                    $output->write("<fg=white;bg=red>Could not generate thumbnail!</fg=white;bg=red> \n");
                }
            }
        }



        $output->writeln("DONE!");
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