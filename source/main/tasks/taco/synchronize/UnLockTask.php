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

/**
 * LockTask
 *
 * Uvolní proces zamčený Lockem.
 *
 * @author Martin Takáč <martin@takac.name>
 * @package phing.tasks.taco
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
