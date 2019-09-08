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
require_once __dir__ . '/HgBaseTask.php';



/**
 * HgBrancheTask
 *
 * Loads a (text) names of branches between two revision of hg.
 *
 * @package phing.tasks.taco
 */
class Taco_HgBrancheTask extends Taco_HgBaseTask
{


	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'branches';


	/**
	 * Revision of begin.
	 */
	private $revFrom;


	/**
	 * Revision of end.
	 */
	private $revTo;


	/**
	 * Formát výstupu. name, id, changset
	 */
	private $format = '%name%';


	/**
	 * Oddělovač jednotlivých branchí.
	 */
	private $separator = ',';



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
	protected function formatOutput(array $output)
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



}
