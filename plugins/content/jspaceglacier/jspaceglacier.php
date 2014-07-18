<?php
/**
 * @package    JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

require_once(JPATH_PLATFORM.'/amazon/aws-autoloader.php');

use Aws\Glacier\GlacierClient;
use Aws\Common\Credentials\Credentials;

jimport('jspace.factory');

/**
 * Manages assets within an Amazon Web Services Glacier vault.
 *
 * @package  JSpace.Plugin
 */
class PlgContentJSpaceGlacier extends JPlugin
{
	static $partSize = 4194304; //4 * 1024 * 1024
	static $concurrency = 3; 

	/**
	 * Instantiates an instance of the PlgContentJSpaceGlacier class.
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
		
		$params = JComponentHelper::getParams('com_jspace', true);
		
		$this->params->loadArray(array('component'=>$params->toArray()));
	}
	
	/**
	 * validates the S3 settings.
	 *
	 * @param  JForm  $form
	 * @param  array  $data
	 * @param  array  $group
	 *
	 * @throw  UnexpectedValueException  If either the vault, region, access key id or secret access key 
	 * are blank.
	 */
	public function onJSpaceRecordAfterValidate($form, $data, $group = null)
	{
		if (!$this->params->get('vault'))
		{
			throw new UnexpectedValueException('AWS vault required.');
		}
		
		if (!$this->params->get('region'))
		{
			throw new UnexpectedValueException('AWS region required.');
		}
	
		if (!$this->params->get('access_key_id'))
		{
			throw new UnexpectedValueException('AWS access key id required.');
		}

		if (!$this->params->get('secret_access_key'))
		{
			throw new UnexpectedValueException('AWS secret access key required.');
		}
	}
	
	/**
	 * Saves an asset to an Amazon Glacier vault.
     *
     * @param   string    $context  The context of the content being passed. Will be com_jspace.asset.
     * @param   JObject   $asset    An instance of the JSpaceAsset class.
     * @param   bool      $isNew    True if the asset being saved is new, false otherwise.
     *
     * @return  bool      True if the asset is successfully saved, false otherwise.
	 *
	 * @throw  Exception  If the asset already exists in the archive.
	 */
	public function onContentAfterSave($context, $asset, $isNew)
	{
        if ($context != 'com_jspace.asset')
        {
            return true;
        }
        
		$credentials = new Credentials($this->params->get('access_key_id'), $this->params->get('secret_access_key')); 
		
		$database = JFactory::getDbo();
		$query = $database->getQuery(true);
		
		$columns = array(
			$database->qn('hash'),
			$database->qn('vault'),
			$database->qn('region'),
			$database->qn('valid'),
			$database->qn('asset_id'));
		
		$query
			->select($columns)
			->from($database->qn('#__jspaceglacier_archives'))
			->where($database->qn('asset_id').'='.(int)$asset->id);
		
		$database->setQuery($query);
		
		if ($database->loadObject())
		{
			throw new Exception('Asset already archived in AWS Glacier.');
		}
		
		try
		{
			$glacier = GlacierClient::factory(array('credentials'=>$credentials, 'region'=>$this->params->get('region')));
			
			$uploader = \Aws\Glacier\Model\MultipartUpload\UploadBuilder::newInstance()
				->setClient($glacier)
				->setSource($asset->tmp_name)
				->setArchiveDescription((string)$asset->getMetadata())
				->setVaultName($this->params->get('vault'))
				->setPartSize(self::$partSize)
				->setConcurrency(self::$concurrency)
				->build();
			
			if ($result = $uploader->upload())
			{
				$values = array(
					$database->q($result->get('archiveId')),
					$database->q($this->params->get('vault')),
					$database->q($this->params->get('region')),
					1,
					(int)$asset->id);
			
				$query = $database->getQuery(true);
				
				$query
					->insert($database->qn('#__jspaceglacier_archives'))
					->columns($columns)
					->values(implode(",", $values));
					
				$database->setQuery($query)->execute();
			}
		} 
		catch(Exception $e)
		{
			JLog::add(__METHOD__.' '.$e->getMessage(), JLog::ERROR, 'jspace');
			throw $e;
		}
	}
	
	/**
	 * Deletes an asset from an Amazon Glacier vault.
	 *
	 * If the asset cannot be found, this method will fail silently.
	 *
     * @param   string   $context  The context of the content being passed. Will be com_jspace.asset.
     * @param   JObject  $asset    An instance of the JSpaceAsset class.
	 */
	public function onContentBeforeDelete($context, $asset)
	{
        if ($context != 'com_jspace.asset')
        {
            return true;
        }
        
		$credentials = new Credentials($this->params->get('access_key_id'), $this->params->get('secret_access_key')); 
		
		$glacier = GlacierClient::factory(array('credentials'=>$credentials, 'region'=>$this->params->get('region')));
		
		$database = JFactory::getDbo();
		$query = $database->getQuery(true);
		
		$columns = array(
			$database->qn('hash'),
			$database->qn('vault'),
			$database->qn('region'),
			$database->qn('valid'),
			$database->qn('asset_id'));
		
		$query
			->select($columns)
			->from($database->qn('#__jspaceglacier_archives'))
			->where($database->qn('asset_id').'='.(int)$asset->id);
		
		if ($archive = $database->setQuery($query)->loadObject('JObject'))
		{
			try 
			{
				$result = $glacier->deleteArchive(array('vaultName'=>$archive->vault, 'archiveId'=>$archive->hash));
				
				$query = $database->getQuery(true);
				$query
					->delete($database->qn('#__jspaceglacier_archives'))
					->where($database->qn('asset_id').'='.(int)$asset->id);
					
				$database->setQuery($query)->execute();
			}
			catch (Exception $e) 
			{
				JLog::add(__METHOD__.' '.$e->getMessage(), JLog::ERROR, 'jspace');
				throw $e;
			}
		}
		else 
		{
			JLog::add(__METHOD__.' '.JText::sprintf('PLG_JSPACE_GLACIER_WARNING_ARCHIVEDOESNOTEXIST', json_encode($asset)), JLog::WARNING, 'jspace');	
		}
	}
}