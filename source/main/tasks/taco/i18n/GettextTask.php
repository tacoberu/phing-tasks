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

require_once __dir__ . '/GettextExtractor.php';
require_once __dir__ . '/GettextFileSet.php';

require_once 'phing/tasks/system/MatchingTask.php';
include_once 'phing/util/SourceFileScanner.php';
include_once 'phing/mappers/MergeMapper.php';
include_once 'phing/util/StringHelper.php';

/**
 * @package   phing.tasks.taco
 */
class GettextTask extends MatchingTask
{

	/**
	 *	Jméno projektu.
	 */
	private $file;

	/**
	 *	Cesta k souborům.
	 */
	private $path;

	/**
	 *
	 */
	private $type = 'LC_MESSAGES';

	/**
	 *	Jméno lokalizace
	 */
	private $language = 'en_GB';

	private $baseDir;

	private $includeEmpty = true; // Whether to include empty dirs in the TAR

	private $filesets = array();
	private $fileSetFiles = array();



	/**
	 * Add a new fileset
	 * @return FileSet
	 */
	private function createPoFile()
	{
//		$file = $this->path
//			. DIRECTORY_SEPARATOR . $this->language
//			. DIRECTORY_SEPARATOR . $this->type
//			. DIRECTORY_SEPARATOR . $this->file
//			. '.po';
		return new PhingFile($this->file);
	}



	/**
	 * Add a new fileset
	 * @return FileSet
	 */
	public function createGettextFileSet()
	{
		$this->fileset = new GettextFileSet();
		$this->filesets[] = $this->fileset;
		return $this->fileset;
	}



	/**
	 * Add a new fileset.  Alias to createGettextFileSet() for backwards compatibility.
	 * @return FileSet
	 * @see createGettextFileSet()
	 */
	public function createFileSet()
	{
		return $this->createGettextFileSet();
	}



	/**
	 * Set is the name/location of where to create the tar file.
	 * @param PhingFile $destFile The output of the tar
	 */
	public function setFile($m)
	{
		$this->file = $m;
	}



	/**
	 * @param
	 */
	public function setLanguage($m)
	{
		$this->language = $m;
	}



	/**
	 * This is the base directory to look in for things to tar.
	 * @param PhingFile $baseDir
	 */
	public function setBasedir(PhingFile $baseDir)
	{
		$this->baseDir = $baseDir;
	}



	/**
	 * Set the include empty dirs flag.
	 * @param  boolean  Flag if empty dirs should be tarred too
	 * @return void
	 * @access public
	 */
	public function setIncludeEmptyDirs($bool)
	{
		$this->includeEmpty = (boolean) $bool;
	}



	/**
	 * do the work
	 * @throws BuildException
	 */
	public function main()
	{
		$file = $this->createPoFile();

		if ($file === null) {
			throw new BuildException("po file must be set!", $this->getLocation());
		}

		if ($file->exists() && $file->isDirectory()) {
			throw new BuildException("po file is a directory!", $this->getLocation());
		}

		if ($file->exists() && !$file->canWrite()) {
			throw new BuildException("Can not write to the specified po file!", $this->getLocation());
		}

		if (!$file->getParentFile()->exists()) {
			if (!$file->getParentFile()->getParentFile()->exists()) {
				$file->getParentFile()->getParentFile()->mkdir();
			}
			$file->getParentFile()->mkdir();
		}

		// shouldn't need to clone, since the entries in filesets
		// themselves won't be modified -- only elements will be added
		$savedFileSets = $this->filesets;

		try {
			if ($this->baseDir !== null) {
				if (!$this->baseDir->exists()) {
					throw new BuildException("basedir '" . (string) $this->baseDir . "' does not exist!", $this->getLocation());
				}
				if (empty($this->filesets)) { // if there weren't any explicit filesets specivied, then
											  // create a default, all-inclusive fileset using the specified basedir.
					$mainFileSet = new GettextFileSet($this->fileset);
					$mainFileSet->setDir($this->baseDir);
					$this->filesets[] = $mainFileSet;
				}
			}

			if (empty($this->filesets)) {
				throw new BuildException("You must supply either a basedir "
										 . "attribute or some nested filesets.",
										 $this->getLocation());
			}

			$this->log("Building po gettext: " . $file->__toString(), Project::MSG_INFO);

			$tar = new \Taco\Tools\Gettext\GettextExtractor(/* $this->project->getBasedir() */);

			foreach ($this->filesets as $fs) {
				$files = $fs->getFiles($this->project, $this->includeEmpty);
				if (count($files) > 1 && strlen($fs->getFullpath()) > 0) {
					throw new BuildException("fullpath attribute may only "
											 . "be specified for "
											 . "filesets that specify a "
											 . "single file.");
				}
				$fsBasedir = $fs->getDir($this->project);
				for ($i = 0, $fcount = count($files); $i < $fcount; $i++) {
					$f = new PhingFile($fsBasedir, $files[$i]);
					$this->log("Scanning file " . $f->getPath() . '.', Project::MSG_VERBOSE);
					$tar->scan($f);
				}
			}

			//	Uložit soubor.
			$tar->save($file->getAbsolutePath());
		}
		catch (IOException $ioe) {
			$msg = "Problem scanning TAR: " . $ioe->getMessage();
			$this->filesets = $savedFileSets;
			throw new BuildException($msg, $ioe, $this->getLocation());
		}

		$this->filesets = $savedFileSets;
	}


}
