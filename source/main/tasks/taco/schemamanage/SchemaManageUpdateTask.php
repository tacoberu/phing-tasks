<?php
/**
 * Copyright (c) 2004, 2011 Martin Takáč
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author     Martin Takáč <taco@taco-beru.name>
 */

require_once "phing/Task.php";
require_once "phing/tasks/taco/schemamanage/SchemaManageBaseTask.php";


class SchemaManageUpdateTask extends SchemaManageBaseTask
{


    /**
     * Action to execute.
     * @var string
     */
    protected $action = 'update';



	/**
	 *
	 */
	protected function formatOutputProperty($output)
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
			$out[] = $row;
		}
		return implode(', ', $out);
	}

}
