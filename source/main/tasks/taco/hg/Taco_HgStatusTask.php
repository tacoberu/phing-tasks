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
require_once __dir__ . '/HgBaseTask.php';


/**
 * Update mercurial repository to last revision, or last revision of branch.
 *
 * @sample
 * hg status
 *
 * <hg.status repository="." output="true" />
 *
 * @package phing.tasks.taco
 */
class Taco_HgStatusTask extends Taco_HgBaseTask
{

	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'status';


}
