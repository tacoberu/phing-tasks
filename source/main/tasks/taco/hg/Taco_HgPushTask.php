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

require_once "phing/Task.php";
require_once __dir__ . '/HgBaseTask.php';

use Taco\Utils\Process;



/**
 * Push mercurial repository.
 *
 * @sample
 * hg push -b default testing --new-branch -f
 *
 * <hg.push repository="." output="true" branch="default" remote="testing">
 *   <arg name="new-branch"/>
 *   <arg name="-f"/>
 * </hg.push>
 *
 * @package phing.tasks.taco
 */
class Taco_HgPushTask extends Taco_HgBaseTask
{

	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'push';


	/**
	 * @var string
	 */
	protected $remote;



	/**
	 * The setter for the attribute "branch"
	 */
	public function setBranch($str)
	{
		$this->options['b'] = $str;
		return $this;
	}


	/**
	 * The setter for the attribute "remote"
	 */
	public function setRemote($str)
	{
		$this->remote = $str;
		return $this;
	}



	/**
	 * Isset command line arguments for the executable.
	 *
	 * @return Process\Exec
	 */
	protected function issetArguments(Process\Exec $exec)
	{
		if ($this->remote) {
			$exec->arg($this->remote);
		}

		return parent::issetArguments($exec);
	}



	/**
	 * @param Process\ExecException $e
	 * @throw BuildException if code != 0
	 * @return object {code, content}
	 */
	protected function catchException(Process\ExecException $e)
	{
		if ($e->getCode() > 1) {
			parent::catchException($e);
		}

		return (object) array(
				'code' => $e->getCode(),
				'content' => explode(PHP_EOL, $e->getMessage()),
				);
	}


}
