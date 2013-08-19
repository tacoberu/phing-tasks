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

require_once 'phing/Task.php';

/**
 * LoadFileTask
 *
 * Loads a (text) file and stores the contents in a property.
 * Supports filterchains.
 *
 * @package phing.tasks.taco
 */
class FsLoadFileTask extends Task
{
	/**
	 * File to read
	 * @var PhingFile file
	 */
	private $file;

	/**
	 * Property to be set
	 * @var string $property
	 */
	private $property;
	
	/**
	 * Array of FilterChain objects
	 * @var FilterChain[]
	 */
	private $filterChains = array();


	/**
	 * Whether to check the return code.
	 * @var boolean
	 */
	protected $checkreturn = false;



	/**
	 * Set file to read
	 * @param PhingFile $file
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	 * Convenience setter to maintain Ant compatibility (@see setFile())
	 * @param PhingFile $file
	 */
	public function setSrcFile($srcFile)
	{
		$this->file = $srcFile;
	}
	
	/**
	 * Set name of property to be set
	 * @param $property
	 * @return void
	 */
	public function setProperty($property)
	{
		$this->property = $property;
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
	 * Creates a filterchain
	 *
	 * @return  object  The created filterchain object
	 */
	function createFilterChain() {
		$num = array_push($this->filterChains, new FilterChain($this->project));
		return $this->filterChains[$num-1];
	}					



	/**
	 * Main method
	 *
	 * @return  void
	 * @throws  BuildException
	 */
	public function main()
	{
		if (empty($this->file)) {
			throw new BuildException("Attribute 'file' required", $this->getLocation());
		}
		
		if (empty($this->property)) {
			throw new BuildException("Attribute 'property' required", $this->getLocation());
		}
		
		// read file (through filterchains)
		$contents = "";

		try {
			$reader = FileUtils::getChainedReader(new FileReader($this->file), $this->filterChains, $this->project);
			while(-1 !== ($buffer = $reader->read())) {
				$contents .= $buffer;
			}
			$reader->close();
		}
		catch (\Exception $e) {
			if ($this->checkreturn) {
				throw $e;
			}
			else {
				$this->log($e->getMessage(), Project::MSG_VERBOSE);			
			}
		}
		
		// publish as property
		$this->project->setProperty($this->property, $contents);
	}





}
