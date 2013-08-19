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
 * HgBrancheTask
 *
 * Loads a (text) names of branches between two revision of hg.
 */
class HgBrancheTask extends Task
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
	 * Formát výstupu. name, id, changset
	 */
	private $format = '%name%';


	/**
	 * Oddělovač jednotlivých branchí.
	 */
	private $separator = ',';



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
	public function setRevFrom($value)
	{
		$this->revFrom = (int)$value;
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
	 *	Formát výstupu. Máme nějaké kousky, a z nich můžeme poskládát výstup.
	 *	Seznam placeholdrů:
	 *		%id%	ciselen id changesetu.
	 *		%name%	Jméno branche.
	 *		%changeset%	hexa hash changesetu.
	 *
	 *	@param string 
	 *	@return this
	 */
	public function setFormat($value)
	{
		$this->format = $value;
		return $this;
	}



	/**
	 * Oddělovače jednotlivých branchí.
	 *
	 * @param string 
	 * @return this
	 */
	public function setSeparator($value)
	{
		$this->separator = $value;
		return $this;
	}



	/**
	 * Zpracovat výstup. Rozprazsuje řádek, vyfiltruje jej zda je větší jak revize a naformátuje jej do výstupu.
	 *
	 * @param array of string Položky branch + id:hash
	 *
	 * @return string
	 */
	protected function filterOutput($output)
	{
		$ret = array();
		$this->log('Filter for: ' . $this->revFrom, Project::MSG_VERBOSE);
		foreach ($output as $row) {
			if (preg_match('~([^\s]+)\s+(\d+)\:([\d\w]+)~', $row, $matches)) {
				if ($matches[2] >= $this->revFrom) {
					//	Mapování
					$mask = array();
					$mask['%id%'] = $matches[2];
					$mask['%name%'] = $matches[1];
					$mask['%changeset%'] = $matches[3];

					//	přiřazení
					$this->log('add:  ' . $row, Project::MSG_VERBOSE);
					$ret[] = strtr($this->format, $mask);
				}
				else {
					$this->log('skip: ' . $row, Project::MSG_VERBOSE);
				}
			}
		}
		return implode($this->separator, $ret);
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

		$this->command .= ' branches';
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
		
#		if ($this->passthru) {
#			passthru($this->command, $return);
#		}
#		else {
			exec($this->command, $output, $return);
#		}
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
