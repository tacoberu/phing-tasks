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

require_once __dir__ . '/HgBaseTask.php';



use Taco\Utils\Process;


/**
 * Update mercurial repository to last revision, or last revision of branch.
 *
 * @sample
 * hg update default
 *
 * <hg.update repository="." output="true" branch="default" />
 *
 * @package phing.tasks.taco
 */
class Taco_HgUpdateTask extends Taco_HgBaseTask
{

	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'update';


	/**
	 * @var string
	 */
	protected $branch;


	/**
	 * The setter for the attribute "branch"
	 */
	public function setBranch($str)
	{
		$this->branch = $str;
		return $this;
	}



	/**
	 * Isset command line arguments for the executable.
	 *
	 * @return Process\Exec
	 */
	protected function issetArguments(Process\Exec $exec)
	{
		if ($this->branch) {
			$exec->arg($this->branch);
		}

		return parent::issetArguments($exec);
	}


}
