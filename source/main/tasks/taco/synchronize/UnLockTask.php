<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
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
	 * @return  void
	 * @throws  BuildException
	 */
	function main()
	{
		unlink($this->prepareLockFilename());
	}

}
