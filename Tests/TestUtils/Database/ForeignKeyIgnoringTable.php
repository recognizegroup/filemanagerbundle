<?php
namespace Recognize\FilemanagerBundle\Tests\TestUtils\Database;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;

class ForeignKeyIgnoringTable extends Table {

    /**
     * @param Index $indexCandidate
     */
    protected function _addIndex(Index $indexCandidate){
        try {
            parent::_addIndex( $indexCandidate );
        } catch ( \Exception $e ){
            // Ignore the ForeignKey error exception
        }
    }



}