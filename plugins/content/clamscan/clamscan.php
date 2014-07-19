<?php
/**
 * @package    Content.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

/**
 * Scans a file using ClamAV server.
 *
 * @package  Content.Plugin
 */
class PlgContentClamscan extends JPlugin
{
    /**
     * @var  string  $_hostname  The clamav host name.
     */
    private $_hostname;
    
    /**
     * @var  string  $_port  The clamav port.
     */
    private $_port;
    
    /**
     * @var  string  $_timeout  The clamav socket time out.
     */
    private $_timeout;

	/**
	 * Instatiates an instance of the PlgContentClamscan class.
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
		
		$this->_hostname = $this->params->get('hostname', '127.0.0.1');
        $this->_port = $this->params->get('port', '3310');
        $this->_timeout = $this->params->get('timeout', null);
    
        if (substr($this->_hostname, 0, 7) == 'unix://')
        {
            $this->_port = -1;
        }
    
        if ($this->_timeout === null)
        {
            $this->_timeout = ini_get("default_socket_timeout");
        }
	}
	
	/**
	 * Scans a file.
	 *
	 * @param   string     $path The path of the file to scan.
	 *
	 * @return  bool       True on success.
	 *
	 * @throw   Exception  When a virus is detected.
	 */
	public function onScan($path)
	{
        $clamav = new JSpaceClamAVClient();

        if ($clamav->scan($path, 'INSTREAM') != 'stream: OK')
        {
            JLog::add(JText::sprintf('PLG_CONTENT_CLAMSCAN_ERROR_VIRUS_DETECTED', $path), JLog::WARNING, 'clamscan');
            throw new Exception(JText::sprintf('PLG_CONTENT_CLAMSCAN_ERROR_VIRUS_DETECTED', $path));
        }

		return true;
	}
	
    /**
     * Opens a socket to the configured Clam AV server.
     *
     * @return  resource  An open file handle.
     */
    private function _open()
    {
        if ($f = fsockopen($this->_hostname, $this->_port, $errno, $errstr, $this->_timeout)) 
        {
            return $f;
        }

        throw new InvalidArgumentException($errstr.' ('.$errno.')', E_USER_ERROR);
    }
    
    /**
     * Reads the output from the configured Clam AV server.
     * 
     * @param  resource  $handle  The opened file handle.  
     */
    private function _read($handle)
    {
        $contents = '';
        
        while (($buffer = fread($handle, 8192)) !== false)
        {
            if(!strlen($buffer))
            { 
                break;
            }

            $contents .= $buffer;
        }

        $array = explode("\0", $contents, 2);

        if(count($array) == 2)
        {
            return $array[0];
        }
        
        throw new InvalidArgumentException('clamd response is not NULL terminated.', E_USER_ERROR);
    }

    /**
     * Pings the Clam AV server.
     * 
     * @return  string  "PONG" on success or false on failure.
     */
    public function ping()
    {
        if ($handle = $this->_open())
        {
            fwrite($handle, "zPING\0");
            $ping = $this->_read($handle);
            fclose($handle);
            
            return $ping;
        }
        
        return false;
    }
    
    /**
     * Requests version information from the Clam AV server.
     * 
     * @return  string  Version information. E.g. "ClamAV 0.95.3/10442/Wed Feb 24 07:09:42 2010".
     */
    function getVersion()
    {
        if ($handle = $this->_open())
        {
            fwrite($handle, "zVERSION\0");
            $version = $this->_read($handle);
            fclose($handle);
            
            return $version;
        }
        
        return false;
    }
    
    /**
     * Scan a file or directory.
     *
     * Only a file can be scanned when using INSTREAM.
     *
     * @param   string  $path  Absolute path (file or directory).
     * @param   string  $mode  One of SCAN/RAWSCAN/CONTSCAN/MULTISCAN/INSTREAM. Default is "MULTI".
     * 
     * @return  string  "path: OK" will be returned if OK. false on failure.
     */
    function scan($path, $mode='MULTI')
    {
        if (!in_array($mode, array('','RAW','CONT','MULTI', 'INSTREAM')))
        {
            throw new InvalidArgumentException("invalid mode ".$mode, E_USER_ERROR);
            return false;
        }
        
        if ($mode == "INSTREAM")
        {
            $reader = fopen($path, "rb");

            if($writer = $this->_open())
            {
                fwrite($writer, "zINSTREAM\0");
                
                while (!feof($reader))
                {
                    $data = fread($reader, 8192);
                    fwrite($writer, pack("N",strlen($data)).$data);
                }
                
                fclose($reader);

                fwrite($writer, pack("N",0)); // chunk termination
                $result = $this->_read($writer);
                fclose($writer);
                
                return $result;
            }
            
            return false;
        }
        
        if ($handle = $this->_open())
        {
            fwrite($handle, "z".$mode."SCAN ".$path."\0");
            $result = $this->_read($handle);
            fclose($handle);
        
            return $result;
        }
        
        return false;
    }
    
    /**
     * issue STATS command
     *
     * @return string The status information of clamd.
     */
    function stat()
    {
        if ($handle = $this->_open())
        {
            fwrite($handle, "zSTATS\0");
            $stats = $this->_read($handle);
            fclose($handle);
            
            return $stats;
        }
        
        return false;
    }
}