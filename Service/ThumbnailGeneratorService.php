<?php
namespace Recognize\FilemanagerBundle\Service;


use Imagick;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Utils\PathUtils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ThumbnailGeneratorService implements ThumbnailGeneratorInterface {

    private $thumbnail_directory = null;
    private $thumbnail_size = 50;

    public function __construct( array $configuration ){

        if( isset( $configuration['thumbnail'] ) ){
            if( isset( $configuration['thumbnail']['directory'] ) ){
                $this->thumbnail_directory = $configuration['thumbnail']['directory'];
            }

            if( isset( $configuration['thumbnail']['size'] ) ) {
                $this->thumbnail_size = $configuration['thumbnail']['size'];
            }
        }
    }

    /**
     * Generate a thumbnail for a filereference
     *
     * @param FileReference $reference
     * @return $path to thumbnail
     */
    public function generateThumbnailForFile( FileReference $reference = null ){
        $thumbnaillink = "";

        // Only generate the thumbnail if we are dealing with an image and if the file exists
        $fs = new FileSystem();
        if( $this->thumbnail_directory != null && $reference != null
            && $fs->exists( $reference->getAbsolutePath() )
            && $this->canGenerateThumbnail( $reference->getMimetype() ) ){

            $thumbnailname = $this->generateThumbnailName( $reference->getAbsolutePath(), $reference->getExtension() );
            $thumbnaillink = PathUtils::addTrailingSlash( $this->thumbnail_directory ) . $thumbnailname;

            if( strpos( $reference->getMimetype(), "png" ) !== false ){
                $this->createThumbnailFileForPNG( $reference->getAbsolutePath(), $thumbnaillink );
            } else if( strpos( $reference->getMimetype(), "jpg" ) !== false ||
                strpos( $reference->getMimetype(), "jpeg" ) !== false ){
                $this->createThumbnailFileForJPG( $reference->getAbsolutePath(), $thumbnaillink );
            } else if ( extension_loaded('imagick') == true &&
                strpos( $reference->getMimetype(), "pdf" ) !== false ){
                $count = 1;
                $thumbnaillink = str_replace(".pdf", ".jpg", $thumbnaillink, $count );
                $this->createThumbnailFileForPDF( $reference->getAbsolutePath(), $thumbnaillink );
            }
        }

        return $thumbnaillink;
    }

    /**
     * Check if a thumbnail can be created for this mimetype
     */
    protected function canGenerateThumbnail( $mimetype ){
        return ( strpos( $mimetype, "image" ) !== false && strpos( $mimetype, "svg" ) === false )
        || extension_loaded('imagick') && strpos($mimetype, "pdf") !== false;
    }

    /**
     * Generates a thumbnail filename that doesn't overwrite another file
     *
     * @param string $filename                     The filename
     * @param string $extension                    The files extension
     */
    protected function generateThumbnailName( $filename, $extension ){
        $thumbnaillink = md5( $filename . time() ) . "." . $extension;


        $fs = new FileSystem();
        if( $fs->exists( PathUtils::addTrailingSlash( $this->thumbnail_directory ) . $thumbnaillink) == false ){
            return $thumbnaillink;
        } else {
            return $this->generateThumbnailForFile( $filename . "1", $extension);
        }
    }

    /**
     * Create a thumbnail for a png file
     *
     * @param $oldpath
     * @param $newpath
     */
    protected function createThumbnailFileForPNG( $oldpath, $newpath ){
        $im = imagecreatefrompng( $oldpath );

        $width = $this->thumbnail_size;
        $height = $this->thumbnail_size;

        $thumbnailfile = imagecreatetruecolor($width, $height);
        imagealphablending($thumbnailfile, false);
        imagesavealpha($thumbnailfile,true);

        $sample = $this->getResizedSampleFromImage( $im );

        $transparent = imagecolorallocatealpha($thumbnailfile, 255, 255, 255, 127);
        imagefilledrectangle($thumbnailfile, 0, 0, $width, $height, $transparent);
        imagecopyresampled($thumbnailfile, $im, $sample['destx'], $sample['desty'], $sample['x'], $sample['y'],
            $sample['srcwidth'], $sample['srcheight'], $sample['width'], $sample['height']);

        imagepng( $thumbnailfile, $newpath );
    }

    /**
     * Create a thumbnail for a jpg file
     *
     * @param $oldpath
     * @param $newpath
     */
    protected function createThumbnailFileForJPG( $oldpath, $newpath ){
        $im = imagecreatefromjpeg( $oldpath );

        $width = $this->thumbnail_size;
        $height = $this->thumbnail_size;

        $thumbnailfile = imagecreatetruecolor($width, $height);
        imagealphablending($thumbnailfile, false);
        imagesavealpha($thumbnailfile,true);

        $sample = $this->getResizedSampleFromImage( $im );

        $transparent = imagecolorallocatealpha($thumbnailfile, 255, 255, 255, 127);
        imagefilledrectangle($thumbnailfile, 0, 0, $width, $height, $transparent);
        imagecopyresampled($thumbnailfile, $im, $sample['destx'], $sample['desty'], $sample['x'], $sample['y'],
            $sample['srcwidth'], $sample['srcheight'], $sample['width'], $sample['height']);

        imagejpeg( $thumbnailfile, $newpath );
    }

    /**
     * Create a thumbnail for a PDF file
     *
     * @param $oldpath
     * @param $newpath
     */
    protected function createThumbnailFileForPDF( $oldpath, $newpath ){
        $pdf = new imagick( $oldpath . '[0]');
        $pdf->setImageFormat('jpg');
        $pdf->setResolution($this->thumbnail_size, $this->thumbnail_size);

        file_put_contents($newpath, $pdf);
    }

    /**
     * Get the sample position and size to generate a properly cropped image
     *
     * @param $im
     * @return array
     */
    protected function getCroppedSampleFromImage( $im ){
        $sample = array();
        $sample['x'] = 0;
        $sample['y'] = 0;
        $sample['destx'] = 0;
        $sample['desty'] = 0;
        $sample['srcwidth'] = $this->thumbnail_size;
        $sample['srcheight'] = $this->thumbnail_size;

        $oldwidth = imagesx( $im );
        $oldheight = imagesy( $im );

        // Only resize if the width and the height of the image aren't a square ratio
        if( $oldheight !== $oldwidth ){

            // Get the center of both sizes - divide them by two to get the offset
            if( $oldheight < $oldwidth ){
                $sample['x'] = $oldwidth / 2 - $oldheight / 2;
                $oldwidth = $oldheight;

            } else if ( $oldwidth < $oldheight ){

                $sample['y'] = $oldheight / 2 - $oldwidth / 2;
                $oldheight = $oldwidth;
            }
        }

        $sample['width'] = $oldwidth;
        $sample['height'] = $oldheight;

        return $sample;
    }

    /**
     * Get the sample position and size to generate a properly resized image
     *
     * @param $im
     * @return array
     */
    protected function getResizedSampleFromImage( $im ){
        $sample = array();
        $sample['x'] = 0;
        $sample['y'] = 0;
        $sample['destx'] = 0;
        $sample['desty'] = 0;
        $srcwidth = $this->thumbnail_size;
        $srcheight = $this->thumbnail_size;

        $sample['srcwidth'] = $srcwidth;
        $sample['srcheight'] = $srcheight;

        $oldwidth = imagesx( $im );
        $oldheight = imagesy( $im );

        // Only resize if the width and the height of the image aren't a square ratio
        if( $oldheight !== $oldwidth ){

            // Get the width height ratio, then fit the smallest area inside the source area
            if( $oldheight < $oldwidth ){

                $ratio = $oldheight / $oldwidth;
                $sample['srcheight'] = $srcheight * $ratio;
                $sample['desty'] = ( $srcheight / 2 ) - ( ( $srcheight * $ratio ) / 2 );

            } else if ( $oldwidth < $oldheight ){

                $ratio = $oldwidth / $oldheight;
                $sample['srcwidth'] = $srcwidth * $ratio;
                $sample['x'] = ( $srcwidth / 2 ) - ( ( $srcwidth * $ratio ) / 2 );

            }
        }

        $sample['width'] = $oldwidth;
        $sample['height'] = $oldheight;

        return $sample;
    }


}