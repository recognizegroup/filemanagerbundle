<?php
namespace Recognize\FilemanagerBundle\Controller;

use Recognize\FilemanagerBundle\Response\FilemanagerResponseBuilder;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FilemanagerController extends Controller {

    /**
     * @return FilemanagerService
     */
    protected function getFilemanager(){
        return $this->get('recognize.file_manager');
    }

    public function read(Request $request){
        $filemanager = $this->getFilemanager();
        $contents = $filemanager->getDirectoryContents( $request->get('directory') );


        $builder = new FilemanagerResponseBuilder();
        $builder->addFiles( $contents );
        return $builder->build();
    }

    public function search(Request $request){
        $filemanager = $this->getFilemanager();

        $files = $filemanager->searchDirectoryContents($request->get('directory'), "/" . $request->get('q') . "/" );
        $builder = new FilemanagerResponseBuilder();
        $builder->addFiles( $files );
        return $builder->build();
    }


    public function create(Request $request) {
        $filemanager = $this->getFilemanager();
        $filemanager->setWorkingDirectory( $request->get('filemanager_directory') );

        

        $builder = new FilemanagerResponseBuilder();
        $builder->fail( json_encode( $request->files ), 500);
        var_dump( $request );
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
