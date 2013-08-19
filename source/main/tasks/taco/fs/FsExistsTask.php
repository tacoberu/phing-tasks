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

/**
 * 
 * 
 * @package   phing.tasks.taco
 */
class FsExistsTask extends Task
{

	/** Name of property to set. */
	private $propertyName;
	
	/** The [possibly] relative file/path that needs to be resolved. */
	private $file;
	
	/** Base directory used for resolution. */
	private $dir;
	
	/**
	 * Set the name of the property to set.
	 * @param string $v Property name
	 * @return void
	 */
	public function setPropertyName($v)
	{
		$this->propertyName = $v;
	}
	
	/**
	 * Sets a base dir to use for resolution.
	 * @param PhingFile $d
	 */
	function setDir(PhingFile $d)
	{
		$this->file = $d;
	}
	


	/**
	 * Sets a file that we want to resolve.
	 * @param string $f
	 */
	function setFile(PhingFile $f)
	{
		$this->file = $f;
	}



	/**
	 * Perform the resolution & set property.
	 */
	public function main()
	{
		if (!$this->propertyName) {
			throw new BuildException("You must specify the propertyName attribute", $this->getLocation());
		}
		
		// Currently only files are supported
		if ($this->file === null) {
			throw new BuildException("You must specify a path to resolve", $this->getLocation());
		}
		
		$fs = FileSystem::getFileSystem();
		
		// if dir attribute was specified then we should
		// use that as basedir to which file was relative.
		// -- unless the file specified is an absolute path
		$date = $fs->getLastModifiedTime($this->file);
		if ($data) {
			$this->project->setProperty($this->propertyName, (bool) $date);
		}		
	}

}
