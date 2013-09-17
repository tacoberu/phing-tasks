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
 * @author	 Martin Takáč (martin@takac.name)
 */

require_once "phing/Task.php";
require_once __dir__ . '/HgBaseTask.php';



/**
 * HgLogFilesTask
 *
 * Loads a (text) filenames between two revision of hg.
 * /usr/bin/hg diff --stat -r 6006:7086
 *
 * @package phing.tasks.taco
 */
class HgLogFilesTask extends HgBaseTask
{

	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'diff';


	/**
	 * Revision of begin.
	 */
	private $revFrom;


	/**
	 * Revision of end.
	 */
	private $revTo;


	/**
	 * filter to be set
	 * @var string $filter
	 */
	private $filter;



	/**
	 * Default options for ...
	 *
	 * @var array
	 */
	protected $options = array(
			'stat' => Null,
			);



	/**
	 * Array of FilterChain objects
	 * @var FilterChain[]
	 */
	private $filterChains = array();



	/**
	 * Set filter of files - regular expresion.
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
	 * @throw BuildException that not requred params.
	 */
	protected function assertRequiredParams()
	{
		parent::assertRequiredParams();

		if (empty($this->revFrom)) {
			throw new BuildException("revFrom: '" . (int) $this->revFrom . "' is not a valid value.");
		}
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function buildExecute()
	{
		$r = $this->revFrom;
		if ($this->revTo) {
			$r .= ' -r ' . $this->revTo;
		}
		$this->options['r'] = $r;

		return parent::buildExecute();
	}



	/**
	 * Zpracovat výstup. Rozprazsuje řádek, vyfiltruje jej zda je větší jak revize a naformátuje jej do výstupu.
	 *
	 * @param array of string Položky branch + id:hash
	 *
	 * @return string
	 */
	protected function formatOutput(array $output)
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



}
