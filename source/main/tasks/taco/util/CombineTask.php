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
 *  Compine
 * 			<util.combine property="domains.list" separator="${line.separator}" format="branch-%branch%-%client%">
 * 				<param name="branch" value="${branches.list}" separator=","/>
 * 				<param name="client" value="${clients.list}"/>
 * 				<filterchain>
 * 					<replaceregexp>
 * 						<regexp pattern="branch\-default\-" replace=""/>
 * 					</replaceregexp>
 * 				</filterchain>
 * 			</util.combine>
 *
 *  @package  phing.tasks.taco
 */
class CombineTask extends Task
{

	/**
	 *	Seznam parametrů.
	 *	@var array of CombineParam
	 */
	protected $params = array(); // parameters for func_tion calls


	/**
	 *	all filterchains objects assigned to this task
	 *	@var array of FilterChain
	 */
	protected $filterChains = array();



	/**
	 * Property to be set
	 * @var string $property
	 */
	private $property;



	/**
	 * Oddělovač jednotlivých branchí.
	 * @var string $property
	 */
	private $separator = ',';



	/**
	 * Formát výstupu. name, id, changset
	 */
	private $format = Null;



	/**
	 *	Vykonání akce.
	 */
	function main()
	{
		$copy = $this->params;
		$values = array();
		$output = $this->map($values, $copy);
		$output = $this->applyFilters($output);
		$output = implode($this->separator, $output);
		$this->project->setProperty($this->property, $output);
	}



	/**
	 * 
	 */
	public function applyFilters(array $items)
	{
		if (count($items) && (is_array($this->filterChains)) && (!empty($this->filterChains))) {	
			$ret = array();
			foreach ($items as $row) {
				$in = FileUtils::getChainedReader(new StringReader($row), $this->filterChains, $this->getProject());
				$ret[] = $in->read();
			}
			return $ret;
		}
		return $items;
	}




	/**
	 * Set name of property to be set
	 * @param $property
	 * @return this
	 */
	private function map(array $values, array $items)
	{
		$ret = array();
		$item = array_shift($items);
		foreach ($item->getValues() as $row) {
			$values['%' . $item->getName() . '%'] = $row;
			if (count($items)) {
				$ret = array_merge($ret, self::map($values, $items));
			}
			else {
				$ret[] = strtr($this->format, $values);
			}
		}
		return $ret;
	}



	/**
	 * Set name of property to be set
	 * @param $property
	 * @return this
	 */
	public function setProperty($property)
	{
		$this->property = $property;
		return $this;
	}



	/**
	 * Oddělovače jednotlivých prvků.
	 *
	 * @param string 
	 * @return this
	 */
	public function setSeparator($value)
	{
		$this->separator = $value;
		return $this;
	}



	/**
	 *	Formát výstupu. Máme nějaké kousky, a z nich můžeme poskládát výstup.
	 *	Seznam placeholdrů:
	 *		%id%	ciselen id changesetu.
	 *		%name%	Jméno branche.
	 *		%changeset%	hexa hash changesetu.
	 *
	 *	@param string 
	 *	@return this
	 */
	public function setFormat($value)
	{
		$this->format = $value;
		return $this;
	}



	/**
	 *	Supporting the <echo>Message</echo> syntax. 
	 */
	function addText($msg)
	{
		$this->msg = (string) $msg;
	}


	/**
	 *	Add a nested <param> tag.
	 */
	public function createParam()
	{
		$p = new CombineParam();
		$this->params[] = $p;
		return $p;
	}		



	/**
	 * Creates a filterchain
	 *
	 * @access public
	 * @return  object  The created filterchain object
	 */
	function createFilterChain()
	{
		$num = array_push($this->filterChains, new FilterChain($this->project));
		return $this->filterChains[$num-1];
	}



}



/**
 * Supports the <param> nested tag for PhpTask.
 *
 * @package  phing.tasks.system
 */
class CombineParam {

	private $raw = Null;
	private $name = Null;
	private $values = Null;
	private $separator = ',';

	
	public function setName($v)
	{
		$this->name = trim($v);
		return $this;
	}

	
	public function getName()
	{
		return $this->name;
	}

	
	public function setValue($v)
	{
		$this->raw = trim($v);
		return $this;
	}


	
	public function setSeparator($v)
	{
		$this->separator = $v;
		return $this;
	}
	
	
	/**
	 *	Supporting the <param>Message</param> syntax. 
	 */
	public function addText($v)
	{
		$this->raw = $v;
	}
	
	
	/**
	 *	Vypočítá a vrátí pole záznamů.
	 */
	public function getValues()
	{
		if (empty($this->values)) {
			$this->values = self::parseRaw($this->raw, $this->separator);
		}
		return $this->values;
	}



	private static function parseRaw($raw, $separator)
	{
		return explode($separator, $raw);
	}


}
