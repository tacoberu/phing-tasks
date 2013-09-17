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



use Taco\Utils\Process;



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
	 * Working directory.
	 * @var PhingFile
	 */
	protected $dir;



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
	 * Jméno databáze.
	 * @var string
	 */
	protected $database;


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
	 * The init method: Do init steps.
	 * Možnost globálně změnit chování pomocí build.properties
	 */
	public function init()
	{
		if ($bin = $this->getProject()->getProperty($this->getTaskName() . '.bin')) {
			$this->setBin($bin);
		}
		elseif ($bin = $this->getProject()->getProperty('schema-manage.bin')) {
			$this->setBin($bin);
		}
	}



	/**
	 * The setter for the attribute "bin"
	 */
	public function setBin($str)
	{
		$this->bin = $str;
		return $this;
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
	 * Specify the working directory for executing this command.
	 * @param PhingFile $dir
	 */
	function setDir(PhingFile $dir)
	{
		$this->dir = $dir;
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
				throw new BuildException(sprintf('Unknown log level "%s"', $level));
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
		return $this;
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
		return $this;
	}



	/**
	 * Whether to log returned output as MSG_INFO instead of MSG_VERBOSE
	 *
	 * @param boolean $logOutput If output shall be logged visibly
	 *
	 * @return void
	 */
	public function setOutput($logOutput)
	{
		$this->output = (bool) $logOutput;
		return $this;
	}




	/**
	 * The setter for the attribute "database"
	 */
	public function setDatabase($str)
	{
		$this->database = $this->options['database'] = trim($str);
		return $this;
	}



	/**
	 * The setter for the attribute "host"
	 */
	public function setHost($str)
	{
		$this->options['host'] = trim($str);
		return $this;
	}



	/**
	 * The setter for the attribute "user-login"
	 */
	public function setUserlogin($str)
	{
		$this->options['user-login'] = trim($str);
		return $this;
	}



	/**
	 * The setter for the attribute "host-password"
	 */
	public function setUserpassword($str)
	{
		$this->options['user-password'] = trim($str);
		return $this;
	}



	/**
	 * The setter for the attribute "admin-login"
	 */
	public function setAdminlogin($str)
	{
		$this->options['admin-login'] = trim($str);
		return $this;
	}



	/**
	 * The setter for the attribute "admin-password"
	 */
	public function setAdminpassword($str)
	{
		$this->options['admin-password'] = trim($str);
		return $this;
	}




	/**
	 * The main entry point method.
	 */
	public function main()
	{
		$this->assertRequiredParams();

		try {
			$status = $this->executeCommand();
		}
		catch (Process\ExecException $e) {
			$status = $this->catchException($e);
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
	 * @throw BuildException that not requred params.
	 */
	protected function assertRequiredParams()
	{
		if (null === $this->dir) {
			throw new BuildException('"dir" is required parameter');
		}

		// expand any symbolic links first
		if (!$this->dir->getCanonicalFile()->isDirectory()) {
			throw new BuildException("'" . (string) $this->dir . "' is not a valid directory");
		}
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function executeCommand()
	{
		$exec = $this->buildExecute()
			->setWorkDirectory($this->dir->getPath());
		$exec = $this->issetArguments($exec);

		$this->log($exec->dryRun(), Project::MSG_VERBOSE);

		return $exec->run();
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
	 * Isset command line arguments for the executable.
	 *
	 * @return Process\Exec
	 */
	protected function issetArguments(Process\Exec $exec)
	{
		$options = $this->options;

//		foreach ($this->args as $i => $arg) {
//			if (! $arg->getName()) {
//				throw new BuildException("Invalid $i-line argument. Name is not set.");
//			}
//			$options[$arg->getName()] = $arg->getValue();
//		}

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



	/**
	 * @param Process\ExecException $e
	 * @throw BuildException if code != 0
	 * @return object {code, content}
	 */
	protected function catchException(Process\ExecException $e)
	{
		throw new BuildException("Task exited with code: {$e->getCode()} and output: " . $e->getMessage());
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

		foreach ($this->buildParams() as $name => $value) {
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
	protected function _executeCommand()
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
	 * Seskládá parametry příkazu.
	 */
	private function buildParams()
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

		if ($this->adminlogin) {
			$ret['admin-login'] = $this->adminlogin;
		}

		if ($this->adminpassword) {
			$ret['admin-password'] = $this->adminpassword;
		}

		return $ret;
	}






	/**
	 * @deprecated
	 */
	public function setLogoutput($logOutput)
	{
		$this->output = (bool) $logOutput;
	}


}
