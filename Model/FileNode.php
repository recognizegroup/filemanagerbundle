<?php
namespace Recognize\FilemanagerBundle\Model;

abstract class FileNode implements \ArrayAccess {

    protected $filename;
    protected $path;
    protected $filetype;
    protected $children = array();

    public function getChildren(){
        return $this->children;
    }

    /**
     * Check if the node is a leaf
     *
     * @return bool
     */
    public function isLeafnode(){
        return count( $this->children ) > 0;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset           An offset to check for.
     *
     * @return boolean true on success or false on failure.
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset){
        return isset( $this->children[ $offset ] );
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset           The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet( $filename ){
        return $this->children[ $filename ];
    }

    /**
     * Disallow setting the children from outside the node
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset           The offset to assign the value to.
     * @param mixed $value            The value to set.
     *
     * @return void
     */
    public function offsetSet($offset, $value) {
        // Dont allow setting
    }

    /**
     * Disallow unsetting the children from outside the node
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset          The offset to unset.
     *
     * @return void
     */
    public function offsetUnset( $offset ){
        // Dont allow unsetting
    }

}