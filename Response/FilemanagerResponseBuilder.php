<?php
namespace Recognize\FilemanagerBundle\Response;

use Recognize\FilemanagerBundle\Exception\ConflictException;
use Recognize\FilemanagerBundle\Exception\FileTooLargeException;
use Recognize\FilemanagerBundle\Repository\FileRepository;
use Recognize\FilemanagerBundle\Service\ThumbnailGeneratorService;
use Recognize\FilemanagerBundle\Utils\PathUtils;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class FilemanagerResponseBuilder {

    protected $config = array("status" => "success", "data" => array());

    protected $statuscode = 200;
    protected $error_message = "";

    protected $translation_function = null;
    protected $finfo = null;

    protected $preview_link = "";

    protected $thumbnail_strategy = ThumbnailGeneratorService::STRATEGY_INDEXED_ONLY;

    public function __construct( $preview_link = "/admin/fileapi/preview", $thumbnail_strategy = ThumbnailGeneratorService::STRATEGY_INDEXED_ONLY ){
        $this->finfo = finfo_open( FILEINFO_MIME_TYPE );
        $this->preview_link = $preview_link;
        $this->thumbnail_strategy = $thumbnail_strategy;
    }

    private $fileRepository;
    private $webdir;

    /**
     * Fail the response and return a detailed message
     *
     * @param string $error_message      The error message to display to the requester
     * @param int $statuscode         Optional: the statuscode to return
     *
     * @return FilemanagerResponseBuilder
     */
    public function fail( $error_message, $statuscode = 400 ){
        $this->statuscode = $statuscode;
        $this->error_message = $error_message;

        $this->config["status"] = "failed";
        return $this->add( "message", $error_message );
    }

    /**
     * Add multiple files to the response
     *
     * @param SplFileInfo[] $files
     */
    public function addFiles( array $files ){
        if( isset($this->config['data']['contents']) == false ){
            $this->config['data']['contents'] = array();
        }

        for( $i = 0, $length = count($files); $i < $length; $i++ ){
            $this->addFile( $files[$i] );
        }

        return $this;
    }

    /**
     * Add a single file to the response
     *
     * @param SplFileInfo $file
     */
    public function addFile( $file ){
        if( isset($this->config['data']['contents']) == false ){
            $this->config['data']['contents'] = array();
        }

        $this->config['data']['contents'][] = $this->transformFileToData( $file );
        return $this;
    }

    /**
     * Add a file change to the response
     *
     * @param FileChanges $filechange
     */
    public function addChange( $filechange ){
        if( isset($this->config['data']['changes']) == false ){
            $this->config['data']['changes'] = array();
        }

        $this->config['data']['changes'][] = $filechange->toArray();
        return $this;
    }


    /**
     * Attempt to make a change with the supplied function, catching all the exceptions to translate them
     *
     * @param $function
     */
    public function attemptChange( $function, TranslatorInterface $translator = null ){
        $this->attempt( "change", $function, $translator );
    }

    /**
     * Attempt to read a directory, catching all the exceptions to translate them
     *
     * @param $function
     */
    public function attemptRead( $function, TranslatorInterface $translator = null ){
        $this->attempt( "read", $function, $translator );
    }


    /**
     * Set the translation function that will be called right before the response is made
     *
     * @param callable $translate
     */
    public function setTranslationFunction( callable $translate ){
        $this->translation_function = $translate;
    }

    /**
     * Translate the files
     */
    protected function translateFiles(){
        $contents = $this->getContents();
        if( count($contents) > 0 && $this->translation_function !== null ){

            /** @var callable $translate */
            $translate = $this->translation_function;

            $translated_contents = array();
            for( $i = 0, $length = count( $contents ); $i < $length; $i++ ){
                $translated_contents[] = $translate( $contents[$i] );
            }
            $this->config['data']['contents'] = $translated_contents;
        }
    }

    /**
     * Takes a file and turns it into data expected by the API
     *
     * @param SplFileInfo $file
     */
    protected function transformFileToData( SplFileInfo $file ){
        $filedata = array();
        $filedata['name'] = $file->getFilename();
        $filedata['directory'] = $file->getRelativePath();
        $filedata['path'] = $file->getRelativePath() . $file->getFilename();

        $absolutepath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();

        $filedata['file_extension'] = $file->getExtension();
        $mimetype = @finfo_file( $this->finfo, $absolutepath );
        $filedata['mimetype'] = $mimetype;

        if( $file->isDir() ){
            $filedata['type'] = "dir";
        } else {
            $filedata['type'] = "file";

            // Just get the tracable URL of the thumbnail we generate thumbnails of all the files
            if( $this->thumbnail_strategy === ThumbnailGeneratorService::STRATEGY_ALL ){

                $filedata['preview'] = "/thumbnails/" . ThumbnailGeneratorService::generateRetracableThumbnailName(
                        PathUtils::addTrailingSlash( $file->getRelativePath() ) . $file->getFilename() );
            } else {

                // Search for the file preview if it is enabled
                $fileref = null;
                if( $this->fileRepository != null && $this->webdir != null ){
                    $constraints = array(
                        "working_directory" => str_replace( $filedata['path'], "", $absolutepath),
                        "relative_path" => $file->getRelativePath(),
                        "filename" => $file->getFilename(),
                    );

                    $fileref = $this->fileRepository->findOneBy( $constraints );
                    if( $fileref != null && ( $fileref->getPreviewUrl() != "" && $fileref->getPreviewUrl() != null ) ){
                        $filedata['preview'] = PathUtils::stripWorkingDirectoryFromAbsolutePath( $this->webdir, $fileref->getPreviewUrl() );
                    }
                }

                if( $fileref == null && strpos( $mimetype, "image") !== false ){
                    $filedata['preview'] = $this->preview_link . "?filemanager_path=" . $filedata['path'];
                }
            }
        }

        $date = new \DateTime();
        $date->setTimestamp( $file->getMTime() );
        $filedata['date_modified'] = $date->format("Y-m-d H:i:s");
        $filedata['size'] = $file->getSize();

        return $filedata;
    }

    /**
     * Returns all the files in this response
     *
     * @return array
     */
    protected function getContents(){
        $files = array();
        if( isset( $this->config['data']['contents'] ) ){
            $files = $this->config['data']['contents'];
        }
        return $files;
    }

    // ----------------------------------------------------- STANDARD BUILDER METHODS

    /**
     * Add a value to the request data
     *
     * @param string $key           The key to add
     * @param $value                The value linked to the key
     * @return FilemanagerResponseBuilder
     */
    public function add( $key, $value ){
        $this->config["data"][$key] = $value;
        return $this;
    }

    /**
     * Create the response required for the FileManager
     *
     * @return Response
     */
    public function build(){
        $this->translateFiles();
        @finfo_close( $this->finfo );

        $response = new Response( json_encode( $this->config ) );
        if( $this->statuscode != 200 ){
            $response->setStatusCode( $this->statuscode, $this->error_message );
        }
        return $response;
    }

    /**
     * Attempts something and catches all the exceptions to translate them
     *
     * @param string $type                               change or read
     * @param callable $function                         The function to execute and use the return value from
     * @param TranslatorInterface $translator
     */
    private function attempt($type, $function, TranslatorInterface $translator = null){
        $message = null;

        try {

            if( $type == "change" ) {
                $this->addChange( $function() );
            } else {
                $this->addFiles( $function() );
            }

        } catch (AccessDeniedException $e ){

            $message = $e->getMessage();
            if( $translator != null ){
                $message = $translator->trans("You are not allowed to perform this action");
            }
        } catch (\Symfony\Component\Finder\Exception\AccessDeniedException $e ){

            $message = $e->getMessage();
            if( $translator != null ){
                $message = $translator->trans("No access to file");
            }
        } catch (FileNotFoundException $e ){

            $message = $e->getMessage();
            if( $translator != null ){
                $message = $translator->trans("File not found");
            }
        } catch (ConflictException $e ){

            $message = $e->getMessage();
            if( $translator != null ){
                $message = $translator->trans("File already exists");
            }
        } catch (FileException $e ){

            $message = $e->getMessage();
            if( $translator != null ){
                $message = $translator->trans("File not created");
            }
        } catch (FileTooLargeException $e ){

            $message = $e->getMessage();
            if( $translator != null ){
                $message = $translator->trans("File too large");
            }
        } catch (IOException $e){
            $message = $e->getMessage();
            if( $translator != null ){
                $message = $translator->trans("Action failed");
            }
        }

        if( $message != null ){
            $this->fail( $message );
        }
    }

    /**
     * Set the filerepository and the root directory so we can retrieve thumbnails
     *
     * @param FileRepository $repository
     */
    public function enableThumbnailLinking( FileRepository $repository, $rootdir ){
        $this->fileRepository = $repository;
        $this->webdir = PathUtils::addTrailingSlash(
                PathUtils::moveUpPath( $rootdir )
            ) . "web";

    }

}