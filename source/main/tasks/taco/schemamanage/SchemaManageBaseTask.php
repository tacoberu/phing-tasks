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



abstract class SchemaManageBaseTask extends Task
{

    /**
     * Command to execute.
     * @var string
     */
    protected $command;


    /**
     * Action to execute.
     * @var string
     */
    protected $action;


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
     * Logging level for status messages
     * @var integer
     */
    protected $logLevel = Project::MSG_INFO;



	/**
	 * The database 
	 */
	private $database = null;



	/**
	 * The host 
	 */
	private $host = null;


	/**
	 * The userlogin 
	 */
	private $userlogin = Null;


	/**
	 * The userpassword 
	 */
	private $userpassword = Null;


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
     * Specify the working directory for executing this command.
     * @param PhingFile $dir
     */
    function setDir(PhingFile $dir) {
        $this->dir = $dir;
    }



	/**
	 * The setter for the attribute "branch"
	 */
	public function setDatabase($str)
	{
		$this->database = $str;
	}



	/**
	 * The setter for the attribute "host"
	 */
	public function setHost($str)
	{
		$this->host = $str;
	}



	/**
	 * The setter for the attribute "host"
	 */
	public function setUserlogin($str)
	{
		$this->userlogin = $str;
	}



	/**
	 * The setter for the attribute "host"
	 */
	public function setUserpassword($str)
	{
		$this->userpassword = $str;
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
	 * Parametry příkazu.
	 */
	public function getParams()
	{
		$ret = array();
		if ($this->database) {
			$ret['database'] = $this->database;
		}
		if ($this->host) {
			$ret['host'] = $this->host;
		}
		if ($this->userlogin) {
			$ret['user-login'] = $this->userlogin;
		}
		if ($this->userpassword) {
			$ret['user-password'] = $this->userpassword;
		}
		if ($this->userlogin) {
			$ret['admin-login'] = $this->userlogin;
		}
		if ($this->userpassword) {
			$ret['admin-password'] = $this->userpassword;
		}
		
		return $ret;
	}



    /**
     * Prepares the command building and execution, i.e.
     * changes to the specified directory.
     *
     * @return void
     */
    protected function prepare()
    {
        // expand any symbolic links first
        if (!$this->dir->getCanonicalFile()->isDirectory()) {
            throw new BuildException(
                "'" . (string) $this->dir . "' is not a valid directory"
            );
        }
        foreach ($this->getParams() as $name => $value) {
        	$params[] = '--' . $name . ' ' . $value;
        }
        $this->currdir = getcwd();
        @chdir($this->dir->getPath());
        
        if (count($params)) {
        	$params = ' ' . implode(' ', $params);
        }
        else {
        	$params = Null;
        }
		$this->command = '/usr/bin/schema-manage ' . $this->action . $params;
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
        $this->log('Executing command: [' . $this->command . '], with returning code: ' . $return, Project::MSG_VERBOSE);

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

        $this->log('Execute schema-manage in: ' . $this->dir . ' (' . $this->database . ')', $outloglevel);

        if ($this->returnProperty) {
            $this->project->setProperty($this->returnProperty, $return);
        }

        if ($this->outputProperty) {
            $this->project->setProperty($this->outputProperty, $this->formatOutputProperty($output));
        }

        if ($return != 0) {
            throw new BuildException("Task exited with code $return and message: " . implode("\n", $output));
        }
    }



	/**
	 * The main entry point method.
	 */
	public function main()
	{
        if (null === $this->dir) {
            throw new BuildException('"dir" is required parameter');
        }

        $this->prepare();
        list($return, $output) = $this->executeCommand();
        $this->cleanup($return, $output);
	}





}
