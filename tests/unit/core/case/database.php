<?php
/**
 * @package    JSpace.Test
 *
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

if (!class_exists('PHPUnit_Extensions_Database_TestCase'))
{
    require_once 'PHPUnit/Extensions/Database/TestCase.php';
    require_once 'PHPUnit/Extensions/Database/DataSet/XmlDataSet.php';
    require_once 'PHPUnit/Extensions/Database/DataSet/QueryDataSet.php';
    require_once 'PHPUnit/Extensions/Database/DataSet/MysqlXmlDataSet.php';
}

/**
 * Abstract test case class for database testing.
 *
 * @package  JSpace.Test
 */
abstract class TestCaseDatabase extends PHPUnit_Extensions_Database_TestCase
{
    protected static $driver;
    
    private static $_stash;
    
    public static function setUpBeforeClass()
    {    
        $options = array(
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'jos_'
        );

        try
        {
            self::$driver = JDatabaseDriver::getInstance($options);
            
            $pdo = new PDO('sqlite::memory:');
            
            $pdo->exec(file_get_contents(JSPACEPATH_TESTS.'/schema/ddl.sql'));

            $property = new \ReflectionProperty(get_parent_class(self::$driver), 'connection');
            $property->setAccessible(true);

            $property->setValue(self::$driver, $pdo);
        }
        catch (RuntimeException $e)
        {
            self::$driver = null;
        }

        if (self::$driver instanceof Exception)
        {
            self::$driver = null;
        }
        
        self::$_stash = JFactory::$database;
        JFactory::$database = self::$driver;
    }
    
    public function setUp()
    {
        if (empty(static::$driver))
        {
            $this->markTestSkipped('There is no database driver.');
        }
        
        parent::setUp();
    }
    
    protected function getDataSet()
    {
        $dataSet = new PHPUnit_Extensions_Database_DataSet_CsvDataSet(',', "'", '\\');
        
        $dataSet->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions.csv');
        $dataSet->addTable('jos_categories', JSPACEPATH_TESTS.'/stubs/database/jos_categories.csv');
        $dataSet->addTable('jos_usergroups', JSPACEPATH_TESTS.'/stubs/database/jos_usergroups.csv');
        $dataSet->addTable('jos_viewlevels', JSPACEPATH_TESTS.'/stubs/database/jos_viewlevels.csv');        
        $dataSet->addTable('jos_jspace_records', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_records.csv');
        $dataSet->addTable('jos_jspace_assets', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_assets.csv');
        $dataSet->addTable('jos_jspace_cache', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_cache.csv');
        $dataSet->addTable('jos_assets', JSPACEPATH_TESTS.'/stubs/database/jos_assets.csv');
        $dataSet->addTable('jos_content_types', JSPACEPATH_TESTS.'/stubs/database/jos_content_types.csv');
        $dataSet->addTable('jos_ucm_history', JSPACEPATH_TESTS.'/stubs/database/jos_ucm_history.csv');
        $dataSet->addTable('jos_tags', JSPACEPATH_TESTS.'/stubs/database/jos_tags.csv');
        $dataSet->addTable('jos_contentitem_tag_map', JSPACEPATH_TESTS.'/stubs/database/jos_contentitem_tag_map.csv');
        $dataSet->addTable('jos_ucm_base', JSPACEPATH_TESTS.'/stubs/database/jos_ucm_base.csv');
        $dataSet->addTable('jos_jspacedspace_records', JSPACEPATH_TESTS.'/stubs/database/jos_jspacedspace_records.csv');
        $dataSet->addTable('jos_users', JSPACEPATH_TESTS.'/stubs/database/jos_users.csv');
        $dataSet->addTable('jos_user_usergroup_map', JSPACEPATH_TESTS.'/stubs/database/jos_user_usergroup_map.csv');
        $dataSet->addTable('jos_jspace_record_identifiers', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_record_identifiers.csv');
        $dataSet->addTable('jos_weblinks', JSPACEPATH_TESTS.'/stubs/database/jos_weblinks.csv');
        $dataSet->addTable('jos_ucm_content', JSPACEPATH_TESTS.'/stubs/database/jos_ucm_content.csv');
        $dataSet->addTable('jos_languages', JSPACEPATH_TESTS.'/stubs/database/jos_languages.csv');
        
        return $dataSet;
    }
    
    public function getConnection()
    {
        if (!is_null(self::$driver))
        {
            return $this->createDefaultDBConnection(self::$driver->getConnection(), ':memory:');
        }
        else
        {
            return null;
        }
    }
    
    public static function tearDownAfterClass()
    {
        JFactory::$database = self::$_stash;
        self::$driver = null;
    }
}