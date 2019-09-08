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
 * HgLogLastrevTask
 *
 * <hg.log.lastrev format="id" repository="${dir.source.repository}" property="rev-actual" branch="default" />
 *
 * Loads last revision of mercurial repozitory.
 * Supports filterchains.
 *
 * @package phing.tasks.taco
 */
class Taco_HgLogLastrevTask extends Taco_HgBaseTask
{

	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'log';


	/**
	 * Revision of begin.
	 */
	private $format;


	/**
	 * @var string
	 */
	protected $branch;



	/**
	 * Array of FilterChain objects
	 * @var FilterChain[]
	 */
	private $filterChains = array();





	/**
	 * The setter for the attribute "branch"
	 */
	public function setBranch($str)
	{
		$this->branch = $str;
		return $this;
	}



	/**
	 * @return this
	 */
	public function setFormat($value)
	{
		$this->format = $value;
		return $this;
	}



	/**
	 * Creates a filterchain
	 *
	 * @return  object  The created filterchain object
	 * @TODO
	 */
	function createFilterChain()
	{
		$num = array_push($this->filterChains, new FilterChain($this->project));
		return $this->filterChains[$num-1];
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function buildExecute()
	{
		if (isset($this->branch)) {
			$this->action = 'log';
			$this->options['l'] = '1';
			$this->options['b'] = $this->branch;
		}
		else {
			$this->action = 'tip';
		}

		$exec = parent::buildExecute();
		return $exec;
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




}
