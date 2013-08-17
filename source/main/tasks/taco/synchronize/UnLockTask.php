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
 * @author	 Martin Takáč <taco@taco-beru.name>
 */

require_once 'phing/Task.php';

/**
 * LockTask
 *
 * Uvolní proces zamčený Lockem.
 *
 * @author Martin Takáč <martin@takac.name>
 * @package phing.tasks.ext
 */
class UnLockTask extends LockTask
{


	/**
	 * Main method
	 *
	 * @return  void
	 * @throws  BuildException
	 */
	public function main()
	{
		unlink($this->prepareLockFilename());
	}



}
