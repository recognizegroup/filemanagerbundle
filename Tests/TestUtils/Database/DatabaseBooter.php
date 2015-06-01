<?php
namespace Recognize\FilemanagerBundle\Tests\TestUtils\Database;

use AppKernel;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Connection;

/**
 * Utility class that manages the schema and fixtures
 *
 *
 * Class DatabaseBooter
 * @package ASWatsonKKS\AdminBundle\Tests\TestUtils\Database
 */
class DatabaseBooter {

    protected $testkernel;
    protected $rootpath;
    protected $db;

    public function __construct( $rootpath ){
        $this->testkernel = new AppKernel('test', true);
        $this->testkernel->boot();
        $this->rootpath = $rootpath;

        $this->checkingIfDatabasenameIsProduction();
    }

    /**
     * Makes sure we don't run the database purging and recreation on a developers or production database
     */
    public function checkingIfDatabasenameIsProduction(){
        $devkernel = new AppKernel('dev', true);
        $devkernel->boot();
        $prodkernel = new AppKernel('prod', true);
        $prodkernel->boot();

        /** @var Connection $devdb */
        $devdb = $devkernel->getContainer()->get('doctrine')->getManager()->getConnection();

        /** @var Connection $proddb */
        $proddb = $prodkernel->getContainer()->get('doctrine')->getManager()->getConnection();

        /** @var Connection $testdb */
        $testdb = $this->testkernel->getContainer()->get('doctrine')->getManager()->getConnection();
        $this->db = $testdb->getDatabase();


        if( $proddb->getDatabase() === $testdb->getDatabase() ||
            $devdb->getDatabase() === $testdb->getDatabase() ){

            echo "\n\033[1m\033[41m Achievement unlocked: Production database nuker! \033[00m \n";
            echo "Make sure the name of the testdatabase and the production or development database aren't the same \n";
            echo "To prevent the database from being accidentaly cleared \n\n";
            die();
        }
    }

    /**
     * Reverts all the auto increments of the tables to 1 and removes all the content from the tables
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function clearDatabase() {
        echo "Purging test database \n";
        $doctrine = $this->testkernel->getContainer()->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getManager()->getConnection();
        $connection->query(sprintf('SET FOREIGN_KEY_CHECKS = 0;'));

        $tables = $connection->getSchemaManager()->listTables();
        $truncate_query = "";
        foreach ($tables as $table) {
            $truncate_query = sprintf( "TRUNCATE %s.%s;", $this->db, $table->getName() );

            try {
                $connection->query($truncate_query);
                echo ".";
            } catch( \Exception $e ){
                // Silently fail errors when creating and altering tables during test
                echo "\033[1m\033[41m.\033[00m";
            }
        }

        $connection->query(sprintf('SET FOREIGN_KEY_CHECKS = 1;'));
        echo "\nDone!\n\n";
    }

    /**
     * Creates the schema needed for the fixtures
     */
    public function createAndAlterDatabase( $verbose = false){
        echo "Creating schema\n";
        $doctrine = $this->testkernel->getContainer()->get('doctrine');
        $connection = $doctrine->getManager()->getConnection();

        $metaclasses = $doctrine->getManager()->getMetadataFactory()->getAllMetadata();
        $schematool = new ForeignKeyIgnoringSchemaTool( $doctrine->getManager() );
        $classes = array();
        /** @var ClassMetadata $meta */
        foreach( $metaclasses as $meta ){
            $classes[] = $meta;
        }

        // Create tables
        $sql = $schematool->getCreateSchemaSql( $classes );
        if( count($sql) > 0 ){
            for( $i = 0, $length = count( $sql ); $i < $length; $i++ ){
                $query = $sql[$i];
                $replace_once = 1;
                $query = str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $query, $replace_once);
                try {
                    $connection->query( $query );
                    echo ".";
                } catch( \Exception $e ){
                    // Silently fail errors when creating and altering tables during test
                    echo "\033[1m\033[41m.\033[00m";
                    if( $verbose ){
                        echo "\n" . $e->getMessage();
                    }
                }
            }
        }

        echo "\nDone! \n\n";

    }


    /**
     * Find all the fixtures and add them
     */
    public function fillDatabaseWithFixtures(){
        echo "Filling database with DataFixtures... \n";

        $em = $this->testkernel->getContainer()->get('doctrine')->getManager();

        $loader = new CustomDataFixtureLoader();
        $loader->loadFromDirectory( $this->rootpath );

        $purger = new ORMPurger();
        $executor = new ORMExecutor($em, $purger);
        $executor->execute( $loader->getFixtures() );

        echo "Done! \n\n";
    }

}