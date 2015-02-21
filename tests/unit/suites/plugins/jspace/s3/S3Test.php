<?php
use JSpace\Archive\Record;

require_once(JSPACEPATH_TESTS.'/core/case/database.php');

class JSpaceS3Test extends \TestCaseDatabase
{
    public $url = null;

    public function setUp()
    {
        parent::setUp();

        // retrieve s3 settings from properties file.
        $properties = parse_ini_file(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/build.properties');
        $bucket = $properties['s3.bucket'];
        $id = $properties['s3.secret_key_id'];
        $secret = $properties['s3.secret_key_access'];

        $database = JFactory::getDbo();
        $query = $database->getQuery(true);
        $query
            ->update('#__extensions')
            ->set("params='{\"bucket\":\"".$bucket."\",\"access_key_id\":\"".$id."\",\"secret_access_key\":\"".$secret."\"}'")
            ->where("name='plg_jspace_s3'");

        $database->setQuery($query)->execute();

        $plugin = JPluginHelper::getPlugin('jspace', 's3');

        $params = new Joomla\Registry\Registry;
        $params->loadString($plugin->params);
        $this->url = $params->get('url');
    }

    public function testCreateItem()
    {
        $files = $this->getFiles();

        $record = $this->createRecord();
        $result = $record->save($files);

        $this->assertTrue($result);

        $equals = array(
            'dc.contributor.author'=>'Hayden Young',
            'dc.title'=>'DSpace Test Case'
        );
    }

    private function createRecord()
    {
        $registry = new Joomla\Registry\Registry;
        $registry->set('title', array('DSpace Test Case'));
        $registry->set('author', array('Hayden Young'));

        $record = new Record();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $registry);

        return $record;
    }

    private function getFiles()
    {
        $files = array();
        $files["original"] = array();

        $files["original"][] = array(
            'name'=>'file1.jpg',
            'type'=>'image/jpg',
            'tmp_name'=>dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/stubs/files/file1.jpg',
            'error'=>0,
            'size'=>515621);

        $files["original"][] = array(
            'name'=>'file2.jpg',
            'type'=>'image/jpg',
            'tmp_name'=>dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/stubs/files/file2.jpg',
            'error'=>0,
            'size'=>248198);

        return $files;
    }
}