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
 * Merguje new messages to exists.
 *
 * @package   phing.tasks.taco
 */
class GettextMergeTask extends MatchingTask
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
	 * Whether to use PHP's passthru() function instead of exec()
	 * @var boolean
	 */
	protected $passthru = false;



	/**
	 * Add a new fileset
	 * @return FileSet
	 */
	function createGettextFileSet()
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
	function createFileSet()
	{
		return $this->createGettextFileSet();
	}




	/**
	 * Set is the name/location of where to create the tar file.
	 * @param PhingFile $destFile The output of the tar
	 */
	function setSource(PhingFile $m)
	{
		$this->file = $m;
	}



	/**
	 * @param
	 */
	function setLanguage($m)
	{
		$this->language = $m;
	}



	/**
	 * This is the base directory to look in for things to tar.
	 * @param PhingFile $baseDir
	 */
	function setBasedir(PhingFile $baseDir)
	{
		$this->baseDir = $baseDir;
	}



	/**
	 * Set the include empty dirs flag.
	 * @param  boolean  Flag if empty dirs should be tarred too
	 * @return void
	 * @access public
	 */
	function setIncludeEmptyDirs($bool)
	{
		$this->includeEmpty = (boolean) $bool;
	}



	/**
	 * do the work
	 * @throws BuildException
	 */
	function main()
	{
		if ($this->file === null) {
			throw new BuildException("po file must be set!", $this->getLocation());
		}

		if ($this->file->exists() && $this->file->isDirectory()) {
			throw new BuildException("po file is a directory!", $this->getLocation());
		}

		if ($this->file->exists() && !$this->file->canWrite()) {
			throw new BuildException("Can not write to the specified po file!", $this->getLocation());
		}

		// shouldn't need to clone, since the entries in filesets
		// themselves won't be modified -- only elements will be added
		$savedFileSets = $this->filesets;

		try {
			if ($this->baseDir !== null) {
				if (!$this->baseDir->exists()) {
					throw new BuildException("Basedir '" . (string) $this->baseDir . "' does not exist!", $this->getLocation());
				}

				// if there weren't any explicit filesets specivied, then
				// create a default, all-inclusive fileset using the specified basedir.
				if (empty($this->filesets)) {
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

			$this->log("Merge po gettext: `{$this->file}'.", Project::MSG_INFO);

			foreach ($this->filesets as $fs) {
				$files = $fs->getFiles($this->project, $this->includeEmpty);
				if (count($files) > 1 && strlen($fs->getFullpath()) > 0) {
					throw new BuildException("Fullpath attribute may only "
											 . "be specified for "
											 . "filesets that specify a "
											 . "single file.");
				}
				$fsBasedir = $fs->getDir($this->project);
				for ($i = 0, $fcount = count($files); $i < $fcount; $i++) {
					$f = new PhingFile($fsBasedir, $files[$i]);
					list ($return, $output) = $this->executeCommand($f->getPath(), $this->file->getPath());
					$this->log("Merging with file: `{$f->getPath()}'.", Project::MSG_VERBOSE);
				}
			}
		}
		catch (IOException $ioe) {
			$msg = "Problem scanning TAR: " . $ioe->getMessage();
			$this->filesets = $savedFileSets;
			throw new BuildException($msg, $ioe, $this->getLocation());
		}

		$this->filesets = $savedFileSets;
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	private function executeCommand($to, $from)
	{
		$args = array();
		$args[] = '-U';
		$args[] = '--no-wrap';
		$args[] = '-q';

		$command = 'msgmerge ' . implode(' ', $args) . ' ' . $to . ' ' . $from;

		$this->log("Executing command: `{$command}'.", Project::MSG_VERBOSE);

		$output = array();
		$return = null;

		if ($this->passthru) {
			passthru($command, $return);
		}
		else {
			exec($command, $output, $return);
		}

		return array($return, $output);
	}



}
