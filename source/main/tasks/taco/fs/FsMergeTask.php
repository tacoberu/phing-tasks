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
require_once 'phing/util/DataStore.php';
require_once 'phing/system/io/FileWriter.php';

/**
 * Append missing files from other directories.
 *
 * 	<taco.merge level="verbose" method="append" outputProperty="msg">
 * 		<fileset dir="${dir.repository}/persistence/">
 * 			<include name="s/mysql/1/update"/>
 * 			<include name="u/mysql/1/update"/>
 * 			<include name="t/mysql/1/update"/>
 * 		</fileset>
 * 	</taco.merge>
 *
 * @package  phing.tasks.taco
 */
class FsMergeTask extends Task
{

	const METHOD_APPEND = 'append';

	/**
	 *	all fileset objects assigned to this task
	 */
	protected $filesets = array();

	/**
	 *	a instance of fileutils
	 */
    protected $fileUtils = Null;

	/**
	 *	mode to create directories with
	 */
    protected $mode = 0;

	/**
	 *	all filterchains objects assigned to this task
	 */
    protected $filterChains = array();


    /**
     * Property name to set with output value from exec call.
     *
     * @var string
     */
    protected $outputProperty;


	protected $errorProperty;

	protected $haltOnFailure = False;

	protected $hasErrors = False;

	protected $badFiles = array();

	protected $logLevel = Project::MSG_VERBOSE;

	protected $method = Null;



    /**
     * Sets up this object internal stuff. i.e. the Fileutils instance and default mode
     *
     * @return object   The CopyTask instnace
     * @access public
     */
    function __construct()
    {
        $this->fileUtils = new FileUtils();
        $this->mode = 0777 - umask();
    }



	/**
	 * The haltonfailure property
	 * @param boolean $aValue
	 */
	function setHaltOnFailure($aValue)
	{
		$this->haltOnFailure = $aValue;
	}



	/**
	 * Set an property name in which to put any errors.
	 * @param string $propname
	 */
	function setErrorproperty($propname)
	{
		$this->errorProperty = $propname;
	}



	/**
	 * Whether to store last-modified times in cache
	 *
	 * @param PhingFile $file
	 */
	function setMethod($method)
	{
		$this->method = $method;
	}



    /**
     * The name of property to set to output value from exec() call.
     *
     * @param string $prop Property name
     *
     * @return void
     */
    function setOutputProperty($prop)
    {
        $this->outputProperty = $prop;
    }



	/**
	 * Nested creator, creates a FileSet for this task
	 *
	 * @return FileSet The created fileset object
	 */
	function createFileSet()
	{
		$num = array_push($this->filesets, new FileSet());
		return $this->filesets[$num-1];
	}



    /**
     * Creates a filterchain
     *
     * @access public
     * @return  object  The created filterchain object
     */
    function createFilterChain()
    {
        $num = array_push($this->filterChains, new FilterChain($this->project));
        return $this->filterChains[$num-1];
    }



	/**
	 * Set level of log messages generated (default = info)
	 * @param string $level
	 */
	function setLevel($level)
	{
		switch ($level) {
			case "error": $this->logLevel = Project::MSG_ERR; break;
			case "warning": $this->logLevel = Project::MSG_WARN; break;
			case "info": $this->logLevel = Project::MSG_INFO; break;
			case "verbose": $this->logLevel = Project::MSG_VERBOSE; break;
			case "debug": $this->logLevel = Project::MSG_DEBUG; break;
		}
	}



	/**
	 * Execute
	 */
	function main()
	{
		if(!isset($this->file) and count($this->filesets) == 0) {
			throw new BuildException("Missing either a nested fileset or attribute 'file' set");
		}

		$project = $this->getProject();
		$acts = array();
		foreach($this->filesets as $fs) {
			$ds = $fs->getDirectoryScanner($project);
			$dirs = $ds->getIncludedDirectories();

			$lists = array();
			$base = $fs->getDir($this->project)->getPath();
			foreach ($dirs as $dir) {
				$lists[$dir] = $this->scan($base . DIRECTORY_SEPARATOR . $dir);
			}

			// Projít co kde chybí
			foreach ($lists as $dirfrom => $files) {
				foreach ($files as $file) {
					foreach ($lists as $dirto => $list) {
						if (!in_array($file, $list)) {
							$acts[] = array($base, $dirfrom, $dirto, $file);
						}
					}
				}
			}
		}

		// Uplatnint změny
		$logs = array();
		foreach ($acts as $m) {
			list($base, $dirfrom, $dirto, $file) = $m;
			$this->copy($base . DIRECTORY_SEPARATOR . $dirfrom . DIRECTORY_SEPARATOR . $file,
					$base . DIRECTORY_SEPARATOR . $dirto . DIRECTORY_SEPARATOR . $file
					);
			if (!isset($logs[$dirfrom])) {
				$logs[$dirfrom] = array();
			}
			$logs[$dirfrom][] = $dirto . DIRECTORY_SEPARATOR . $file;
		}


        $outloglevel = $this->logOutput ? Project::MSG_INFO : Project::MSG_VERBOSE;
        $this->log($this->formatOutput($logs), $outloglevel);

        if ($this->outputProperty) {
            $this->project->setProperty(
                $this->outputProperty, $this->formatOutput($logs)
            );
        }
	}



	/**
	 * @param array of array of string Pole názvů souborů.
	 *
	 * @return string
	 */
	private function formatOutput(array $outs)
	{
		$return = array();
		foreach ($outs as $key => $files) {
			$return[] = '`' . $key . '\' -> files: [' . implode(', ', $files) . ']';
		}
		return 'Merged: ' . implode('; ', $return) . '.';
	}



	/**
	 * @param string $path Cesta k adrsáři.
	 *
	 * @return array of string Seznam souborů.
	 */
	private function scan($path)
	{
		if (!is_readable($path)) {
            $this->logError("Path " . $path . " is not readable.");
			return;
		}

		$newfiles = self::listDir($path);

		return $newfiles;
	}



	/**
	 * Lists contens of a given directory and returns array with entries
	 *
	 * @param   src String. Source path and name file to copy.
	 *
	 * @access  public
	 * @return  array  directory entries
	 * @author  Albert Lash, alash@plateauinnovation.com
	 */
	private function listDir($_dir)
	{
		$d = dir($_dir);
		$list = array();
		while(($entry = $d->read()) !== false) {
			if ($entry != "." && $entry != "..") {
				$list[] = $entry;
			}
		}
		$d->close();
		return $list;
	}



	/**
	 * Copy src to desc
	 *
	 * @param   string src Source from copy
	 * @param   string desc Destination to copy
	 */
	private function copy($from, $to)
	{
		$fromFile = new PhingFile($from);
		$toFile = new PhingFile($to);
		try {
			$this->fileUtils->copyFile($fromFile, $toFile, False, False, $this->filterChains, $this->getProject(), $this->mode);
        }
        catch (IOException $ioe) {
            $this->logError("Failed to copy " . $from . " to " . $to . ": " . $ioe->getMessage());
        }
	}





}
