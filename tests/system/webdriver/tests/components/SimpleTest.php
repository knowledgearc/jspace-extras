<?php
require_once 'bootstrap.php';

use SeleniumClient\WebDriver;
use SeleniumClient\By;

class SimpleTest extends PHPUnit_Framework_TestCase
{
	public function testInitiate()
    {
		$webDriver = new WebDriver();
		$webDriver->get("http://localhost/joomla33/administrator");
		
		$webDriver->findElement(By::name("username"))->sendKeys("...");
		$webDriver->findElement(By::name("passwd"))->sendKeys("...");
		$webDriver->findElement(By::cssSelector("button[class='btn btn-primary btn-large']"))->click();
    }
}