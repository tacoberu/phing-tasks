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
 * Register a datatype for use within a buildfile.
 * 
 * This is for registering your own datatypes for use within a buildfile.
 * 
 * <taco.typedef name="mytype" classname="path.to.MyHandlingClass"/>
 * or
 *
 * 	<taco.typedef file="${project.basedir}/source/main/types/types.properties"
 * 			classpath="${project.basedir}/source/main"
 * 			/>
 *  
 * <sometask ...>
 *	 <mytype param1="val1" param2="val2"/>
 * </sometask>
 * 
 * @package  phing.tasks.taco
 */
class TacoTypedefTask extends Task
{
	
	/** 
	 * Tag name for datatype that will be used in XML 
	 */
	private $name;

	
	/**
	 * Classname of task to register.
	 * This can be a dot-path -- relative to a location on PHP include_path.
	 * E.g. path.to.MyClass ->  path/to/MyClass.php
	 * @var string
	 */
	private $classname;
	

	/**
	 * Path to add to PHP include_path to aid in finding specified class.
	 * @var Path
	 */
	private $classpath;

	
	/** 
	 * Refid to already defined classpath 
	 */
	private $classpathId;
	


	/**
	 * Name of file to load multiple definitions from.
	 * @var string
	 */
	private $typeFile;


	/**
	 * Set the classpath to be used when searching for component being defined
	 * 
	 * @param Path $classpath A Path object containing the classpath.
	 */
	public function setClasspath(Path $classpath)
	{
		if ($this->classpath === null) {
			$this->classpath = $classpath;
		}
		else {
			$this->classpath->append($classpath);
		}

		return $this;
	}



	/**
	 * Create the classpath to be used when searching for component being defined
	 * 
	 * @return Path
	 */ 
	public function createClasspath()
	{
		if ($this->classpath === null) {
			$this->classpath = new Path($this->project);
		}

		return $this->classpath->createPath();
	}



	/**
	 * Reference to a classpath to use when loading the files.
	 */
	public function setClasspathRef(Reference $r)
	{
		$this->classpathId = $r->getRefId();
		$this->createClasspath()->setRefid($r);

		return $this;
	}


	/**
	 * Sets the name that will be used in XML buildfile.
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}



	/**
	 * Sets the class name / dotpath to use.
	 * @param string $class
	 */
	public function setClassname($class)
	{
		$this->classname = $class;
		return $this;
	}



	/**
	 * Sets the file of definitionas to use to use.
	 * @param string $file
	 */
	public function setFile($file)
	{
		$this->typeFile = $file;
	}



	/** 
	 * Main entry point 
	 */
	public function _main()
	{
		if ($this->name === null || $this->classname === null) {
			throw new BuildException("You must specify name and class attributes for <typedef>.");
		}		
		$this->project->addDataTypeDefinition($this->name, $this->classname, $this->classpath);
	}



	/** 
	 * Main entry point 
	 */
	public function main()
	{
		if ($this->typeFile === null
				&& ($this->name === null || $this->classname === null)) {
			throw new BuildException("You must specify name and class attributes for <typedef>.");
		}

		if ($this->typeFile == null) {
			$this->log("Task " . $this->name . " will be handled by class " . $this->classname, Project::MSG_VERBOSE);
			$this->project->addDataTypeDefinition($this->name, $this->classname, $this->classpath);
		}
		else {
			// try to load taskdefs given in file
			try {
				$props = new Properties();
				$in = new PhingFile((string) $this->typeFile);

				if ($in === null) {
					throw new BuildException("Can't load type list {$this->typeFile}");
				}
				$props->load($in);

				$enum = $props->propertyNames();
				foreach($enum as $key) {
					$value = $props->getProperty($key);
					$this->project->addDataTypeDefinition($key, $value, $this->classpath);
				}
			}
			catch (IOException $ioe) {
				throw new BuildException("Can't load type list {$this->typeFile}");
			}
		}
	}



}
