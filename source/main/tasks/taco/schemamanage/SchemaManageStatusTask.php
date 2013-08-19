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
require_once "tasks/taco/schemamanage/SchemaManageBaseTask.php";



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
	 * Zpracuje výstup pro proměnnou.
	 */
	protected function formatOutputProperty($output, $loglevel)
	{
		$match = array();
		foreach ($output as $row) {
			if (strpos($row, ' minor verze') !== False) {
				$match[] = $row;
				continue;
			}

			if (strpos($row, 'Databáze:') !== False) {
				$msg = $row;
				continue;
			}
		}
		
		if (count($match) != 2) {
			return '[' . $this->database . ']: Invalid status of repository.';
		}
		else if ($match[0] == $match[1]) {
			if ($loglevel >= Project::MSG_VERBOSE) {
				return '[' . $this->database . ']: Already up-to-date.';
			}
			return False;
		}
		else {
			if (preg_match('([\d\-]+\s?$)', $match[0], $matches)) {
				$m1 = $matches[0];
				if (preg_match('([\d\-]+\s?$)', $match[1], $matches)) {
					$m2 = $matches[0];
					if ($m1 != '-' && $m2 != '-') {
						return "[{$this->database}]: Diferent minor version: $m1 => $m2.";
					}
					else if (isset($msg)) {
						return '[' . $this->database . ']: Error: ' . $msg;
					}
				}
			}
		}
		return '[' . $this->database . ']: Invalid format of status: ' . PHP_EOL . implode(PHP_EOL, $output);
	}
    

}
