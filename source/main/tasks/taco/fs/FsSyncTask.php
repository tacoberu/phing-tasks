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
 * Executes a command on the shell.
 *
 * @author  Andreas Aderhold <andi@binarycloud.com>
 * @author  Hans Lellelid <hans@xmpl.org>
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @version $Revision: 1244 $
 * @package phing.tasks.system
 */
class FsSyncTask extends Task
{


	/**
	 * Obslužný program pro vykonání synchronizace.
	 * @var string
	 */
	private $bin = '/usr/bin/rsync';

	/**
	 * Command to execute.
	 * @var string
	 */
	protected $command;

	/**
	 * Commandline managing object
	 *
	 * @var Commandline
	 */
	protected $commandline;

	/**
	 * Working directory.
	 * @var PhingFile
	 */
	protected $dir;



	/**
	 * Source directory.
	 * @var PhingFile
	 */
	protected $source;



	/**
	 * Destination directory.
	 * @var PhingFile
	 */
	protected $to;



	/**
	 * Atributy příkazu.
	 * @var array
	 */
	protected $atributes = array();


	/**
	 * Operating system.
	 * @var string
	 */
	protected $os;

	/**
	 * Whether to escape shell command using escapeshellcmd().
	 * @var boolean
	 */
	protected $escape = false;

	/**
	 * Where to direct output.
	 * @var File
	 */
	protected $output;

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
	 * Logging level for status messages
	 * @var integer
	 */
	protected $logLevel = Project::MSG_VERBOSE;

	/**
	 * Where to direct error output.
	 * @var File
	 */
	protected $error;

	/**
	 * If spawn is set then [unix] programs will redirect stdout and add '&'.
	 * @var boolean
	 */
	protected $spawn = false;

	/**
	 * Property name to set with return value from exec call.
	 *
	 * @var string
	 */
	protected $returnProperty;

	/**
	 * Property name to set with output value from exec call.
	 *
	 * @var string
	 */
	protected $outputProperty;

	/**
	 * Whether to check the return code.
	 * @var boolean
	 */
	protected $checkreturn = false;



	public function __construct()
	{
		$this->commandline = new Commandline();
		$this->attributes = array('-av', '--delete');
	}



	/**
	 * Main method: wraps execute() command.
	 *
	 * @return void
	 */
	public function main()
	{
		if (!$this->isApplicable()) {
			return;
		}

		$this->prepare();
		$this->buildCommand();
		list($return, $output) = $this->executeCommand();
		$this->cleanup($return, $output);
	}

	/**
	 * Checks whether the command shall be executed
	 *
	 * @return boolean False if the exec command shall not be run
	 */
	protected function isApplicable()
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
	protected function prepare()
	{
		if ($this->dir === null) {
			return;
		}

		// expand any symbolic links first
		if (!$this->dir->getCanonicalFile()->isDirectory()) {
			throw new BuildException(
				"'" . (string) $this->dir . "' is not a valid directory"
			);
		}
		$this->currdir = getcwd();
		@chdir($this->dir->getPath());
	}



	/**
	 *	Příznaky programu.
	 */
	protected function buildAttributes()
	{
		return ' ' . implode(' ', $this->attributes);
	}



	/**
	 *	zdrojový, odkud konpírujeme.
	 */
	protected function buildSource()
	{
		return $this->source . DIRECTORY_SEPARATOR;
	}



	/**
	 *	Cílový, kam konpírujeme.
	 */
	protected function buildDesc()
	{
		return $this->to . DIRECTORY_SEPARATOR;
	}



	/**
	 * Builds the full command to execute and stores it in $command.
	 *
	 * @return void
	 * @uses   $command
	 */
	protected function buildCommand()
	{
		$this->command = $this->bin;

		//	-av --delete --exclude=".hg*"
		$this->command .= $this->buildAttributes();
		
		//	zdrojový, odkud konpírujeme.
		$this->command .= ' ' . $this->buildSource();
		
		//	Cílový, kam konpírujeme.
		$this->command .= ' ' . $this->buildDesc();
		

		if ($this->error !== null) {
			$this->command .= ' 2> ' . $this->error->getPath();
			$this->log(
				"Writing error output to: " . $this->error->getPath(),
				$this->logLevel
			);
		}

		if ($this->output !== null) {
			$this->command .= ' 1> ' . $this->output->getPath();
			$this->log(
				"Writing standard output to: " . $this->output->getPath(),
				$this->logLevel
			);
		} elseif ($this->spawn) {
			$this->command .= ' 1>/dev/null';
			$this->log("Sending output to /dev/null", $this->logLevel);
		}

		// If neither output nor error are being written to file
		// then we'll redirect error to stdout so that we can dump
		// it to screen below.

		if ($this->output === null && $this->error === null) {
			$this->command .= ' 2>&1';
		}

		// we ignore the spawn boolean for windows
		if ($this->spawn) {
			$this->command .= ' &';
		}
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function executeCommand()
	{
		$this->log("Executing command: " . $this->command, $this->logLevel);

		$output = array();
		$return = null;
		
		if ($this->passthru) {
			passthru($this->command, $return);
		}
		else {
			exec($this->command, $output, $return);
		}

		return array($return, $output);
	}

	/**
	 * Runs all tasks after command execution:
	 * - change working directory back
	 * - log output
	 * - verify return value
	 *
	 * @param integer $return Return code
	 * @param array   $output Array with command output
	 *
	 * @return void
	 */
	protected function cleanup($return, $output)
	{
		if ($this->dir !== null) {
			@chdir($this->currdir);
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
				$this->outputProperty, implode("\n", $output)
			);
		}

		if ($return != 0 && $this->checkreturn) {
			throw new BuildException("Task exited with code [$return]\nOutput: " . implode(PHP_EOL, $output) . PHP_EOL);
		}
	}

