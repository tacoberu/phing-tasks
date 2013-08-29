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

require_once "phing/Task.php";



use Taco\Utils\Process;



/**
 * HgTagTask
 *
 * Loads a (text) names of tags between two revision of hg.
 *
 * @package phing.tasks.taco
 */
abstract class HgBaseTask extends Task
{

	/**
	 * Destination of mercurial runtime.
	 * @var string
	 */
	protected $bin = '/usr/bin/hg';


	/**
	 * Exec for executing process.
	 * @var Process\Exec
	 */
	protected $exec;


	/**
	 * Action to execute: status, update, install
	 * @var string
	 */
	protected $action = 'status';


	/**
	 * Working directory of direcotry.
	 * @var PhingFile
	 */
	protected $repository;


	/**
	 * Logging level for status messages
	 * @var integer
	 */
	protected $logLevel = Project::MSG_INFO;



    /**
     * Whether to log returned output as MSG_INFO instead of MSG_VERBOSE
     * @var boolean
     */
    protected $output = false;


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
	 * Default options for ...
	 *
	 * @var array
	 */
	protected $options = array();


	/**
	 * Arguments
	 *
	 * @var array
	 */
	protected $args = array();


	/**
	 * The init method: Do init steps.
	 * Možnost globálně změnit chování pomocí build.properties
	 */
	public function init()
	{
		if ($bin = $this->getProject()->getProperty($this->getTaskName() . '.bin')) {
			$this->setBin($bin);
		}
		elseif ($bin = $this->getProject()->getProperty('hg.bin')) {
			$this->setBin($bin);
		}
	}



	/**
	 * The setter for the attribute "bin"
	 */
	public function setBin($str)
	{
		$this->bin = $str;
	}



	/**
	 * The setter for process.
	 */
	public function setExec(Process\Exec $exec)
	{
		$this->exec = $exec;
		return $this;
	}



	/**
	 * Set repository directory
	 *
	 * @param string $repository Repo directory
	 * @return this
	 */
	public function setRepository(PhingFile $repository)
	{
		$this->repository = $repository;
		return $this;
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
    public function setOutput($bool)
    {
        $this->output = (bool) $bool;
    }



	/**
	 * creates a nested arg task
	 *
	 * @return Arg Argument object
	 */
	public function createArg()
	{
		$arg = new TacoArgument($this);
		$this->args[] = $arg;
		return $arg;
	}



	/**
	 * The main entry point method.
	 */
	public function main()
	{
		if (null === $this->repository) {
			throw new BuildException('"repository" is required parameter');
		}

		$status = $this->executeCommand();
		if ($status->code != 0) {
			throw new BuildException("Task exited with code: {$status->code} and output: " . implode(PHP_EOL, $status->content));
		}

		if ($this->outputProperty) {
			$this->project->setProperty($this->outputProperty, $this->formatOutput($status->content));
		}

		if ($this->returnProperty) {
			$this->project->setProperty($this->returnProperty, $status->code);
		}

		if ($status->content) {
			$outloglevel = $this->output ? Project::MSG_VERBOSE : Project::MSG_INFO;
			$out = $this->formatOutput($status->content, $outloglevel);
			if ($out) {
				$this->log($out, $this->logLevel);
			}
		}
	}
	


	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function buildExecute()
	{
		if ($this->exec) {
			return $this->exec;
		}
	
		if (empty($this->bin)) {
			throw new BuildException('"bin" is not empty parameter');
		}

		$exec = new Process\Exec($this->bin);
		$exec->arg($this->action);

		return $exec;
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function executeCommand()
	{
		if ($this->repository === null) {
			return;
		}

		// expand any symbolic links first
		if (!$this->repository->getCanonicalFile()->isDirectory()) {
			throw new BuildException("'" . (string) $this->repository . "' is not a valid directory");
		}

		$exec = $this->buildExecute()
			->setWorkDirectory($this->repository->getPath());
		$exec = $this->issetArguments($exec);

		$this->log($exec->dryRun(), Project::MSG_VERBOSE);
		
		return $exec->run();
	}



	/**
	 * Isset command line arguments for the executable.
	 *
	 * @return string
	 */
	protected function issetArguments(Process\Exec $exec)
	{
		$args = array();
		$options = $this->options;

		foreach ($this->args as $i => $arg) {
			if (! $arg->getName()) {
				throw new BuildException("Invalid $i-line argument. Name is not set.");
			}
			$options[$arg->getName()] = $arg->getValue();
		}

		foreach ($options as $name => $value) {
			if ($value === False) {
				continue;
			}

			if (empty($value)) {
				if (strlen($name) == 1) {
					$exec->arg("-$name");
				}
				else {
					$exec->arg("--$name");
				}
			}
			else {
				if (strlen($name) == 1) {
					$exec->arg("-$name $value");
				}
				else {
					$exec->arg("--$name $value");
				}
			}
		}
		
		return $exec;
	}



	/**
	 * Zpracovat výstup. Rozprazsuje řádek, vyfiltruje jej zda je větší jak revize a naformátuje jej do výstupu.
	 *
	 * @param array of string Položky branch + id:hash
	 *
	 * @return string
	 */
	protected function formatOutput(array $output)
	{
		return implode(PHP_EOL, $output);
	}



}


/**
 * "Inner" class used for nested xml command line definitions.
 *
 * @package phing.types
 */
class TacoArgument
{

	private $name;
	private $value;
	private $parent;


	public function __construct($parent)
	{
		$this->parent = $parent;
	}


	
	/**
	 * Sets a single commandline argument.
	 *
	 * @param string $value a single commandline argument.
	 */
	public function setValue($value)
	{
		$this->value = trim($value);
		return $this;
	}



	/**
	 * Sets a single commandline argument.
	 *
	 * @param string $value a single commandline argument.
	 */
	public function setName($value)
	{
		$this->name = trim($value);
		return $this;
	}


	
	/**
	 * gets a single commandline argument.
	 *
	 * @param string 
	 */
	public function getValue()
	{
		return $this->value;
	}



	/**
	 * gets a single commandline argument.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


}


