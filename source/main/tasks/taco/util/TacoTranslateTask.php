<?php

require_once 'phing/Task.php';

/**
 *		<taco.translate src="${env.HG_URL}" property="repo.sender">
 *			<match src='remote:ssh:10.18.0.6' to="martintakac" />
 *			<match src='remote:ssh:10.18.10.6' to="johndee" />
 *		</taco.translate>
 *
 * @author Martin Takáč <martin@takac.name>
 */
class TacoTranslateTask extends Task
{

	/**
	 *	Seznam parametrů.
	 *	@var array of CombineParam
	 */
	private $params = array(); // parameters for func_tion calls


	/**
	 * Property to be set
	 * @var string $property
	 */
	private $property;


	/**
	 * @var string
	 */
	private $src;


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



	function main()
	{
        if (empty($this->src)) {
            throw new BuildException("Attribute 'src' required", $this->getLocation());
        }

        if (empty($this->property)) {
            throw new BuildException("Attribute 'property' required", $this->getLocation());
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

}



/**
 * Supports the <match> nested tag for PhpTask.
 */
class TacoTranslateTaskMatch {

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
