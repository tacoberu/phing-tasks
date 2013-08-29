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


/**
 *  @package  phing.tasks.taco
 */
class TacoAutoloadTask extends Task
{



	/**
	 * Autoload file.
	 * @var PhingFile
	 */
	protected $file;



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
	function setFile(PhingFile $dir)
	{
		$this->file = $dir;
	}



	/**
	 * executes the Composer task
	 */
	public function main()
	{
		if (empty($this->file) || ! (string)$this->file->getCanonicalFile()) {
			throw new BuildException("'" . (string) $this->file . "' is not set.");
		}

		require_once $this->file->getCanonicalFile();
	}



}
