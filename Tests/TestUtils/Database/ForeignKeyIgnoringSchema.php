<?php
namespace Recognize\FilemanagerBundle\Tests\TestUtils\Database;

use Doctrine\DBAL\Schema\Schema;

class ForeignKeyIgnoringSchema extends Schema {

    /**
     * Creates a new table.
     *
     * @param string $tableName
     *
     * @return \Doctrine\DBAL\Schema\Table
     */
    public function createTable($tableName)
    {
        $table = new ForeignKeyIgnoringTable($tableName);
        $this->_addTable($table);

        foreach ($this->_schemaConfig->getDefaultTableOptions() as $name => $value) {
            $table->addOption($name, $value);
        }

        return $table;
    }


}