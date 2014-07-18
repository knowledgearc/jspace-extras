<?php
/**
 * @package    JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

require_once(JPATH_PLATFORM.'/amazon/aws-autoloader.php');

use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials;

jimport('jspace.factory');
jimport('jspace.html.assets');

/**
 * Manages assets on Amazon Web Services S3.
 *
 * @package  JSpace.Plugin
 */
class PlgContentJSpaceS3 extends JPlugin
{
	/**
	 * Instatiates an instance of the PlgContentJSpaceS3 class.
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
	 * @throw  UnexpectedValueException  If either the bucket, access key id or secret access key are blank.
	 */
	public function onJSpaceRecordAfterValidate($form, $data, $group = null)
	{
		if (!$this->params->get('bucket'))
		{
			throw new UnexpectedValueException('AWS bucket required.');
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
     * Saves an asset to an Amazon S3 bucket.
     *
     * @param   string    $context  The context of the content being passed. Will be com_jspace.asset.
     * @param   JObject   $asset    An instance of the JSpaceAsset class.
     * @param   bool      $isNew    True if the asset being saved is new, false otherwise.
     *
     * @return  bool      True if the asset is successfully saved, false otherwise.
     *
     * @throw  Exception  If the asset fails to be written to the S3 bucket.
	 */
	public function onContentAfterSave($context, $asset, $isNew)
	{
        if ($context != 'com_jspace.asset')
        {
            return true;
        }
        
		$credentials = new Credentials($this->params->get('access_key_id'), $this->params->get('secret_access_key')); 
		
		$storage = JSpaceArchiveAssetHelper::buildStoragePath($asset->record_id);
		
		try
		{
			$s3 = S3Client::factory(array('credentials'=>$credentials));
			
			$uploader = \Aws\S3\Model\MultipartUpload\UploadBuilder::newInstance()
				->setClient($s3)
				->setSource($asset->tmp_name)
				->setBucket($this->params->get('bucket'))
				->setKey($storage.sha1_file($asset->tmp_name))
				->setOption('Metadata', $asset->getMetadata()->toArray())
				->setOption('CacheControl', 'max-age=3600')
				->setConcurrency(3)
				->build();
			
			$uploader->upload();
		} 
		catch(Exception $e)
		{
			JLog::add(__METHOD__.' '.$e->getMessage(), JLog::ERROR, 'jspace');
			throw $e;
		}
	}
	
    /**
     * Deletes an asset from an Amazon S3 bucket.
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
		
		$storage = JSpaceArchiveAssetHelper::buildStoragePath($asset->record_id);
		
		$path = $storage.$asset->hash;
		
		$s3 = S3Client::factory(array('credentials'=>$credentials));
		
		if ($s3->doesObjectExist($this->params->get('bucket'), $path))
		{		
			$result = $s3->deleteObject(array('Bucket'=>$this->params->get('bucket'), 'Key'=>$path));	
		}
		else
		{
			JLog::add(__METHOD__.' '.JText::sprintf('PLG_JSPACE_S3_WARNING_OBJECTDOESNOTEXIST', json_encode($asset)), JLog::WARNING, 'jspace');
		}
	}
}