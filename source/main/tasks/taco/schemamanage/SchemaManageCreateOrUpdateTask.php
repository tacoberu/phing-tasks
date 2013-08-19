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
 * Aktualizace (nebo vytvoření nové) databáze podle repozitáře.
 *
 * @package   phing.tasks.taco
 */
class SchemaManageCreateOrUpdateTask extends SchemaManageBaseTask
{


	/**
	 *
	 */
	protected function formatOutputProperty($output)
	{
		$out = array();
		foreach ($output as $row) {
			if (strpos($row, '[Process: ' . ucfirst($this->action) . ']') !== False) {
				$out = array();
				continue;
			}
			if (strpos($row, '[Success]') !== False) {
				continue;
			}
			$out[] = $row;
		}
		return implode(', ', $out);
	}



	/**
	 * The main entry point method.
	 */
	public function main()
	{
        if (null === $this->dir) {
            throw new BuildException('"dir" is required parameter');
        }

		if ($this->fireIsCreated()) {
			$this->fireUpdate();
		}
		else {
			$this->fireCreate();
		}
	}



	/**
	 * The main entry point method.
	 */
	public function fireIsCreated()
	{
		$this->action = 'status';
        $this->prepare();
        list($return, $output) = $this->executeCommand();
		foreach ($output as $row) {
			if (strpos($row, 'Databáze: (connectable, managed)') !== False) {
				return True;
			}
		}
		return False;
	}



	/**
	 * The main entry point method.
	 */
	public function fireUpdate()
	{
		$this->action = 'update';
        $this->prepare();
        list($return, $output) = $this->executeCommand();
        $this->cleanup($return, $output);
	}



	/**
	 * The main entry point method.
	 */
	public function fireCreate()
	{
		$this->action = 'install';
        $this->prepare();
        list($return, $output) = $this->executeCommand();
        $this->cleanup($return, $output);
	}


}
