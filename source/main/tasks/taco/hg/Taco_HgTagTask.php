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
require_once __dir__ . '/HgBaseTask.php';


/**
 * HgTagTask
 *
 * Loads a (text) names of tags between two revision of hg.
 *
 * @package phing.tasks.taco
 */
class Taco_HgTagTask extends Taco_HgBaseTask
{

	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'tags';


	/**
	 * Revision of begin.
	 */
	private $revFrom;


	/**
	 * Revision of end.
	 */
	private $revTo;


	/**
	 * Počet záznamů.
	 */
	private $limit = Null;


	/**
	 * Posunutí.
	 */
	private $offset = 0;


	/**
	 * Formát výstupu. name, id, changset
	 */
	private $format = '%name%';


	/**
	 * Oddělovač jednotlivých branchí.
	 */
	private $separator = ',';


	/**
	 * Vyfiltrovat nějaké tagy.
	 */
	private $filter;


	/**
	 * Které tagy vyhazujem.
	 */
	private $exclude = array(
			'tip'
			);


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
	 * Set ...
	 * @return this
	 */
	public function setRevFrom($value)
	{
		$this->revFrom = (int)$value;
		return $this;
	}



	/**
	 * Set filter of name tag.
	 * @return this
	 */
	public function setFilter($value)
	{
		$this->filter = $value;
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
	 * Kolik tagů nás zajímá.
	 *
	 * @param string
	 * @return this
	 */
	public function setLimit($value)
	{
		$this->limit = $value;
		return $this;
	}



	/**
	 * -b default
	 *
	 * @param string
	 * @return this
	 */
	public function setBranch($value)
	{
		$this->options['b'] = $value;
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
		foreach ($output as $row) {
			if (preg_match('~([^\s]+)\s+(\d+)\:([\d\w]+)~', $row, $matches)) {
				if ($this->revFrom && $matches[2] < $this->revFrom) {
					$this->log("skip by rev-from: $row ({$matches[2]}) < {$this->revFrom}", Project::MSG_DEBUG);
					continue;
				}

				if ($this->filter && ! preg_match('~^' . $this->filter . '$~i', $matches[1])) {
					$this->log("skip by filter: `{$matches[1]}` != `{$this->filter}`.", Project::MSG_DEBUG);
					continue;
				}

				if (in_array($matches[1], $this->exclude)) {
					$this->log("discard: $row", Project::MSG_DEBUG);
					continue;
				}

				//	Mapování
				$mask = array();
				$mask['%id%'] = $matches[2];
				$mask['%name%'] = $matches[1];
				$mask['%changeset%'] = $matches[3];

				//	přiřazení
				$this->log('add:  ' . $row, Project::MSG_VERBOSE);
				$ret[] = strtr($this->format, $mask);
			}
		}

		//	Vyříznout jen určitý počet
		$ret = array_slice($ret, $this->offset, $this->limit);

		return implode($this->separator, $ret);
	}



}
