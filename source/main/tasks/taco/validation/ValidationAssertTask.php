<?php
/**
 * This file is part of the Taco Projects.
 *
 * Copyright (c) 2004, 2013 Martin Takáč (http://martin.takac.name)
 *
 * For the full copyright and license information, please view
 * the file LICENCE that was distributed with this source code.
 *
 * PHP version 5.3
 *
 * @author     Martin Takáč (martin@takac.name)
 */
 
require_once 'phing/Task.php';

/**
 *  Validation required files, directories and properties.
 *
 * [code]
 *		<validation.assert property="variable.client.database.c.database">Jméno klientské databáze.</validation.assert>
 *		<validation.assert dir="${dir.source.repository}">Umístění zdrojáků.</validation.assert>
 * [/code]
 *
 *  @author   Martin Takáč <martin@takac.name>
 *  @package  phing.tasks.taco
 */
class ValidationAssertTask extends Task
{

	/**
	 * Ověření existence adresáře.
	 */
	const TEST_TYPE_DIR = 'dir';


	/**
	 * Ověření existence property.
	 */
	const TEST_TYPE_PROPERTY = 'property';


	/**
	 * ??
	 */
	private $conditionData;


	/**
	 * Co ověřujeme, property, nebo adresář?
	 * $var enum self::TEST_TYPE_*
	 */
	private $testType;
	
	
	/**
	 * Text chybové hlášky.
	 */
	private $message = "No message";
	
	
	/**
	 * Assert directory exist.
	 * @param $dir Directory name
	 */
	public function setDir(PhingFile $dir)
	{
		$this->conditionData = $dir;
		$this->testType = self::TEST_TYPE_DIR;
	}



	/**
	 * Assert property exist.
	 * @param $dir Directory name
	 */
	public function setProperty($property)
	{
		$this->conditionData = $property;
		$this->testType = self::TEST_TYPE_PROPERTY;
	}



	/**
	 * Supporting the <task>Message</task> syntax. 
	 */
	function addText($msg)
	{
		$this->message = (string) $msg;
	}



	/**
	 * @throws BuildException
	 */
	public function main()
	{
		$method = 'test' . ucfirst($this->testType) . 'Condition';
		if (! $this->$method()) {
			throw new BuildException($this->message);
		}
	}



	/**
	 * @return boolean
	 */
	private function testDirCondition()
	{
        if ($this->conditionData->getCanonicalFile()->isDirectory()) {
			return True;
		}
		$this->message = strtr('Není nastavena cesta k adresáři [%dir%], jehož význam je: [%message%].', array(
			'%message%' => $this->message,
			'%dir%' => $this->conditionData,
		));
		return False;
	}



	/**
	 * @return boolean
	 */
	private function testPropertyCondition()
	{
		if ($this->project->getProperty($this->conditionData)) {
			return True;
		}
		$this->message = strtr('Není nastavena property [%property%], jejíž význam je: [%message%].', array(
			'%message%' => $this->message,
			'%property%' => $this->conditionData,
		));
		return False;
	}


}
