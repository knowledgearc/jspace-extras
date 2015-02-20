<?php
use JSpace\Archive\Record;

require_once(JSPACEPATH_TESTS.'/core/case/database.php');

class JSpaceS3Test extends \TestCaseDatabase
{
    public $url = null;

    public function setUp()
    {
        parent::setUp();

        $plugin = JPluginHelper::getPlugin('jspace', 's3');

        $params = new Joomla\Registry\Registry;
        $params->loadString($plugin->params);
        $this->url = $params->get('url');
    }

    public function testCreateItem()
    {
        $files = array();//$this->getFiles();

        $record = $this->createRecord();
        $result = $record->save($files);
        var_dump($result);
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
        $files["bundle"] = array();
        $files["bundle"]["assets"] = array();
        $files["bundle"]["assets"]["original"] = array();

        $files["bundle"]["assets"]["original"][] = array(
            'name'=>'file1.jpg',
            'type'=>'image/jpg',
            'tmp_name'=>dirname(__FILE__).'/file1.jpg',
            'error'=>0,
            'size'=>515621);

        $files["bundle"]["assets"]["original"][] = array(
            'name'=>'file2.jpg',
            'type'=>'image/jpg',
            'tmp_name'=>dirname(__FILE__).'/file2.jpg',
            'error'=>0,
            'size'=>248198);

        return $files;
    }
}