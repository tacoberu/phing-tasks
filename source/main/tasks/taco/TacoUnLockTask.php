<?php

require_once 'phing/Task.php';

/**
 * Uvolní proces zamčený Lockem.
 *
 * @author Martin Takáč <martin@takac.name>
 */
class TacoUnLockTask extends Task
{

	/**
	 * Kde se bude uchovávat zámek.
	 */
	private $path = null;


	function setDir($dir)
	{
		$this->path = (string) $dir;
	}



	function main()
	{
		$this->getTool()->unlock();
	}



	private function getTool()
	{
		return new Taco\PhingTasks\LockTool($this->project, $this->path);
	}

}
