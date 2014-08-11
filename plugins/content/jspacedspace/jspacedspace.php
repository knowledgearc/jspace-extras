<?php
/**
 * @package    JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

jimport('jspace.factory');
jimport('jspace.archive.assethelper');
jimport('jspace.filesystem.file');
jimport('jspace.html.assets');

/**
 * Stores metadata and assets in a REST API-enabled DSpace archive.
 *
 * @package  JSpace.Plugin
 */
class PlgContentJSpaceDSpace extends JPlugin
{
    protected static $chunksize = 4096;
    
    protected static $assets = array();

	/**
	 * Instatiates an instance of the PlgContentJSpaceDSpace class.
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		JLog::addLogger(array());

		// load the jspace component's params into plugin params for
		// easy access.
		$params = JComponentHelper::getParams('com_jspace', true);
		
		$this->params->loadArray(array('component'=>$params->toArray()));
	}
	
    /**
     * Returns the HTML to the JSpace DSpace Asset download mechanism.
     *
     * @param   JSpaceAsset  $asset  An instance of the asset being downloaded.
     * 
     * @return  string       The html to the JSpace DSpace Asset download mechanism.
     */
	public function onJSpaceAssetPrepareDownload($asset)
	{
        $asset->url = JRoute::_('index.php?option=com_jspace&task=asset.stream&type=jspacedspace&id='.$asset->id);

        $layout = JPATH_PLUGINS.'/content/jspacedspace/layouts';
        $html = JLayoutHelper::render("jspacedspace", $asset, $layout);

        return $html;
    }

    /**
     * Redirects the client to a DSpace download url.
     *
     * @param  JSpaceAsset  $asset  An instance of the asset being downloaded.
     */
    public function onJSpaceAssetDownload($asset)
    {
        JFactory::getApplication()->redirect($url);
    }
	
	/**
	 * Checks for the existence of a similar file already archived against the current record.
	 *
	 * @param  JForm  $form
	 * @param  array  $data
	 * @param  array  $group
	 */
    public function onJSpaceRecordAfterValidate($form, $data, $group = null)
    {
        // check user has set required params. If not, just throw errors.
        if (!$this->params->get('url'))
        {
            throw new UnexpectedValueException('DSpace REST API url required.');
        }
    
        if (!$this->params->get('username'))
        {
            throw new UnexpectedValueException('DSpace REST API username required.');
        }

        if (!$this->params->get('password'))
        {
            throw new UnexpectedValueException('DSpace REST API password required.');    
        }
        
        if ($catid = JArrayHelper::getValue($data, 'catid'))
        {        
            $category = JTable::getInstance('Category');
            
            if ($category->load($catid))
            {
                $params = new JRegistry($category->params);
                
                // if the collection id has not been set, ignore.
                if ($collection = $params->get('jspacedspace_collection'))
                {               
                    $http = JHttpFactory::getHttp();                    
                    $url = new JUri(JString::rtrim($this->params->get('url'), '/').'/collections/'
                    .$collection.'.json');
                    $headers = array(
                        'user'=>$this->params->get('username'),
                        'pass'=>$this->params->get('password'), 
                        'Content-Type'=>'application/json');
                        
                    $response = $http->get($url, $headers);
                    
                    if ((int)$response->code !== 200)
                    {
                        JFactory::getApplication()->enqueueMessage(JText::_('PLG_CONTENT_JSPACEDSPACE_INVALID_COLLECTION'), 'error');

                        return false;
                    }
                }
            }
        }

        return true;
    }
	
	/**
	 * Saves a record, its metadata and all its assets to DSpace.
	 *
     * @param   string     $context  The context of the content being passed. Will be com_jspace.record.
     * @param   JObject    $record   An instance of the JSpaceRecord class.
     * @param   bool       $isNew    True if the asset being saved is new, false otherwise.
	 *
	 * @return  bool       True if the record is successfully saved, false otherwise.
	 *
	 * @throws  Exception  Thrown if the record cannot be saved for any reason.
	 */
	public function onContentAfterSave($context, $record, $isNew)
    {
        if ($context == 'com_jspace.asset')
        {
            //@todo An issue here that we could run out of system memory with very large files.
            $handle = fopen($record->tmp_name, "r");
            $record->data = fread($handle, filesize($record->tmp_name));
            fclose($handle);
            
            static::$assets[] = JArrayHelper::fromObject($record);
        }
    
        if ($context != 'com_jspace.record')
        {
            return true;
        }
        
        return $this->_saveRecord($record, $isNew);
    }

