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
 * HgLogLastrevTask
 *
 * @package phing.tasks.taco
 */
class HgTagLastTask extends Task
{

	/**
	 *	Kde najdem program.
	 */
	const BIN = '/usr/bin/hg';


	/**
	 * repository.
	 */
	private $repository;


	/**
	 * Revision of begin.
	 */
	private $format;


	/**
	 * Property to be set
	 * @var string $property
	 */
	private $property;


	
	/**
	 * Array of FilterChain objects
	 * @var FilterChain[]
	 */
	private $filterChains = array();


	/**
	 * Set repository directory
	 *
	 * @param string $repository Repo directory
	 * @return this
	 */
	public function setRepository(PhingFile $repository)
	{
		$this->repository = $repository;
		return $this;
	}



	/**
	 * Set file to read
	 * @param PhingFile $file
	 * @return this
	 */
	public function setFormat($value)
	{
		$this->format = $value;
		return $this;
	}



	/**
	 * Set name of property to be set
	 * @param $property
	 * @return this
	 */
	public function setProperty($property)
	{
		$this->property = $property;
		return $this;
	}



	/**
	 * Creates a filterchain
	 *
	 * @return  object  The created filterchain object
	 */
	function createFilterChain()
	{
		$num = array_push($this->filterChains, new FilterChain($this->project));
		return $this->filterChains[$num-1];
	}



	/**
	 * Zpracovat výsutp.
	 *
	 * @return string
	 */
	protected function filterOutput($output)
	{
		$mask = array();
		foreach ($output as $row) {
			$row = explode(':', $row, 2);
			if (count($row) == 2) {
				$mask[$row[0]] = trim($row[1]);
			}
		}
		$tmp = explode(':', $mask['changeset'], 2);
		$mask['id'] = $tmp[0];
		$mask['changeset'] = $tmp[1];
		return strtr($this->format, $mask);
	}



	/**
	 * Prepares the command building and execution, i.e.
	 * changes to the specified directory.
	 *
	 * @return void
	 */
	protected function prepare()
	{
		if ($this->repository === null) {
			return;
		}

		// expand any symbolic links first
		if (!$this->repository->getCanonicalFile()->isDirectory()) {
			throw new BuildException(
				"'" . (string) $this->repository . "' is not a valid directory"
			);
		}
		$this->currdir = getcwd();
		@chdir($this->repository->getPath());
		$this->command = self::BIN;
		$this->command .= ' tags -q';
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function executeCommand()
	{
		$output = array();
		$return = null;
		
		if ($this->passthru) {
			passthru($this->command, $return);
		}
		else {
			exec($this->command, $output, $return);
		}
		$this->log('Executing command: ' . $this->command, Project::MSG_VERBOSE);

		return array($return, $output);
	}



	/**
	 * Runs all tasks after command execution:
	 * - change working directory back
	 * - log output
	 * - verify return value
	 *
	 * @param integer $return Return code
	 * @param array   $output Array with command output
	 *
	 * @return void
	 */
	protected function cleanup($return, $output)
	{
		if ($this->repository !== null) {
			@chdir($this->currdir);
		}

		if ($return != 0) {
			throw new BuildException("Task exited with output $output");
		}

		$output = $this->filterOutput($output);

		if ($this->property) {
			$this->project->setProperty($this->property, $output);
		}

	}



	/**
	 * The main entry point method.
	 */
	public function main()
	{
		if (null === $this->repository) {
			throw new BuildException('"repository" is required parameter');
		}
		if (null === $this->format) {
			throw new BuildException('"revFrom" is required parameter');
		}

		$this->prepare();
		list($return, $output) = $this->executeCommand();
		$this->cleanup($return, $output);
	}



}
