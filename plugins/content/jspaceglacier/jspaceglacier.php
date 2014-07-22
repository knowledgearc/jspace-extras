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
 * The PlgContentJSpaceGlacier plugin is a proof-of-concept extension and is probably not suited 
 * to the requirements of a production environment. Instead, PlgContentJSpaceGlacier plugin 
 * should be re-designed to provide long term storage and restore capabilities to other data 
 * stores and should be run from the terminal as a cron job:
 * E.g. jspace glacier --archive, jspace glacier --restore=[archiveid]
 *
 * @package  JSpace.Plugin
 */
class PlgContentJSpaceGlacier extends JPlugin
{
    protected static $chunksize = 4096;

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
     * Returns the HTML to the JSpace Glacier download mechanism.
     *
     * @param   JSpaceAsset  $asset  An instance of the asset being downloaded.
     * 
     * @return  string       The html to the JSpace Glacier download mechanism.
     */
    public function onJSpaceAssetPrepareDownload($asset)
    {
        JTable::addIncludePath(JPATH_PLUGINS.'/content/jspaceglacier/tables');
        $archive = JTable::getInstance('GlacierArchive', 'JSpaceTable');

        if ($archive->load(array('jspaceasset_id'=>$asset->id)))
        {
            if ($archive->job_id)
            {
                $credentials = new Credentials(
                    $this->params->get('access_key_id'), 
                    $this->params->get('secret_access_key'));
                
                $glacier = GlacierClient::factory(array(
                    'credentials'=>$credentials, 
                    'region'=>$this->params->get('region')));
                
                try
                {
                    $result = $glacier->describeJob(array(
                        'vaultName'=>$archive->vault,
                        'ArchiveId'=>$archive->hash,
                        'jobId'=>$archive->job_id));
                }
                catch (Aws\Glacier\Exception\ResourceNotFoundException $e)
                {
                    // the archive item has expired so force the user to initiate another download.
                    $result = array();
                    $result['StatusCode'] = 'NotAvailable';
                }
                
                switch (JArrayHelper::getValue($result, 'StatusCode'))
                {
                    case 'Succeeded':
                        $asset->url = JRoute::_('index.php?option=com_jspace&task=asset.stream&type=jspaceglacier&id='.$asset->id);
                        
                        $layout = JPATH_PLUGINS.'/content/jspaceglacier/layouts';
                        
                        $html = JLayoutHelper::render("jspaceglaciersucceeded", $asset, $layout);
                        
                        break;
                        
                    case 'Failed':
                        $layout = JPATH_PLUGINS.'/content/jspaceglacier/layouts';
                        
                        $html = JLayoutHelper::render("jspaceglacierfailed", $asset, $layout);
                        
                        break;
                        
                    case 'NotAvailable':
                        $archive->job_id = null;
                        $archive->store(true);
                        
                        JFactory::getApplication()->redirect('index.php?option=com_jspace&view=record&layout=edit&id='.$asset->record_id);
                        break;
                        
                    default: //InProgress
                        $html = 'Archive requested. Please wait...';
                        break;
                }
            }
            else
            {
                $asset->url = JRoute::_('index.php?option=com_jspace&task=asset.stream&type=jspaceglacier&id='.$asset->id);
                
                $layout = JPATH_PLUGINS.'/content/jspaceglacier/layouts';
                
                $html = JLayoutHelper::render("jspaceglacier", $asset, $layout);
            }
        }
        
        return $html;
    }
    
    /**
     * Streams a file from a JSpace Glacier vault to the client's web browser 
     * (or other download mechanism). If the archived file is not available, 
     * a request will be made for its preparation.
     *
     * @param  JSpaceAsset  $asset  An instance of the asset being downloaded.
     */
    public function onJSpaceAssetDownload($asset)
    {
        $credentials = new Credentials(
            $this->params->get('access_key_id'), 
            $this->params->get('secret_access_key'));
        
        JTable::addIncludePath(JPATH_PLUGINS.'/content/jspaceglacier/tables');
        $archive = JTable::getInstance('GlacierArchive', 'JSpaceTable');
        
        if ($archive->load(array('jspaceasset_id'=>$asset->id)))
        {
            $glacier = GlacierClient::factory(array(
                'credentials'=>$credentials, 
                'region'=>$this->params->get('region')));
            
            // initiate a job if none exists for the archive item.
            if ($archive->job_id)
            {
                try
                {
                    $result = $glacier->describeJob(array(
                        'vaultName'=>$archive->vault,
                        'ArchiveId'=>$archive->hash,
                        'jobId'=>$archive->job_id));
                }
                catch (Aws\Glacier\Exception\ResourceNotFoundException $e)
                {
                    // the archive item has expired so force the user to initiate another download.
                    $archive->job_id = null;
                    $archive->store(true);
                    
                    JFactory::getApplication()->redirect('index.php?option=com_jspace&view=record&layout=edit&id='.$asset->record_id);
                }
                
                if (JArrayHelper::getValue($result, 'StatusCode') == 'Succeeded')
                {
                    $result = $glacier->getJobOutput(array(
                        'vaultName'=>$archive->vault,
                        'ArchiveId'=>$archive->hash,
                        'jobId'=>$archive->job_id));
                    
                    // stream out the contents of the archived file.
                    header("Content-Type: ".$asset->getMetadata()->get('contentType'));
                    header("Content-Disposition: attachment; filename=".$asset->getMetadata()->get('fileName').";");
                    header("Content-Length: ".$asset->getMetadata()->get('contentLength'));
                    
                    $stream = JArrayHelper::getValue($result, 'body');
                    $stream->rewind();
                    $buffer = null;
                    
                    while (!$stream->feof())
                    {
                        $buffer = $stream->read(4096);
                        echo $buffer;
                        ob_flush();
                        flush();
                    }
                    
                    $stream->close();

                    return;
                }
            }
            else
            {
            
                $job = $glacier->initiateJob(array(
                    'accountId'=>'-',
                    'vaultName'=>$archive->vault,
                    'Type'=>'archive-retrieval',
                    'ArchiveId'=>$archive->hash
                ));

                $archive->job_id = JArrayHelper::getValue($job, 'jobId');
                $archive->store();
            }
        }
        
        JFactory::getApplication()->redirect('index.php?option=com_jspace&view=record&layout=edit&id='.$asset->record_id);
    }
	
	/**
	 * Validates the Glacier settings.
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
        
		$credentials = new Credentials(
            $this->params->get('access_key_id'), 
            $this->params->get('secret_access_key'));

        JTable::addIncludePath(JPATH_PLUGINS.'/content/jspaceglacier/tables');
        $archive = JTable::getInstance('GlacierArchive', 'JSpaceTable');

        if ($archive->load(array('jspaceasset_id'=>$asset->id)))
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
			$database->qn('jspaceasset_id'));
		
		$query
			->select($columns)
			->from($database->qn('#__jspaceglacier_archives'))
			->where($database->qn('jspaceasset_id').'='.(int)$asset->id);
		
		if ($archive = $database->setQuery($query)->loadObject('JObject'))
		{
			try 
			{
				$result = $glacier->deleteArchive(array('vaultName'=>$archive->vault, 'archiveId'=>$archive->hash));
				
				$query = $database->getQuery(true);
				$query
					->delete($database->qn('#__jspaceglacier_archives'))
					->where($database->qn('jspaceasset_id').'='.(int)$asset->id);
					
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