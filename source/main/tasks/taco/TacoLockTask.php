<?php

require_once 'phing/Task.php';

/**
 * Zamkne proces dokud nedojde předchozí. Zámek se vytváří souborem.
 *
 * @author Martin Takáč <martin@takac.name>
 */
class TacoLockTask extends Task
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
		if ($lock = $this->getTool()->lockPid()) {
			throw new BuildException("Build process is locked at `{$lock->created}'; expected end at `{$lock->end}' (expired `{$lock->expired}'). Check filelock: '{$lock->filename}'.", $this->getLocation());
		}
	}



	private function getTool()
	{
		return new Taco\PhingTasks\LockTool($this->project, $this->path);
	}

}
