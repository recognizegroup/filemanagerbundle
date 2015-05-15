<?php
namespace Recognize\FilemanagerBundle\Security;

class FilePermissionCache {

    private $cache;
    protected $staged_cache = array();

    public function __construct(){
        $this->clearCache();
    }

    /**
     * Check if the action for the path is already set
     *
     * @param $action
     * @param $path
     * @return bool
     */
    public function isCached( $action, $path ){
        return isset( $this->cache[ $action ][ $path ] );
    }

    /**
     * Return the cached result of the permission
     *
     * @param $action
     * @param $path
     * @return mixed
     */
    public function isGranted( $action, $path ){
        return $this->cache[ $action ][ $path ];
    }

    /**
     * Add a path to the staging area of the cache
     *
     * @param $path
     */
    public function stagePath( $path ){
        $this->staged_cache[] = $path;
    }

    /**
     * Commit all the results of the staged paths with the result of the permission
     *
     * @param $action
     * @param $granted
     */
    public function commitResultsForStagedPaths( $action, $granted ){
        for( $i = 0, $length = count( $this->staged_cache ); $i < $length; $i++ ){
            $this->cache[ $action ][ $this->staged_cache[ $i ] ] = $granted;
        }

        $this->staged_cache = array();
    }

    /**
     * Clears all the results from the cache
     */
    public function clearCache(){
        $this->cache = array(
            "open" => array(),
            "upload" => array(),
            "create" => array(),
            "rename" => array(),
            "move" => array(),
            "delete" => array(),
            "mask_owner" => array()
        );
    }

}