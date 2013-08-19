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
 * Aktualizace databáze podle repozitáře.
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
class SchemaManageUpdateTask extends SchemaManageBaseTask
{


    /**
     * Action to execute.
     * @var string
     */
    protected $action = 'update';



	/**
	 * Zpracuje výstup pro proměnnou.
	 */
	protected function formatOutputProperty($output, $outloglevel)
	{
		$out = array();
		foreach ($output as $row) {
			if (strpos($row, '[Process: Update]') !== False) {
				$out = array();
				continue;
			}
			
			if (strpos($row, '[Success]') !== False) {
				continue;
			}

			if (strpos($row, 'Aktualizuji:') !== False) {
				$out[] = $row;
				continue;
			}
			
			$msg[] = $row;
		}

		if (count($out)) {
			return "[{$this->database}]: Process:\n" . implode(PHP_EOL, $out);
		}
		else {
			if ($loglevel >= Project::MSG_VERBOSE) {
				return "[{$this->database}]: " . implode('; ', $msg);
			}
		}
	}

}
