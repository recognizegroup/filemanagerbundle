<?php
namespace Recognize\FilemanagerBundle\Controller;

use Recognize\FilemanagerBundle\Response\FilemanagerResponseBuilder;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class FilemanagerController extends Controller {

    /**
     * @return FilemanagerService
     */
    protected function getFilemanager(){
        return $this->get('recognize.file_manager');
    }

    /**
     * Read from the directory
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function read(Request $request){
        $filemanager = $this->getFilemanager();
        $contents = $filemanager->getDirectoryContents( $request->get('directory') );

        $builder = new FilemanagerResponseBuilder();
        $builder->addFiles( $contents );
        return $builder->build();
    }

    /**
     * Recursively search the directory
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function search(Request $request){
        $filemanager = $this->getFilemanager();

        $files = $filemanager->searchDirectoryContents($request->get('directory'), "/" . $request->get('q') . "/" );
        $builder = new FilemanagerResponseBuilder();
        $builder->addFiles( $files );
        return $builder->build();
    }


    /**
     * Upload a file or create a directory into the current directory
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function create(Request $request) {
        $filemanager = $this->getFilemanager();
        $builder = new FilemanagerResponseBuilder();

        if( $request->files->has('filemanager_upload') && $request->request->has('filemanager_directory') ){

            $filemanager->goToDeeperDirectory( $request->get('filemanager_directory') );

            /** @var UploadedFile $file */
            $file = $request->files->get( 'filemanager_upload' );
            $changes = $filemanager->saveUploadedFile( $file, $file->getClientOriginalName() );
            $builder->addChange( $changes );

        } else if( $request->request->has('directory_name') && $request->request->has('filemanager_directory') ) {

            $directoryname = $request->request->get('directory_name');
            $filemanager->goToDeeperDirectory( $request->get('filemanager_directory') );
            $changes = $filemanager->createDirectory( $directoryname );
            $builder->addChange( $changes );

        } else {
            $builder->fail( "Invalid request");
        }

        return $builder->build();
    }

    public function move(Request $request) {

    }

    public function rename(Request $request) {
        $filemanager = $this->getFilemanager();

        $oldfile = "";
        $newfile = "1";

        $changes = $filemanager->rename( $oldfile, $newfile );
    }

    public function delete(Request $request) {

    }

}
