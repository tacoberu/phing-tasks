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
require_once __dir__ .  "/SchemaManageBaseTask.php";



/**
 * Get status of versioning database.
 *
 * [code]
 *		<schemamanage.status
 *				database="dbname"
 *				dir="${dir.source.persistence}"
 *				logoutput="true"
 *				/>
 * [/code]
 *
 * @package   phing.tasks.taco
 */
class SchemaManageStatusTask extends SchemaManageBaseTask
{


    /**
     * Action to execute.
     * @var string
     */
    protected $action = 'status';



	/**
	 * Zpracovat výstup. Rozprazsuje řádek, vyfiltruje jej zda je větší jak revize a naformátuje jej do výstupu.
	 *
	 * @param array of string Položky branch + id:hash
	 *
	 * @return string
	 */
	protected function formatOutput(array $output, $loglevel)
	{
		$match = array();
		foreach ($output as $i => $row) {
			if (strpos($row, ' minor verze') !== False) {
				$match[] = $row;
				continue;
			}

			if (strpos($row, 'Databáze:') !== False) {
				$msg = $row;
				continue;
			}

			if (strpos($row, '[Error statement]') !== False) {
				return '[' . $this->database . ']: Error statement: ' . implode(PHP_EOL, array_slice($output, $i + 1));
			}
		}

		// Jeden v sekci Repozitář, druhý v sekci Databáze.
		if (count($match) != 2) {
			return "[{$this->database}]: Invalid status of repository. {$msg}";
		}
		else if ($match[0] == $match[1]) {
			return "[{$this->database}]: Already up-to-date. {$msg}";
		}
		else {
			if (preg_match('([\d\-]+\s?$)', $match[0], $matches)) {
				$m1 = $matches[0];
				if (preg_match('([\d\-]+\s?$)', $match[1], $matches)) {
					$m2 = $matches[0];
					if ($m1 != '-' && $m2 != '-') {
						return "[{$this->database}]: Diferent minor version: $m1 => $m2. {$msg}";
					}
					else if (isset($msg)) {
						return '[' . $this->database . ']: Error: ' . $msg;
					}
				}
			}
		}

		return '[' . $this->database . ']: Invalid format of status: ' . implode(PHP_EOL, $output) . ' ' . $msg;
	}



	/**
	 * @param Process\ExecException $e
	 * @throw BuildException if code != 0
	 * @return object {code, content}
	 */
	protected function catchException(\Exception $e)
	{
		return (object) array(
			'code' => $e->getCode(),
			'content' => explode(PHP_EOL, $e->getMessage()),
		);
	}

}
