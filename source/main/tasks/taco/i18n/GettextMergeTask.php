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
 * Merge new messages to exists po file.
 *
 * @package   phing.tasks.taco
 */
class GettextMergeTask extends MatchingTask
{

	/**
	 *	Jméno cílového po souboru.
	 */
	private $file;


	/**
	 *	Skupina souborů, které se zmergují do jednoho cílového.
	 */
	private $filesets = array();


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
	 * @param PhingFile $destFile The destination of the po file.
	 */
	function setFile(PhingFile $m)
	{
		$this->file = $m;
		return $this;
	}



	/**
	 * do the work
	 * @throws BuildException
	 */
	function main()
	{
		$this->assertRequiredParams();

		// shouldn't need to clone, since the entries in filesets
		// themselves won't be modified -- only elements will be added
		$savedFileSets = $this->filesets;

		try {
			if (empty($this->filesets)) {
				throw new BuildException("You must supply either a basedir attribute or some nested filesets.", $this->getLocation());
			}

			$this->log("Merge with file: `{$this->file}'.", Project::MSG_INFO);

			//	Posbírat soubory
			$xs = array();
			foreach ($this->filesets as $fs) {
				$files = $fs->getFiles($this->project, False);
				if (count($files) > 1 && strlen($fs->getFullpath()) > 0) {
					throw new BuildException("Fullpath attribute may only be specified for filesets that specify a single file.");
				}
				$basedir = $fs->getDir($this->project);
				foreach ($files as $file) {
					$f = new PhingFile($basedir, $file);
					$xs[] = $f;
				}
			}

			//	Spojit je do jednoho
			$tmpfile = new PhingFile('/', tempnam(sys_get_temp_dir(), 'taco'));
			$tmpfile = $this->doConcatFiles($xs, $tmpfile);

			//	Výsledek zmergnout
			$this->doMergeFiles($tmpfile, $this->file);
			unlink($tmpfile->getPath());

			$this->log("Merged files: `" . implode(', ', array_map(function($f) {
				return $f->getPath();
			}, $xs)) . "'.", Project::MSG_VERBOSE);
		}
		catch (IOException $ioe) {
			$msg = "Problem with merging po files: " . $ioe->getMessage();
			$this->filesets = $savedFileSets;
			throw new BuildException($msg, $ioe, $this->getLocation());
		}

		$this->filesets = $savedFileSets;
	}



	private function doConcatFiles($xs, PhingFile $dest)
	{
		$this->executeCommand('msgcat ' . implode(' ', array_map(function($f) {
			return $f->getPath();
		}, $xs)) . ' -o ' . $dest->getPath());
		return $dest;
	}



	private function doMergeFiles(PhingFile $from, PhingFile $to)
	{
		$args = array();
		$args[] = '-U';
		$args[] = '--no-wrap';
		$args[] = '-q';

		$this->executeCommand('msgmerge ' . implode(' ', $args) . ' ' . $to->getPath() . ' ' . $from->getPath());
		return $to;
	}



	/**
	 * @throw BuildException that not requred params.
	 */
	private function assertRequiredParams()
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
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	private function executeCommand($command)
	{
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
