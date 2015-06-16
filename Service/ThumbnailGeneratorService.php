<?php
namespace Recognize\FilemanagerBundle\Service;


use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Utils\PathUtils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ThumbnailGeneratorService implements ThumbnailGeneratorInterface {

    private $thumbnail_directory = null;

    public function __construct( array $configuration ){

        if( isset( $configuration['thumbnail_directory']) ){
            $this->thumbnail_directory = $configuration['thumbnail_directory'];
        } else {
            throw new \RuntimeException( "Default upload and file management directory should be set! " );
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
            && $this->isImage( $reference->getMimetype() ) ){

            $thumbnailname = $this->generateThumbnailName( $reference->getAbsolutePath(), $reference->getExtension() );
            $thumbnaillink = PathUtils::addTrailingSlash( $this->thumbnail_directory ) . $thumbnailname;

            if( strpos( $reference->getMimetype(), "png" ) !== false ){
                $this->createThumbnailFileForPNG( $reference->getAbsolutePath(), $thumbnaillink );
            } else if( strpos( $reference->getMimetype(), "jpg" ) !== false ||
                strpos( $reference->getMimetype(), "jpeg" ) ){
                $this->createThumbnailFileForJPG( $reference->getAbsolutePath(), $thumbnaillink );
            }
        }

        return $thumbnaillink;
    }

    /**
     * Check if the mimetype is an image
     */
    protected function isImage( $mimetype ){
        return strpos( $mimetype, "image" ) !== false && strpos( $mimetype, "svg" ) === false;
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

        $width = 50;
        $height = 50;

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

        $width = 50;
        $height = 50;

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
        $sample['srcwidth'] = 50;
        $sample['srcheight'] = 50;

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
        $srcwidth = 50;
        $srcheight = 50;

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