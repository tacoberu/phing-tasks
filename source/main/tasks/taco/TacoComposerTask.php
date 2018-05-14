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

require_once 'phing/Task.php';
require_once "phing/types/Commandline.php";

use Taco\Utils\Process;


/**
 * Composer Task
 * Run composer straight from phing. Re
 *
 *  @package  phing.tasks.taco
 */
class TacoComposerTask extends Task
{

	/**
	 * Commandline managing object
	 *
	 * @var Commandline
	 */
	private $commandline;


	/**
	 * @var string the path to php interperter
	 */
	private $composer = '/usr/bin/env composer';


	/**
	 *
	 * @var string the Composer action to execute
	 */
	private $action = null;


	/**
	 * Working directory.
	 * @var PhingFile
	 */
	protected $dir;


	/**
	 * Whether to use PHP's passthru() function instead of exec()
	 * @var boolean
	 */
	protected $passthru = false;


	/**
	 * Whether to log returned output as MSG_INFO instead of MSG_VERBOSE
	 * @var boolean
	 */
	protected $logOutput = false;


	/**
	 * @var boolean
	 */
	protected $quiet = false;


	/**
	 * Logging level for status messages
	 * @var integer
	 */
	protected $logLevel = Project::MSG_INFO;


	function __construct()
	{
		$this->commandline = new Commandline();
	}



	/**
	 * The init method: Do init steps.
	 * Možnost globálně změnit chování pomocí build.properties
	 */
	function init()
	{
		if ($bin = $this->getProject()->getProperty($this->getTaskName() . '.bin')) {
			$this->setBin($bin);
		}
		elseif ($bin = $this->getProject()->getProperty('composer.bin')) {
			$this->setBin($bin);
		}
	}



	/**
	 * The setter for the attribute "bin"
	 */
	function setBin($str)
	{
		$this->composer = $str;
	}



	function setQuiet($bool)
	{
		$this->quiet = (bool) $bool;
	}



	/**
	 * Whether to log returned output as MSG_INFO instead of MSG_VERBOSE
	 *
	 * @param boolean $logOutput If output shall be logged visibly
	 *
	 * @return void
	 */
	function setLogoutput($logOutput)
	{
		$this->logOutput = (bool) $logOutput;
	}



	/**
	 * Set level of log messages generated (default = verbose)
	 *
	 * @param string $level Log level
	 *
	 * @return void
	 */
	function setLevel($level)
	{
		switch ($level) {
		case 'error':
			$this->logLevel = Project::MSG_ERR;
			break;
		case 'warning':
			$this->logLevel = Project::MSG_WARN;
			break;
		case 'info':
			$this->logLevel = Project::MSG_INFO;
			break;
		case 'verbose':
			$this->logLevel = Project::MSG_VERBOSE;
			break;
		case 'debug':
			$this->logLevel = Project::MSG_DEBUG;
			break;
		default:
			throw new BuildException(
				sprintf('Unknown log level "%s"', $level)
			);
		}
	}



	/**
	 * Specify the working directory for executing this command.
	 * @param PhingFile $dir
	 */
	function setDir(PhingFile $dir)
	{
		$this->dir = $dir;
	}



	/**
	 * sets the Composer command to execute
	 * @param string $command
	 */
	function setCommand($command)
	{
		$this->action = $command;
	}



	/**
	 * creates a nested arg task
	 *
	 * @return Arg Argument object
	 */
	function createArg()
	{
		return $this->commandline->createArgument();
	}



	/**
	 * executes the Composer task
	 */
	function main()
	{
		list($return, $output) = $this->executeCommand();
		if ($return != 0 && $this->checkreturn) {
			throw new BuildException("Task exited with code [$return]\nOutput: " . implode(PHP_EOL, $output) . PHP_EOL);
		}

		$outloglevel = $this->logOutput ? Project::MSG_INFO : Project::MSG_VERBOSE;
		foreach ($output as $line) {
			$this->log($line, $outloglevel);
		}

		if ($this->returnProperty) {
			$this->project->setProperty($this->returnProperty, $return);
		}

		if ($this->outputProperty) {
			$this->project->setProperty(
				$this->outputProperty, implode(PHP_EOL, $output)
			);
		}
	}



	/**
	 * Specify the working directory for executing this command.
	 *
	 * @return string
	 */
	private function requireWorkDirectory()
	{
		if (empty($this->dir)) {
			$this->dir = $this->project->getBasedir();
		}
		if (!$this->dir->getCanonicalFile()->isDirectory()) {
			throw new BuildException(
				"'" . (string) $this->dir . "' is not a valid directory"
			);
		}
		return $this->dir->getPath();
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	private function executeCommand()
	{
		if (empty($this->composer)) {
			throw new BuildException('ExecTask: Please provide "bin"');
		}
		if (empty($this->action)) {
			throw new BuildException('ExecTask: Please provide "command"');
		}
		$this->commandline->setExecutable((string)$this->composer);

		$exec = new Process\Exec($this->commandline->getExecutable());
		$exec->arg($this->action);
		foreach ($this->commandline->getArguments() as $x) {
			$exec->arg($x);
		}
		$exec->setWorkDirectory($this->requireWorkDirectory());

		$this->log("Executing command: " . $exec->dryRun(), $this->logLevel);
		try {
			$state = $exec->run();
			return array($state->code, $state->content);
		}
		catch (Process\ExecException $e) {
			return $this->catchException($e);
		}
	}

}
