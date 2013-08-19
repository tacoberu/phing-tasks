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



/**
 * Update mercurial repository to last revision, or last revision of branch.
 *
 * @package phing.tasks.taco
 */
class HgUpdateTask extends Task
{

    /**
     * Command to execute.
     * @var string
     */
    protected $command;



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
    protected $logLevel = Project::MSG_INFO;



	/**
	 * The branch passed in the buildfile.
	 */
	private $branch = null;



    /**
     * Current repository directory
     * @var string
     */
    private $repository; 



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
	 * The setter for the attribute "branch"
	 */
	public function setBranch($str)
	{
		$this->branch = $str;
	}



    /**
     * Set repository directory
     *
     * @param string $repository Repo directory
     * @return GitBaseTask
     */
    public function setRepository(PhingFile $repository)
    {
        $this->repository = $repository;
        return $this;
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
	 * The init method: Do init steps.
	 */
	public function init()
	{
	}



    /**
     * Prepares the command building and execution, i.e.
     * changes to the specified directory.
     *
     * @return void
     */
    protected function prepare()
    {
        if ($this->repository === null) {
            return;
        }

        // expand any symbolic links first
        if (!$this->repository->getCanonicalFile()->isDirectory()) {
            throw new BuildException(
                "'" . (string) $this->repository . "' is not a valid directory"
            );
        }
        $this->currdir = getcwd();
        @chdir($this->repository->getPath());
		$this->command = '/usr/bin/hg update ' . $this->branch . ' --clean';
    }



    /**
     * Executes the command and returns return code and output.
     *
     * @return array array(return code, array with output)
     */
    protected function executeCommand()
    {
//        $this->log("Executing command: " . $this->command, $this->logLevel);

        $output = array();
        $return = null;
        
        if ($this->passthru) {
            passthru($this->command, $return);
        }
        else {
            exec($this->command, $output, $return);
        }
        $this->log('Executing command: ' . $this->command, Project::MSG_VERBOSE);

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
        if ($this->repository !== null) {
            @chdir($this->currdir);
        }

        $outloglevel = $this->logOutput ? Project::MSG_INFO : Project::MSG_VERBOSE;

        $this->log('Updated hg in: ' . $this->repository . ' (' . $this->branch . ')', $outloglevel);

        if ($this->returnProperty) {
            $this->project->setProperty($this->returnProperty, $return);
        }

        if ($this->outputProperty) {
            $this->project->setProperty(
                $this->outputProperty, implode(PHP_EOL, $output)
            );
        }

        if ($return != 0) {
            throw new BuildException("Task exited with code $return");
        }
    }



	/**
	 * The main entry point method.
	 */
	public function main()
	{
        if (null === $this->repository) {
            throw new BuildException('"repository" is required parameter');
        }

        $this->prepare();
//        $this->buildCommand();
        list($return, $output) = $this->executeCommand();
        $this->cleanup($return, $output);
	}





}