	/**
	 * The executable to use.
	 *
	 * @param mixed $executable String or string-compatible (e.g. w/ __toString()).
	 *
	 * @return void
	 */
	public function setExecutable($executable)
	{
		$this->commandline->setExecutable((string)$executable);
	}

	/**
	 * Whether to use escapeshellcmd() to escape command.
	 *
	 * @param boolean $escape If the command shall be escaped or not
	 *
	 * @return void
	 */
	public function setEscape($escape)
	{
		$this->escape = (bool) $escape;
	}



	/**
	 * Specify the working directory for executing this command.
	 *
	 * @param PhingFile $dir Working directory
	 *
	 * @return void
	 */
	public function setDir(PhingFile $dir)
	{
		$this->dir = $dir;
	}



	/**
	 * Specify the working directory for executing this command.
	 *
	 * @param PhingFile $dir Working directory
	 *
	 * @return void
	 */
	public function setSource(PhingFile $dir)
	{
		$this->source = $dir;
	}



	/**
	 * Specify the mask for ignoring.
	 *
	 * @param Mask of exclude files.
	 *
	 * @return void
	 */
	public function setExclude($mask)
	{
		$this->attributes[] = "--exclude '$mask'";
	}



	/**
	 * Specify the mask for ignoring.
	 *
	 * @param Mask of exclude files.
	 *
	 * @return void
	 */
	public function setDryRun($_)
	{
		$this->attributes[] = "--dry-run";
	}



	/**
	 * Specify the working directory for executing this command.
	 *
	 * @param PhingFile $dir Working directory
	 *
	 * @return void
	 */
	public function setTo($dir)
	{
		if (strpos($dir, '://')) {
			list($protocol, $dir) = explode('://', $dir);
			switch ($protocol) {
				case 'ssh':
					$dir = rtrim($dir, '\/');
					$this->attributes[] = '-e ssh';
					break;
				default:
					throw new \InvalidArgumentException('Unknow protocol: ' . $protocol);
			}
		}
		$this->to = $dir;
	}



	/**
	 * Specify OS (or muliple OS) that must match in order to execute this command.
	 *
	 * @param string $os Operating system string (e.g. "Linux")
	 *
	 * @return void
	 */
	public function setOs($os)
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
	public function setOutput(PhingFile $f)
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
	public function setError(PhingFile $f)
	{
		$this->error = $f;
	}

	/**
	 * Whether to use PHP's passthru() function instead of exec()
	 *
	 * @param boolean $passthru If passthru shall be used
	 *
	 * @return void
	 */
	public function setPassthru($passthru)
	{
		$this->passthru = (bool) $passthru;
	}

	/**
	 * Whether to log returned output as MSG_INFO instead of MSG_VERBOSE
	 *
	 * @param boolean $logOutput If output shall be logged visibly
	 *
	 * @return void
	 */
	public function setLogoutput($logOutput)
	{
		$this->logOutput = (bool) $logOutput;
	}

	/**
	 * Whether to suppress all output and run in the background.
	 *
	 * @param boolean $spawn If the command is to be run in the background
	 *
	 * @return void
	 */
	public function setSpawn($spawn)
	{
		$this->spawn  = (bool) $spawn;
	}

	/**
	 * Whether to check the return code.
	 *
	 * @param boolean $checkreturn If the return code shall be checked
	 *
	 * @return void
	 */
	public function setCheckreturn($checkreturn)
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
	public function setReturnProperty($prop)
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
	public function setOutputProperty($prop)
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
	public function setLevel($level)
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
	 * Creates a nested <arg> tag.
	 *
	 * @return CommandlineArgument Argument object
	 */
	public function createArg()
	{
		return $this->commandline->createArgument();
	}


}

