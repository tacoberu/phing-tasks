<?php

require_once 'phing/Task.php';

/**
 *		<taco.translate src="${env.HG_URL}" property="repo.sender">
 *			<match src='remote:ssh:10.18.0.6' to="martintakac" />
 *			<match src='remote:ssh:10.18.10.6' to="johndee" />
 *		</taco.translate>
 *
 *		<taco.translate src="${env.HG_URL}" property="repo.sender" dictionary="dictionary.properties" />
 *
 * @author Martin Takáč <martin@takac.name>
 */
class TacoTranslateTask extends Task
{

	/**
	 * Logging level for status messages
	 * @var integer
	 */
	private $logLevel = Project::MSG_INFO;


	/**
	 *	Seznam parametrů.
	 *	@var array of TacoTranslateTaskMatch
	 */
	private $params = array(); // parameters for func_tion calls


	/**
	 * Property to be set
	 * @var string
	 */
	private $property;


	/**
	 * @var string
	 */
	private $src;


	/**
	 * @var PhingFile
	 */
	private $dictionary;


	/**
	 * Set level of log messages generated (default = info)
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



	function setSrc($v)
	{
		$this->src = trim($v);
		return $this;
	}



	/**
	 * Set name of property to be set
	 * @param $property
	 * @return void
	 */
	function setProperty($property)
	{
		$this->property = $property;
	}



	/**
	 * File type properties with dictionary.
	 * @param PhingFile $file
	 */
	function setDictionary($file)
	{
		if (is_string($file)) {
			$file = new PhingFile($file);
		}
		$this->dictionary = $file;
	}



	function main()
	{
        if (empty($this->src)) {
            throw new BuildException("Attribute 'src' required", $this->getLocation());
        }

        if (empty($this->property)) {
            throw new BuildException("Attribute 'property' required", $this->getLocation());
        }

		foreach ($this->loadFile($this->dictionary) as $key => $value) {
			$this->createMatch()->setSrc($key)->setTo($value);
		}

		$contents = $this->src;
		foreach ($this->params as $row) {
			if ($row->getSrc() == $this->src) {
				$contents = $row->getTo();
				break;
			}
		}

		$this->project->setNewProperty($this->property, $contents);
	}



	/**
	 *	Add a nested <match> tag.
	 */
	function createMatch()
	{
		$p = new TacoTranslateTaskMatch();
		$this->params[] = $p;
		return $p;
	}



	/**
	 * load properties from a file.
	 * @param PhingFile $file
	 */
	private function loadFile(PhingFile $file)
	{
		$this->log("Loading ". $file->getAbsolutePath(), $this->logLevel);
		// try to load file
		try {
			if ($file->exists()) {
				$props = new Properties();
				$props->load($file);
				return $props->getProperties();
			}
			else {
				$this->log("Unable to find property file: ". $file->getAbsolutePath() ."... skipped", Project::MSG_WARN);
				return [];
			}
		}
		catch (IOException $e) {
			throw new BuildException("Could not load properties from file.", $e);
		}
	}

}



/**
 * Supports the <match> nested tag for TacoTranslateTask.
 */
class TacoTranslateTaskMatch
{

	private $src = Null;
	private $to = Null;


	function setSrc($v)
	{
		$this->src = trim($v);
		return $this;
	}



	function getSrc()
	{
		return $this->src;
	}



	function setTo($v)
	{
		$this->to = trim($v);
		return $this;
	}



	function getTo()
	{
		return $this->to;
	}

}
