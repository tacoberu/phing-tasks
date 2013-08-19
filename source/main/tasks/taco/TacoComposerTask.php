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


/**
 * Composer Task
 * Run composer straight from phing. Re
 *
 *  @package  phing.tasks.taco
 */
class TacoComposerTask extends Task
{


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
     * Whether to log returned output as MSG_INFO instead of MSG_VERBOSE
     * @var boolean
     */
    protected $logOutput = false;
    


    /**
     * Logging level for status messages
     * @var integer
     */
    protected $logLevel = Project::MSG_INFO;



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
	 * Specify the working directory for executing this command.
	 * @param PhingFile $dir
	 */
	function setDir(PhingFile $dir)
	{
		$this->dir = $dir;
	}


	/**
	 * Specify the working directory for executing this command.
	 */
	function getDir()
	{
		if (empty($this->dir)) {
			$this->dir = $this->project->getBasedir();
		}
		return $this->dir;
	}




	/**
	 * sets the Composer command to execute
	 * @param string $command
	 */
	public function setCommand($command)
	{
		$this->action = $command;
	}



	/**
	 * returns the path to Composer application
	 * @return string
	 */
	public function getComposer()
	{
		return $this->composer;
	}



	/**
	 * creates a nested arg task
	 *
	 * @return Arg Argument object
	 */
	public function createArg()
	{
		return $this->commandLine->createArgument();
	}



	/**
	 * executes the Composer task
	 */
	public function main()
	{
		$this->prepare();
		list($return, $output) = $this->executeCommand();
		$this->cleanup($return, $output);
	}




	/**
	 * Prepares the command building and execution, i.e.
	 * changes to the specified directory.
	 *
	 * @return void
	 */
	protected function prepare()
	{
		if (empty($this->action)) {
			throw new \LogicException("Not set action.");
		}

		if (!$this->getDir()->getCanonicalFile()->isDirectory()) {
			throw new BuildException("'" . (string) $this->dir . "' is not a valid directory.");
		}

		$params = array(
			'--no-ansi',
			'-n',
			);

#		foreach ($this->getParams() as $name => $value) {
#			$params[] = '--' . $name . ' ' . $value;
#		}

		$this->currdir = getcwd();
		@chdir($this->dir->getPath());
		
		if (count($params)) {
			$params = ' ' . implode(' ', $params);
		}
		else {
			$params = Null;
		}

		$this->command = $this->composer . ' ' . $this->action . $params;
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function executeCommand()
	{
		$output = array();
		$return = null;
		
		if ($this->passthru) {
			passthru($this->command, $return);
		}
		else {
			exec($this->command, $output, $return);
		}
		
		//	schema-manage vrací špatné návratové hodnoty.
		if (strpos(implode('', $output), '[Error]') !== False) {
			$return = 1;
		}
		$this->log('Executing command: [' . $this->command . '], in workdir: [' 
				. $this->dir->getPath() . '], with returning code: ' 
				. $return,
				Project::MSG_VERBOSE);

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

		$outloglevel = $this->logOutput ? Project::MSG_VERBOSE : Project::MSG_INFO;
		$out = $this->formatOutputProperty($output, $outloglevel);
		if ($out) {
			$this->log($out, $this->logLevel);
		}

		if ($this->returnProperty) {
			$this->project->setProperty($this->returnProperty, $return);
		}

		if ($out && $this->outputProperty) {
			$this->project->setProperty($this->outputProperty, $out);
		}

		if ($return != 0) {
			throw new BuildException("Task exited with code $return and message: " . implode("\n", $output));
		}
	}




	/**
	 * Zpracuje výstup pro proměnnou.
	 */
	protected function formatOutputProperty($output, $loglevel)
	{
		return implode(PHP_EOL, $output);
	}


}
