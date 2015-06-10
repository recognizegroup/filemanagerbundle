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

        $builder = new FilemanagerResponseBuilder();
        $builder->attemptRead( function() use ($filemanager, $request) {
            return $filemanager->getDirectoryContents($request->get('directory'));
        });
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

        $builder = new FilemanagerResponseBuilder();
        $builder->attemptRead( function() use ($filemanager, $request) {
            return $filemanager->searchDirectoryContents($request->get('directory'), "/" . preg_quote( strtolower( $request->get('q') ), "/" ) . "/" );
        }, $this->get('translator'));

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
            $builder->attemptChange( function() use ($filemanager, $request, $file) {
                $filemanager->goToDeeperDirectory($request->get('filemanager_directory'));

                /** @var UploadedFile $file */
                return $filemanager->saveUploadedFile($file, $file->getClientOriginalName());
            }, $this->get('translator'));

        } else if( $request->request->has('directory_name') && $request->request->has('filemanager_directory') ) {

            $builder->attemptChange( function() use ($filemanager, $request) {
                $directoryname = $request->request->get('directory_name');
                $filemanager->goToDeeperDirectory( $request->get('filemanager_directory') );

                return $filemanager->createDirectory($directoryname);
            }, $this->get('translator'));

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

            $builder->attemptChange( function() use ($filemanager, $request) {
                $oldfilepath = $request->get('filemanager_filepath');
                $newdirectory = $request->get('filemanager_newdirectory');

                return $filemanager->move($oldfilepath, $newdirectory);
            }, $this->get('translator'));


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

            $builder->attemptChange( function() use ($filemanager, $request){
                $filemanager->goToDeeperDirectory( $request->get('filemanager_directory') );

                $oldfilename = $request->get('filemanager_filename');
                $newfilename = $request->get('filemanager_newfilename');

                return $filemanager->rename( $oldfilename, $newfilename );
            }, $this->get('translator'));

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
            $builder->attemptChange( function() use ($filemanager, $request) {
                $filemanager->goToDeeperDirectory($request->request->get('filemanager_directory'));

                $filename = $request->request->get('filemanager_filename');
                return $filemanager->delete($filename);
            }, $this->get('translator'));

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