    /**
     * Saves a record to DSpace.
     *
     * @param   JObject    $record  An instance of the JSpaceRecord class.
     * @param   bool       $isNew   True if the asset being saved is new, false otherwise.
     *
     * @return  bool       True if the record is successfully saved, false otherwise.
     *
     * @throws  Exception  Thrown if the record cannot be saved for any reason.
     */
    private function _saveRecord($record, $isNew)
    {
        $database = JFactory::getDbo();

        $query = $database->getQuery(true);
        
        $query
            ->select($database->qn('dspace_id'))
            ->from($database->qn('#__jspacedspace_records'))
            ->where($database->qn('record_id').'='.(int)$record->id);
            
        $dspaceId = $database->setQuery($query)->loadResult();
        
        $http = JHttpFactory::getHttp(null, 'curl');
        
        $headers = array(
            'user'=>$this->params->get('username'),
            'pass'=>$this->params->get('password'), 
            'Content-Type'=>'application/json');
        
        if ($dspaceId)
        {
            $assets = $record->getAssets();
            
            // if there are new assets the item will need to be deleted and recreated.
            // else if just the metadata has been updated, don't delete, just edit.
            if (count($assets))
            {
                $this->onContentBeforeDelete('com_jspace.record', $record);

                $dspaceId = 0;
            }
            else
            {                
                $crosswalk = JSpaceFactory::getCrosswalk($record->get('metadata'), array('name'=>'dspace'));
                $metadata = $crosswalk->walk(true);
                
                $data = new stdClass();
                $data->metadata = array();
                
                $i = 0;
                foreach ($metadata as $key=>$field)
                {
                    $data->metadata[$i] = new stdClass();
                    
                    foreach ($field as $value)
                    {
                        $data->metadata[$i]->name = $key;
                        $data->metadata[$i]->value = $value;
                        $i++;
                    }
                }

                $url = new JUri(JString::rtrim($this->params->get('url'), '/').'/items/'.$dspaceId.'/metadata.json');
                $response = $http->put($url, json_encode($data), $headers);
            }
        }
        
        if (!$dspaceId)
        {
            $headers['Content-Type'] = 'multipart/form-data';
            
            $package = $this->_packageRecord($record);
            
            $post = array(
                'upload'=>
                    curl_file_create($package, 'application/zip', JFile::getName($package)));
            
            $url = new JUri(JString::rtrim($this->params->get('url'), '/').'/items.stream');
            $response = $http->post($url, $post, $headers);
            
            JFile::delete($package);
            static::$assets = array();
        }
        
        switch ((int)$response->code)
        {
            case 201:
                $dspaceId = JArrayHelper::getValue($response->headers, 'EntityId', 0, 'int');
            
                $query = $database->getQuery(true);
                $columns = array($database->qn('record_id'), $database->qn('dspace_id'));
                $values = array((int)$record->id, $dspaceId);
            
                $query = $database->getQuery(true);
                
                $query
                    ->insert($database->qn('#__jspacedspace_records'))
                    ->columns($columns)
                    ->values(implode(",", $values));

                $database->setQuery($query)->execute();
                
                break;
                
            case 204:
                break;
        
            default:
                JLog::add(__METHOD__.' '.$response->body, JLog::ERROR, 'jspace');
                throw new Exception('Could not archive record in DSpace archive.');
                
                break;
        }
        
        return true;
    }
    
    public function _packageRecord($record)
    {
        $collection = 0;
        
        $category = JTable::getInstance('Category');
        
        if ($category->load($record->catid))
        {
            $params = new JRegistry($category->params);
            $collection = $params->get('jspacedspace_collection');
        }
        
        if (!$collection)
        {
            throw new Exception(JText::_('PLG_CONTENT_JSPACEDSPACE_INVALID_COLLECTION'));
        }
        
        $crosswalk = JSpaceFactory::getCrosswalk($record->get('metadata'), array('name'=>'dspace'));
        $metadata = $crosswalk->walk(true);
        
        $data = new SimpleXMLElement("<request/>");
        $data->collectionId = new SimpleXMLElement("<collectionId>".(int)$collection."</collectionId>");
        $data->metadata = new SimpleXMLElement("<metadata/>");
        
        $i = 0;
        foreach ($metadata as $key=>$field)
        {
            $element = $data->metadata->addChild("field");
            
            foreach ($field as $value)
            {
                $element->name = $key;
                $element->value = $value;
                $i++;
            }
        }
        
        $bundle = $data->addChild("bundles")->addChild("bundle");
            
        $bundle->addChild("name", "ORIGINAL");
        $bitstreams = $bundle->addChild("bitstreams");
        
        foreach (static::$assets as $asset)
        {
            $bitstream = $bitstreams->addChild("bitstream");
            $bitstream->addChild("name", JArrayHelper::getValue($asset, 'name'));
            $bitstream->addChild("mimeType", JArrayHelper::getValue($asset, 'type'));
        }
        
        static::$assets[] = array('name'=>'package.xml', 'data'=>$data->saveXML());
    
        $package = new JArchiveZip();
        $package->create(JPATH_ROOT.'tmp/'.$record->id.'.zip', static::$assets);
        
        return JPATH_ROOT.'/tmp/'.$record->id.'.zip';
    }
    
    /**
     * Deletes a record's file assets from the configured file system.
     *
     * @param   string   $context  The context of the content being passed. Will be com_jspace.asset.
     * @param   JObject  $asset    An instance of the JSpaceAsset class.
     */
	public function onContentBeforeDelete($context, $record)
	{
        if ($context != 'com_jspace.record')
        {
            return true;
        }

        $database = JFactory::getDbo();

        $query = $database->getQuery(true);
        
        $query
            ->select($database->qn('dspace_id'))
            ->from($database->qn('#__jspacedspace_records'))
            ->where($database->qn('record_id').'='.(int)$record->id);
            
        $dspaceId = $database->setQuery($query)->loadResult();
        
        $http = JHttpFactory::getHttp(null, 'curl');
        
        $headers = array(
            'user'=>$this->params->get('username'),
            'pass'=>$this->params->get('password'), 
            'Content-Type'=>'application/json');
        
        $url = new JUri(JString::rtrim($this->params->get('url'), '/').'/items/'.$dspaceId.'.json');
        $response = $http->delete($url, $headers);

        $query = $database->getQuery(true);
        
        $query
            ->delete($database->qn('#__jspacedspace_records'))
            ->where($database->qn('record_id').'='.(int)$record->id)
            ->where($database->qn('dspace_id').'='.(int)$dspaceId);
            
        $database->setQuery($query)->execute();
	}
	
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        $name = $form->getName();
        
        if (!in_array($name, array('com_categories.categorycom_jspace')))
        {
            return true;
        }

        JForm::addFormPath(__DIR__.'/forms');
        $form->loadFile('jspacedspace', false);
        return true;
    }
}