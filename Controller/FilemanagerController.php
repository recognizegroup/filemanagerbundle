<?php
namespace Recognize\FilemanagerBundle\Controller;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Response\FilemanagerResponseBuilder;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
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
        $contents = $filemanager->getDirectoryContents($request->get('directory'));

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

        $files = $filemanager->searchDirectoryContents($request->get('directory'), "/" . preg_quote( strtolower( $request->get('q') ), "/" ) . "/" );
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

        // Attempt to find a parameter that ends with filemanager_upload
        // This is to allow multiple filemanager upload inputfields in a single document
        $file = false;
        $files = $request->files->all();
        $parameternames = array_keys( $files );
        for( $i = 0, $length = count( $parameternames ); $i < $length; $i++ ){
            if( preg_match("/filemanager_upload$/", $parameternames[$i]) ){
                $file = $files[ $parameternames[$i] ];
                break;
            }
        }

        if( $file !== false && $request->request->has('filemanager_directory') ){
            $filemanager->goToDeeperDirectory( $request->get('filemanager_directory') );

            /** @var UploadedFile $file */
            try {
                $changes = $filemanager->saveUploadedFile($file, $file->getClientOriginalName());
                $builder->addChange($changes);
            } catch( \Exception $e ){
                $builder->fail( $e->getMessage(), 400 );
            }

        } else if( $request->request->has('directory_name') && $request->request->has('filemanager_directory') ) {

            $directoryname = $request->request->get('directory_name');
            $filemanager->goToDeeperDirectory( $request->get('filemanager_directory') );

            try {
                $changes = $filemanager->createDirectory($directoryname);
                $builder->addChange( $changes );
            } catch( \Exception $e ){
                $builder->fail( $e->getMessage(), 400 );
            }

        } else {

            $builder->fail( "Invalid request ");
        }

        return $builder->build();
    }

    /**
     * Move a file or a directory
     *
     * @param Request $request
     */
    public function move(Request $request) {
        $filemanager = $this->getFilemanager();
        $builder = new FilemanagerResponseBuilder();

        if( $request->request->has('filemanager_filepath')
            && $request->request->has('filemanager_newdirectory') ){

            $oldfilepath = $request->get('filemanager_filepath');
            $newdirectory = $request->get('filemanager_newdirectory');
            $changes = $filemanager->move( $oldfilepath, $newdirectory );

            $builder->addChange( $changes );

        } else {
            $builder->fail( "Invalid request");
        }

        return $builder->build();
    }

    /**
     * Rename a file or a directory
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rename(Request $request) {
        $filemanager = $this->getFilemanager();
        $builder = new FilemanagerResponseBuilder();

        if( $request->request->has('filemanager_filename')
            && $request->request->has('filemanager_newfilename')
            && $request->request->has('filemanager_directory') ){

            $filemanager->goToDeeperDirectory( $request->get('filemanager_directory') );

            $oldfilename = $request->get('filemanager_filename');
            $newfilename = $request->get('filemanager_newfilename');
            $changes = $filemanager->rename( $oldfilename, $newfilename );

            $builder->addChange( $changes );

        } else {
            $builder->fail( "Invalid request");
        }

        return $builder->build();
    }

    /**
     * Delete a file or a directory including its contents
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request) {
        $filemanager = $this->getFilemanager();
        $builder = new FilemanagerResponseBuilder();

        if( $request->request->has('filemanager_directory') && $request->request->has('filemanager_filename') ){
            $filemanager->goToDeeperDirectory( $request->request->get('filemanager_directory') );

            $filename = $request->request->get('filemanager_filename');

            $changes = $filemanager->delete( $filename );
            $builder->addChange( $changes );

        } else {
            $builder->fail( "Invalid request");
        }

        return $builder->build();
    }


    /**
     * Preview a file
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function preview(Request $request) {
        $filemanager = $this->getFilemanager();
        return $filemanager->getLiveFilePreview( $request->query->get('filemanager_path') );
    }

    /**
     * Download a file
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Request $request) {
        $filemanager = $this->getFilemanager();
        return $filemanager->downloadFile( $request->query->get('filemanager_path') );
    }

}
