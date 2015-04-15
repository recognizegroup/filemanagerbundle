<?php

namespace Recognize\CMSBundle\Controller;

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
    }

    public function create(Request $request) {

    }

    public function download(Request $request) {

    }

    public function move(Request $request) {

    }

    public function rename(Request $request) {
        $filemanager = $this->getFilemanager();

        $oldfile = "";
        $newfile = "1";

        $filemanager->rename( $oldfile, $newfile );

    }

    public function delete(Request $request) {

    }

}
