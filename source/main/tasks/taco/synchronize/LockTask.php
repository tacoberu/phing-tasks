<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

require_once 'phing/Task.php';

/**
 * LockTask
 *
 * Zamkne proces dokud nedojde předchozí. Zámek se vytváří souborem.
 *
 * @author Martin Takáč <martin@takac.name>
 * @package phing.tasks.taco
 */
class LockTask extends Task
{


	/**
	 * @return  void
	 * @throws  BuildException
	 */
	function main()
	{
		$filename = $this->prepareLockFilename();
		$f = @fopen($filename, 'x');
		if ($f == False) {
			throw new BuildException("Build process is locked. Check filelock: '{$filename}'.", $this->getLocation());
		}
		fwrite($f, $this->prepareLockContent());
		fclose($f);
	}



	/**
	 *	Vygeneruje název souboru sloužící coby zámek.
	 */
	protected function prepareLockFilename()
	{
		return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phing-' . md5($this->project->getProperty('phing.file')) . '.lock';
	}



	/**
	 *	Vygeneruje obsah souboru sloužící coby zámek.
	 */
	private function prepareLockContent()
	{
		return 'phing.file: ' . $this->project->getProperty('phing.file')
			. "\nphing.version: " . $this->project->getPhingVersion()
			. "\nproject.name: " . $this->project->getName()
			. "\nproject.description: " . $this->project->getDescription()
			. "\n";
	}

}
