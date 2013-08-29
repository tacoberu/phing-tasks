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
 * @call phpunit --bootstrap ../../../bootstrap.php Tests_Unit_Phing_Tasks_Taco_Hg_HgTagTaskTest HgTagTaskTest.php 
 */
class Tests_Unit_Phing_Tasks_Taco_Hg_HgTagTaskTest extends BuildFileTest
{

	private $task;
	

    public function setUp()
    {
		$this->task = new HgTagTask();
    }



	/**
	 * Povinný přiřazení repoisitroy
	 */
	public function _testFailRepositorySet()
	{
		$this->setExpectedException('BuildException', '"repository" is required parameter');
		$this->task
			->main();
	}



	/**
	 * Povinný přiřazení repoisitroy
	 */
	public function _testRepositorySet()
	{
		$mock = $this->getMock('Taco\Utils\Process\Exec', array('setWorkDirectory', 'arg'), array('hg'));
        $mock->expects($this->at(0))
             ->method('setWorkDirectory')
             ->will($this->returnSelf());
        $mock->expects($this->never())
             ->method('arg');

		$this->task
			->setExec($mock)
			->setRepository(new PhingFile($this->getTempDir()))
			->main();
	}



	/**
	 * Povinný přiřazení repoisitroy
	 */
	public function testWithArgumentsFail()
	{
		$this->setExpectedException('BuildException', 'Task exited with code: 42 and output: none');

		$mock = $this->getMock('Taco\Utils\Process\Exec', array('setWorkDirectory', 'arg', 'run'), array('hg'));
        $mock->expects($this->at(0))
             ->method('setWorkDirectory')
             ->will($this->returnSelf());
        $mock->expects($this->at(1))
             ->method('arg')
             ->with("-b def")
             ->will($this->returnSelf());
        $mock->expects($this->at(2))
             ->method('run')
             ->will($this->returnValue((object) array(
             		'code' => 42,
             		'content' => array('none')
             		)));

		$this->task
			->setExec($mock)
			->setRepository(new PhingFile($this->getTempDir()));
		$this->task->createArg()->setName('b')->setValue('def');
		$this->task->main();
	}



	/**
	 * Povinný přiřazení repoisitroy
	 */
	public function testWithArguments()
	{
		$mock = $this->getMock('Taco\Utils\Process\Exec', array('setWorkDirectory', 'arg', 'run'), array('hg'));
        $mock->expects($this->at(0))
             ->method('setWorkDirectory')
             ->will($this->returnSelf());
        $mock->expects($this->at(1))
             ->method('arg')
             ->with("-b def")
             ->will($this->returnSelf());
        $mock->expects($this->at(2))
             ->method('run')
             ->will($this->returnValue((object) array(
             		'code' => 0,
             		'content' => array('none')
             		)));

		$this->task
			->setExec($mock)
			->setRepository(new PhingFile($this->getTempDir()))
			->setProject($this->getMockProject());
		$this->task->createArg()->setName('b')->setValue('def');
		$this->task->main();
	}



	/**
	 * @return string
	 */
	public function getTempDir()
	{
		return realpath(__dir__ . '/../../../../../temp');
	}



	/**
	 * @return mock 
	 */
	public function getMockProject()
	{
		$mock = $this->getMock('Project', array('logObject'));
		return $mock;
	}

}

