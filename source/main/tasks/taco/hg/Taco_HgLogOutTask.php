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

require_once __dir__ . '/HgBaseTask.php';


use Taco\Utils\Process;


/**
 * @sample
 * hg out
 *
 * <taco.hg.log.out repository="." output="true" />
 *
 * @package phing.tasks.taco
 */
class Taco_HgLogOutTask extends Taco_HgBaseTask
{

	const FORMAT_RANGE = 'range';

	/**
	 * @var string
	 */
	protected $action = 'out --template "- {rev}\n"';

	/**
	 * Logging level for status messages
	 * @var integer
	 */
	protected $logLevel = Project::MSG_VERBOSE;

	/**
	 * @var string
	 */
	private $format = self::FORMAT_RANGE;


	/**
	 * @param string Enum from 'range'
	 * @return this
	 */
	function setFormat($value)
	{
		$this->format = $value;
		return $this;
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function executeCommand()
	{
		$exec = $this->buildExecute()
			->setWorkDirectory($this->repository->getPath());
		$exec = $this->issetArguments($exec);

		$this->log($exec->dryRun(), Project::MSG_VERBOSE);

		try {
			return $exec->run();

			return (object) [
				'code' => 0,
				'content' => [],
			];

		}
		catch (Process\ExecException $e) {
			if ($e->getCode() === 1 && substr($e->getMessage(), -16) === 'no changes found') {
				return (object) [
					'code' => 0,
					'content' => [],
				];
			}
			$status = $this->catchException($e);
		}
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
		$xs = array();
		foreach ($output as $row) {
			if ($row{0} === '-') {
				$xs[] = trim($row, ' -');
			}
		}

		if (empty($xs)) {
			return null;
		}

		switch ($this->format) {
			case self::FORMAT_RANGE:
			default:
				return min($xs) . ':' . max($xs);
		}
	}


}
