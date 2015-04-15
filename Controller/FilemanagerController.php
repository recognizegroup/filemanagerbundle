<?php

namespace Recognize\CMSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FilemanagerController extends Controller {

    public function read(Request $request){
        $filemanager = $this->get('recognize.file_manager');
    }

    public function create(Request $request) {

    }

    public function download(Request $request) {

    }

    public function move(Request $request) {

    }

    public function rename(Request $request) {

    }

    public function delete(Request $request) {

    }

}
