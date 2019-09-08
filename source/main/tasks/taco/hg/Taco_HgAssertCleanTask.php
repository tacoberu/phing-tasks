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
require_once __dir__ . '/Taco_HgStatusTask.php';


/**
 *  Validation
 *
 * [code]
 *		<hg.assert-clean repository="${dir.source.repository}">Prázdný repozitář.</hg.assert>
 * [/code]
 *
 *  @author   Martin Takáč <martin@takac.name>
 *  @package  phing.tasks.taco
 */
class Taco_HgAssertCleanTask extends Taco_HgStatusTask
{

	/**
	 * Text chybové hlášky.
	 */
	private $message = "No message";


	/**
	 * Supporting the <task>Message</task> syntax.
	 */
	function addText($msg)
	{
		$this->message = (string) $msg;
	}




	/**
	 * The main entry point method.
	 */
	public function main()
	{
		if (null === $this->repository) {
			throw new BuildException('"repository" is required parameter');
		}

		$status = $this->executeCommand();

		if ($status->content) {
			$message = strtr('Repo: [%repository%] is not clean. %message%', array(
				'%message%' => $this->message,
				'%repository%' => $this->repository,
			));

			throw new BuildException($message);
		}
	}


}
