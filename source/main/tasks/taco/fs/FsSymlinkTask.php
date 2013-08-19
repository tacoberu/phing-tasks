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

/**
 * Generates symlinks based on a target / link combination.
 * Can also symlink contents of a directory, individually.
 * Pokud link už existuje a je správný, ignoruje.
 * Pokud link už existuje a je nesprávný, hlásí a přepisuje.
 *
 * Single target symlink example:
 * <code>
 *	 <symlink target="/some/shared/file" link="${project.basedir}/htdocs/my_file" />
 * </code>
 *
 * Symlink entire contents of directory
 *
 * This will go through the contents of "/my/shared/library/*"
 * and create a symlink for each entry into ${project.basedir}/library/
 * <code>
 *	 <symlink link="${project.basedir}/library">
 *		 <fileset dir="/my/shared/library">
 *			 <include name="*" />
 *		 </fileset>
 *	 </symlink>
 * </code>
 * 
 * @author Andrei Serdeliuc <andrei@serdeliuc.ro>
 * @author Martin Takáč <taco@taco-beru.name>
 * @package phing.tasks.taco
 */
class FsSymlinkTask extends Task
{
	/**
	 * What we're symlinking from
	 * 
	 * (default value: null)
	 * 
	 * @var string
	 * @access private
	 */
	private $_target = null;
	
	/**
	 * Symlink location
	 * 
	 * (default value: null)
	 * 
	 * @var string
	 * @access private
	 */
	private $_link = null;
	
	/**
	 * Collection of filesets
	 * Used when linking contents of a directory
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access private
	 */
	private $_filesets = array();
	
	/**
	 * setter for _target
	 * 
	 * @access public
	 * @param string $target
	 * @return void
	 */
	public function setTarget($target)
	{
		$this->_target = $target;
	}
	
	/**
	 * setter for _link
	 * 
	 * @access public
	 * @param string $link
	 * @return void
	 */
	public function setLink($link)
	{		
		$this->_link = $link;
	}
	
	/**
	 * creator for _filesets
	 * 
	 * @access public
	 * @return FileSet
	 */
	public function createFileset()
	{
		$num = array_push($this->_filesets, new FileSet());
		return $this->_filesets[$num-1];
	}
	
	/**
	 * getter for _target
	 * 
	 * @access public
	 * @return string
	 */
	public function getTarget()
	{
		if($this->_target === null) {
			throw new BuildException('Target not set');
		}
		
		return $this->_target;
	}
	
	/**
	 * getter for _link
	 * 
	 * @access public
	 * @return string
	 */
	public function getLink()
	{
		if($this->_link === null) {
			throw new BuildException('Link not set');
		}
		
		return $this->_link;
	}
	
	/**
	 * getter for _filesets
	 * 
	 * @access public
	 * @return array
	 */
	public function getFilesets()
	{
		return $this->_filesets;
	}
	
	/**
	 * Generates an array of directories / files to be linked
	 * If _filesets is empty, returns getTarget()
	 * 
	 * @access protected
	 * @return array|string
	 */
	protected function getMap()
	{
		$fileSets = $this->getFilesets();
		
		// No filesets set
		// We're assuming single file / directory
		if(empty($fileSets)) {
			return $this->getTarget();
		}
	
		$targets = array();
		
		foreach($fileSets as $fs) {
			if(!($fs instanceof FileSet)) {
				continue;
			}
			
			// We need a directory to store the links
			if(!is_dir($this->getLink())) {
				throw new BuildException('Link must be an existing directory when using fileset');
			}
			
			$fromDir = $fs->getDir($this->getProject())->getAbsolutePath();

			if(!is_dir($fromDir)) {
				$this->log('Directory doesn\'t exist: ' . $fromDir, Project::MSG_WARN);
				continue;
			}
			
			$fsTargets = array();
			
			$ds = $fs->getDirectoryScanner($this->getProject());
			
			$fsTargets = array_merge(
				$fsTargets,
				$ds->getIncludedDirectories(),
				$ds->getIncludedFiles()
			);
			
			// Add each target to the map
			foreach($fsTargets as $target) {
				if(!empty($target)) {
					$targets[$target] = $fromDir . DIRECTORY_SEPARATOR . $target;
				}
			}
		}
		
		return $targets;
	}



	/**
	 * Main entry point for task
	 * 
	 * @access public
	 * @return bool
	 */
	public function main()
	{
		$map = $this->getMap();
		
		// Single file symlink
		if(is_string($map)) {
			return $this->symlink($map, $this->getLink());
		}
		
		// Multiple symlinks
		foreach($map as $name => $targetPath) {
			$this->symlink($targetPath, $this->getLink() . DIRECTORY_SEPARATOR . $name);
		}
		
		return true;
	}



	/**
	 * Create the actual link
	 * 
	 * @access protected
	 * @param string $target
	 * @param string $link
	 * @return bool
	 */
	protected function symlink($target, $link)
	{
		$fs = FileSystem::getFileSystem();

		if (file_exists($link) && !is_link($link)) {
			return True;
		}
		$msg = '';
		$target = realpath($target);
		if (is_link($link) && $rl = readlink($link)) {
			if ($rl == $target) {
				return True;
			}
			
			//	Odstranit původní link.
			$fs->unlink($link);
			$msg = ' (replace)';
		}
		
		$this->log("Linking$msg:" . $target . ' to ' . $link, Project::MSG_INFO);
		return $fs->symlink($target, $link);
	}
}
