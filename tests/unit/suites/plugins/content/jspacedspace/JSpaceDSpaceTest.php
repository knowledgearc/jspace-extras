<?php
jimport('jspace.archive.record');

require_once(JSPACEPATH_TESTS.'/core/case/database.php');

// @todo query dspace for info to assert tests against.
class JSpaceDSpaceTest extends TestCaseDatabase
{
    public $url = null;

    public function setUp()
    {
        parent::setUp();
        
        $plugin =JPluginHelper::getPlugin('content', 'jspacedspace');
        
        $params = new JRegistry();
        $params->loadString($plugin->params);
        $this->url = $params->get('url');
    }

    public function testDSpaceCreateItem()
    {
        $files = $this->_getFiles();

        $record = $this->_createRecord();
        $result = $record->save($files);
        $this->assertTrue($result);

        $equals = array(
            'dc.contributor.author'=>'Hayden Young',
            'dc.title'=>'DSpace Test Case'
        );
        
        $this->_assertMetadata($record, $equals);
    }
   
    public function testDSpaceUpdateItem()
    {
        // create a new record before trying to edit it.
        $new = $this->_createRecord();
        $new->save();

        $registry = new JRegistry;
        $registry->set('title', array('DSpace Test Case Updated'));
        $registry->set('author', array('Hayden Young'));
        $registry->set('description', array('Update description.'));
    
        $record = new JSpaceRecord();
        $record->id = $new->id;
        $record->catid = 9;
        $record->set('title', 'Updated Title');
        $record->set('language', '*');
        $record->set('path', 'updated-path');
        $record->set('metadata', $registry);

        $result = (bool)$record->save();
        
        $this->assertTrue($result);
        
        $equals = array(
            'dc.contributor.author'=>'Hayden Young',
            'dc.title'=>'DSpace Test Case Updated',
            'dc.description'=>'Update description.'
        );
        
        $this->_assertMetadata($record, $equals);
    }

    public function testDSpaceUpdateItemWithAssets()
    {
        $files = $this->_getFiles();
        
        // create a new record before trying to edit it.
        $new = $this->_createRecord();
        $new->save();

        $registry = new JRegistry;
        $registry->set('title', array('DSpace Test Case Updated'));
        $registry->set('author', array('Hayden Young'));
        $registry->set('description', array('Update description.'));
    
        $record = new JSpaceRecord();
        $record->id = $new->id;
        $record->catid = 9;
        $record->set('title', 'Updated Title');
        $record->set('language', '*');
        $record->set('path', 'updated-path');
        $record->set('metadata', $registry);

        $result = (bool)$record->save($files);
        
        $this->assertTrue($result);
        
        $equals = array(
            'dc.contributor.author'=>'Hayden Young',
            'dc.title'=>'DSpace Test Case Updated',
            'dc.description'=>'Update description.'
        );
        
        $this->_assertMetadata($record, $equals);
    }

    public function testDSpaceDeleteARecord()
    {
        $files = $this->_getFiles();

        $record = $this->_createRecord();
        $result = $record->save($files);
        $this->assertTrue($result);

        $equals = array(
            'dc.contributor.author'=>'Hayden Young',
            'dc.title'=>'DSpace Test Case'
        );
        
        $query = JFactory::getDbo()->getQuery(true);
        
        $query
            ->select('COUNT(*)')
            ->from('jos_jspacedspace_records');

        // do we have a record?
        $this->assertEquals(1, JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        
        $query
            ->select('dspace_id')
            ->from('jos_jspacedspace_records')
            ->where('record_id='.$record->id);
        
        $dspaceId = JFactory::getDbo()->setQuery($query)->loadResult();
        
        $record->delete();
        
        $query = JFactory::getDbo()->getQuery(true);
        
        $query
            ->select('COUNT(*)')
            ->from('jos_jspacedspace_records');

        // record should have fired the event, deleting the corresponding dspace record.
        $this->assertEquals(0, JFactory::getDbo()->setQuery($query)->loadResult());
        
        $http = JHttpFactory::getHttp();
        $url = new JUri(JString::rtrim($this->url, '/').'/items/'.$dspaceId.'.json');
        $response = $http->get($url);
        
        $this->assertEquals(404, $response->code);
    }
    
    private function _assertMetadata($record, $expected)
    {
        $query = JFactory::getDbo()->getQuery(true);
        
        $query
            ->select('COUNT(*)')
            ->from('jos_jspacedspace_records');

        // check that the plugin has cleaned up (deleted) the previous record.
        $this->assertEquals(1, JFactory::getDbo()->setQuery($query)->loadResult());
    
        $query = JFactory::getDbo()->getQuery(true);
        
        $query
            ->select('dspace_id')
            ->from('jos_jspacedspace_records')
            ->where('record_id='.$record->id);
        
        $dspaceId = JFactory::getDbo()->setQuery($query)->loadResult();

        $http = JHttpFactory::getHttp();
        $url = new JUri(JString::rtrim($this->url, '/').'/items/'.$dspaceId.'.json');
        $response = $http->get($url);
        
        $item = json_decode($response->body);
        
        $actual = array();
        foreach ($item->metadata as $field)
        {
            $name = $field->schema.'.'.$field->element.(($field->qualifier) ? '.'.$field->qualifier : '');
            
            $actual[$name] = $field->value;
        }
        
        // check what metadata has been saved in DSpace.
        $this->assertEquals($expected, $actual);
    }

    private function _createRecord()
    {
        $registry = new JRegistry;
        $registry->set('title', array('DSpace Test Case'));
        $registry->set('author', array('Hayden Young'));
    
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $registry);

        return $record;
    }
    
    private function _getFiles()
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