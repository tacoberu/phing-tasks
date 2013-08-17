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

require_once "phing/Task.php";



/**
 * HgLogFilesTask
 *
 * Loads a (text) filenames between two revision of hg.
 * Supports filterchains.
 */
class HgLogFilesTask extends Task
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
	private $revFrom;


	/**
	 * Revision of end.
	 */
	private $revTo;


	/**
	 * Property to be set
	 * @var string $property
	 */
	private $property;


	
	/**
	 * filter to be set
	 * @var string $filter
	 */
	private $filter;


	
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
	 * Set filter of files
	 *
	 * @param string $filter
	 * @return this
	 */
	public function setFilter($filter)
	{
		$this->filter = $filter;
		return $this;
	}



	/**
	 * Set file to read
	 * @param PhingFile $file
	 * @return this
	 */
	public function setRevFrom($value)
	{
		$this->revFrom = $value;
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
	 * Zpracovat výstup.
	 *
	 * @return string of regular expresion.
	 */
	protected function filterOutput($output)
	{
		unset($output[count($output) - 1]);
		$ret = array();
		foreach ($output as $row) {
#			$row = preg_replace('~|.*$~', '', trim($row));
			if (preg_match('~([^| ]+)|~', trim($row), $matches) && !empty($matches[0])) {
				if (preg_match('~' . $this->filter . '~', $matches[0], $out)) {
					$ret[] = $out[0];
				}
			}
		}
		return implode(',', $ret);
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
		$this->command .= ' diff --stat';
		$this->command .= ' -r ' . $this->revFrom;
		if ($this->revTo) {
			$this->command .= ' -r ' . $this->revTo;
		}
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
		if (null === $this->revFrom) {
			throw new BuildException('"revFrom" is required parameter');
		}

		$this->prepare();
		list($return, $output) = $this->executeCommand();
		$this->cleanup($return, $output);
	}



}
