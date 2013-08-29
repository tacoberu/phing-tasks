<?php
/**
 * Copyright (c) 2004, 2011 Martin Takáč
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author     Martin Takáč <taco@taco-beru.name>
 */

require_once 'phing/BuildFileTest.php';

/**
 * HgUpdateTaskTest
 *
 * Loads a (text) filenames between two revision of hg.
 * Supports filterchains.
 *
 * @call phpunit --bootstrap ../../../bootstrap.php Tests_Unit_Phing_Tasks_Taco_Hg_HgUpdateTaskTest HgUpdateTaskTest.php 
 */
class Tests_Unit_Phing_Tasks_Taco_Hg_HgUpdateTaskTest extends BuildFileTest
{

	private $task;
	

    public function setUp()
    {
		$this->task = new HgUpdateTask();
    }



	/**
	 * Povinný přiřazení repoisitroy
	 */
	public function testFailRepositorySet()
	{
		$this->setExpectedException('BuildException', '"repository" is required parameter');
		$this->task
			->main();
	}



	/**
	 * Povinný přiřazení repoisitroy
	 */
	public function testRepositorySet()
	{
		$mock = $this->getMock('Taco\Utils\Process\Exec', array('update'), array('ab'));
        $mock->expects($this->at(0))
             ->method('setWorkDirectory')
             ->will($this->returnSelf());

		$this->task
			->setExec($mock)
			->setRepository(new PhingFile($this->getTempDir()))
			->main();
	}



	/**
	 * @return string
	 */
	public function getTempDir()
	{
		return realpath(__dir__ . '/../../../../../temp');
	}
}

