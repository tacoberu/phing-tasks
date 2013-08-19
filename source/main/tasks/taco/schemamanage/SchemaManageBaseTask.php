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
 * Base class for SchemaManage
 *
 * @package   phing.tasks.taco
 */
abstract class SchemaManageBaseTask extends Task
{

    /**
     * Destination of schema-manage runtime.
     * @var string
     */
    protected $bin = '/usr/bin/schema-manage';


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
	protected $database = null;


	/**
	 * The host 
	 */
	protected $host = null;


	/**
	 * The userlogin 
	 */
	protected $userlogin = Null;


	/**
	 * The userpassword 
	 */
	protected $userpassword = Null;


	/**
	 * The adminlogin 
	 */
	protected $adminlogin = Null;


	/**
	 * The adminpassword 
	 */
	protected $adminpassword = Null;


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
	 * The setter for the attribute "bin"
	 */
	public function setBin($str)
	{
		$this->bin = $str;
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
	 * The setter for the attribute "database"
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
	 * The setter for the attribute "user-login"
	 */
	public function setUserlogin($str)
	{
		$this->userlogin = $str;
	}



	/**
	 * The setter for the attribute "host-password"
	 */
	public function setUserpassword($str)
	{
		$this->userpassword = $str;
	}



	/**
	 * The setter for the attribute "admin-login"
	 */
	public function setAdminlogin($str)
	{
		$this->adminlogin = $str;
	}



	/**
	 * The setter for the attribute "admin-password"
	 */
	public function setAdminpassword($str)
	{
		$this->adminpassword = $str;
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

        if (empty($this->bin)) {
            throw new BuildException("Not set bin with schema-manage runtime.");
        }

        if (!$this->dir->getCanonicalFile()->isDirectory()) {
            throw new BuildException("'" . (string) $this->dir . "' is not a valid directory.");
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
		$this->command = $this->bin . ' ' . $this->action . $params;
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
	 * Parametry příkazu.
	 */
	private function getParams()
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
			$ret['admin-login'] = $this->adminlogin;
		}

		if ($this->userpassword) {
			$ret['admin-password'] = $this->adminpassword;
		}
		
		return $ret;
	}


}
