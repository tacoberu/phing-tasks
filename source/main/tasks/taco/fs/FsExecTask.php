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

use Taco\Utils\Process;

/**
 * Executes a command on the shell.
 *
 * @package phing.tasks.taco
 *
 */
class FsExecTask extends Task
{

	/**
	 * Commandline managing object
	 *
	 * @var Commandline
	 */
	private $commandline;

	/**
	 * Working directory.
	 * @var PhingFile
	 */
	private $dir;

	/**
	 * Operating system.
	 * @var string
	 */
	private $os;

	/**
	 * Where to direct output.
	 * @var File
	 */
	private $output;

	/**
	 * Whether to log returned output as MSG_INFO instead of MSG_VERBOSE
	 * @var boolean
	 */
	private $logOutput = false;

	/**
	 * Logging level for status messages
	 * @var integer
	 */
	private $logLevel = Project::MSG_VERBOSE;

	/**
	 * Where to direct error output.
	 * @var File
	 */
	private $error;

	/**
	 * Property name to set with return value from exec call.
	 *
	 * @var string
	 */
	private $returnProperty;

	/**
	 * Property name to set with output value from exec call.
	 *
	 * @var string
	 */
	private $outputProperty;

	/**
	 * Whether to check the return code.
	 * @var boolean
	 */
	private $checkreturn = true;


	function __construct()
	{
		$this->commandline = new Commandline();
		$this->dir = new PhingFile(getcwd());
	}



	/**
	 * Main method: wraps execute() command.
	 *
	 * @return void
	 */
	function main()
	{
		if (!$this->isApplicable()) {
			return;
		}

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
	 * The command to use.
	 *
	 * @param mixed $command String or string-compatible (e.g. w/ __toString()).
	 *
	 * @return void
	 */
	function setCommand($command)
	{
		$this->commandline->setExecutable((string)$command);
	}



	/**
	 * Specify the working directory for executing this command.
	 *
	 * @param PhingFile $dir Working directory
	 *
	 * @return void
	 */
	function setDir(PhingFile $dir)
	{
		$this->dir = $dir;
	}



	/**
	 * Specify OS (or muliple OS) that must match in order to execute this command.
	 *
	 * @param string $os Operating system string (e.g. "Linux")
	 *
	 * @return void
	 */
	function setOs($os)
	{
		$this->os = (string) $os;
	}



	/**
	 * File to which output should be written.
	 *
	 * @param PhingFile $f Output log file
	 *
	 * @return void
	 */
	function setOutput(PhingFile $f)
	{
		$this->output = $f;
	}



	/**
	 * File to which error output should be written.
	 *
	 * @param PhingFile $f Error log file
	 *
	 * @return void
	 */
	function setError(PhingFile $f)
	{
		$this->error = $f;
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
	 * Whether to check the return code.
	 *
	 * @param boolean $checkreturn If the return code shall be checked
	 *
	 * @return void
	 */
	function setCheckreturn($checkreturn)
	{
		$this->checkreturn = (bool) $checkreturn;
	}



	/**
	 * The name of property to set to return value from exec() call.
	 *
	 * @param string $prop Property name
	 *
	 * @return void
	 */
	function setReturnProperty($prop)
	{
		$this->returnProperty = $prop;
	}



	/**
	 * The name of property to set to output value from exec() call.
	 *
	 * @param string $prop Property name
	 *
	 * @return void
	 */
	function setOutputProperty($prop)
	{
		$this->outputProperty = $prop;
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
				throw new BuildException(sprintf('Unknown log level "%s"', $level));
		}
	}



	/**
	 * Creates a nested <arg> tag.
	 *
	 * @return CommandlineArgument Argument object
	 */
	function createArg()
	{
		return $this->commandline->createArgument();
	}



	/**
	 * Checks whether the command shall be executed
	 *
	 * @return boolean False if the exec command shall not be run
	 */
	private function isApplicable()
	{
		if ($this->os === null) {
			return true;
		}

		$myos = Phing::getProperty('os.name');
		$this->log('Myos = ' . $myos, Project::MSG_VERBOSE);

		if (strpos($this->os, $myos) !== false) {
			// this command will be executed only on the specified OS
			// OS matches
			return true;
		}

		$this->log(
			sprintf(
				'Operating system %s not found in %s',
				$myos, $this->os
			),
			Project::MSG_VERBOSE
		);
		return false;
	}



	/**
	 * Prepares the command building and execution, i.e.
	 * changes to the specified directory.
	 *
	 * @return void
	 */
	private function requireWorkDirectory()
	{
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
		if (empty($this->commandline->getExecutable())) {
			throw new BuildException('ExecTask: Please provide "command"');
		}

		$exec = new Process\Exec($this->commandline->getExecutable());
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



	/**
	 * @param Process\ExecException $e
	 * @return object {code, content}
	 */
	private function catchException(Process\ExecException $e)
	{
		return array($e->getCode(), explode(PHP_EOL, $e->getMessage()));
	}


}

