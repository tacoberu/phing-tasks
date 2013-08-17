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
 * @author	 Martin Takáč <taco@taco-beru.name>
 */

require_once 'phing/Task.php';

/**
 * LockTask
 *
 * Zamkne proces dokud nedojde předchozí. Zámek se vytváří souborem.
 *
 * @author Martin Takáč <martin@takac.name>
 * @package phing.tasks.ext
 */
class LockTask extends Task
{


	/**
	 * Main method
	 *
	 * @return  void
	 * @throws  BuildException
	 */
	public function main()
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
