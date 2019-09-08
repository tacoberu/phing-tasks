<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

require_once 'phing/Task.php';
require_once 'phing/util/FileUtils.php';


/**
 * Lookup first rev from one branch.
 *
 * hg log -b cesys-608 -r : -l 1 --template="{rev}"
 *
 * @author   Martin Takáč <martin@takac.name>
 * @package  phing.tasks.taco
 */
class Taco_HgLogFirstrevTask extends Taco_HgBaseTask
{

	/**
	 * @var string
	 */
	protected $action = 'log';


	/**
	 * @var string
	 */
	private $branch;


	/**
	 * @var string
	 */
	private $format = 'id';


	function setBranch($val)
	{
		$this->branch = $val;
		return $this;
	}



	function setFormat($val)
	{
		$this->format = $val;
		return $this;
	}



	/**
	 * @throw BuildException that not requred params.
	 */
	protected function assertRequiredParams()
	{
		parent::assertRequiredParams();

		if (empty($this->branch)) {
			throw new BuildException("branch is missing.");
		}
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function buildExecute()
	{
		$this->options['b'] = $this->branch;
		$this->options['r'] = ':';
		$this->options['l'] = '1';
		$this->options['template'] = '{rev}';

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
		return reset($output);
	}

}
