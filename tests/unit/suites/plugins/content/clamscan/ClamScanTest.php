<?php
jimport('jspace.clamav.client');

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;

class ClamScanTest extends PHPUnit_Framework_TestCase
{
	const EICAR_TEST = "X5O!P%@AP[4\PZX54(P^)7CC)7}\$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!\$H+H*";

	private $clamav;
	
	public function setUp()
	{
		$this->clamav = new JSpaceClamAVClient();
	}

    public function testPing()
    {
        
		$ping = $this->clamav->ping();
		
		$this->assertEquals($ping, 'PONG');
    }
    
	public function testFileScan()
    {
		$root = vfsStream::setup();
		$largeFile = vfsStream::newFile('large.txt')
			->withContent(LargeFileContent::withKilobytes(100))
            ->at($root);
		 
		$this->assertEquals($this->clamav->scan($largeFile->url(), 'INSTREAM'), 'stream: OK');
    }
    
    public function testVirus()
    {
		$root = vfsStream::setup();
		$largeFile = vfsStream::newFile('large.txt')
			->withContent(LargeFileContent::withKilobytes(100))
            ->at($root);
		
		$handle = fopen($largeFile->url(), 'w');
		fwrite($handle, self::EICAR_TEST, strlen(self::EICAR_TEST));
		fclose($handle);
		
		$this->assertEquals($this->clamav->scan($largeFile->url(), 'INSTREAM'), 'stream: Eicar-Test-Signature FOUND');    
    }
    
    public function testLargeFile()
    {
		$root = vfsStream::setup();
		$largeFile = vfsStream::newFile('large.txt')
			->withContent(LargeFileContent::withMegabytes(100))
            ->at($root);
		
		$handle = fopen($largeFile->url(), 'w');
		fwrite($handle, self::EICAR_TEST, strlen(self::EICAR_TEST));
		fclose($handle);
		
		$this->assertEquals($this->clamav->scan($largeFile->url(), 'INSTREAM'), 'stream: Eicar-Test-Signature FOUND');    
    }
}