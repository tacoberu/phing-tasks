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
require_once 'phing/system/io/PhingFile.php';

/**
 * Task ověří existenci adresáře. Pokud neexistuje, vytvoří jej.
 * 
 * <code>
 * <require dir="dirname"/>
 * </code>
 */
class FsRequireTask extends Task
{

	/**
	 * directory to create
	 */
	private $dir;

	
	/**
	 * Mode to create directory with
	 * @var integer
	 */
	private $mode = 0755;



	/**
	 * create the directory and all parents
	 *
	 * @throws BuildException if dir is somehow invalid, or creation failed.
	 */
	function main()
	{
		if ($this->dir === null) {
			throw new BuildException("dir attribute is required", $this->location);
		}
		
		if ($this->dir->isFile()) {
			throw new BuildException("Unable to create directory as a file already exists with that name: " . $this->dir->getAbsolutePath());
		}
		
		if (!$this->dir->exists()) {
			$result = $this->dir->mkdirs($this->mode);
			if (!$result) {
				$msg = "Directory " . $this->dir->getAbsolutePath() . " creation was not successful for an unknown reason";
				throw new BuildException($msg, $this->location);
			}
			$this->log("Created dir: " . $this->dir->getAbsolutePath());
		}
	}



	/**
	 * the directory to create; required. 
	 */
	function setDir(PhingFile $dir)
	{
		$this->dir = $dir;
	}



	/**
	 * Sets mode to create directory with
	 * @param mixed $mode
	 */
	function setMode($mode)
	{
		$this->mode = base_convert((int) $mode, 8, 10);
	}

}
