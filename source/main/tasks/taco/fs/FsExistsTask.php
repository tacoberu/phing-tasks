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
 * Task for resolving relative paths and setting absolute path in property value.
 * 
 * This task was created to address a need for resolving absolute paths of files / directories.
 * In many cases a relative directory (e.g. "./build") is specified, but it needs to be treated
 * as an absolute path since other build files (e.g. in subdirs) should all be using the same
 * path -- and not treating it as a relative path to their own directory.
 * 
 * <code>
 * <property name="relative_path" value="./dirname"/>
 * <resolvepath propertyName="absolute_path" file="${relative_path}"/>
 * <echo>Resolved [absolute] path: ${absolute_path}</echo>
 * </code>
 * 
 * TODO:
 *	  - Possibly integrate this with PackageAsPath, for handling/resolving dot-path paths.
 * 
 * @author	Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 552 $
 * @package   phing.tasks.system
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
