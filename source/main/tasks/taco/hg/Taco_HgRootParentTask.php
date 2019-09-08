<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

require_once 'phing/Task.php';
require_once 'phing/util/FileUtils.php';

/**
 * Lookup start rev from default branch. Cross many branches.
 *
 * hg log -b cesys-608 -r : -l 1 --template="{rev}"
 *
 * @author   Martin Takáč <martin@takac.name>
 * @package  phing.tasks.taco
 */
class Taco_HgRootParentTask extends Taco_HgBaseTask
{


	/**
	 * @var string
	 */
	private $branch;


	/**
	 * @var string
	 */
	private $format = 'id';


	function setBranch($val)
	{
		$this->branch = $val;
		return $this;
	}



	function setFormat($val)
	{
		$this->format = $val;
		return $this;
	}



	/**
	 * @throw BuildException that not requred params.
	 */
	protected function assertRequiredParams()
	{
		parent::assertRequiredParams();

		if (empty($this->branch)) {
			throw new BuildException("branch is missing.");
		}
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function executeCommand()
	{
		$branch = $this->branch;
		while ($branch != 'default') {
			$id = $rev;
			$rev = $this->executeFirstrev($branch);
			list($id, $branch) = $this->executeParentRev($rev);
		}
		return (object)['content' => [$rev], 'code' => 0];
	}



	/**
	 * hg log -b cesys-608 -r : -l 1 --template="{rev}"
	 * @return int
	 */
	private function executeFirstrev($branch)
	{
		$this->action = 'log';
		$exec = $this->buildExecute();
		$exec->arg('-b ' . $branch);
		$exec->arg('-r :');
		$exec->arg('-l 1');
		$exec->arg('--template "{rev}"');
		return reset($exec->run()->content);
	}



	/**
	 * hg parent -r 25890 --template="{rev}|{branch}"
	 * @return int
	 */
	private function executeParentRev($rev)
	{
		$this->action = 'parent';
		$exec = $this->buildExecute();
		$exec->arg('-r ' . $rev);
		$exec->arg('--template "{rev}|{branch}"');
		return explode('|', reset($exec->run()->content), 2);
	}



	/**
	 * Executes the command and returns return code and output.
	 *
	 * @return array array(return code, array with output)
	 */
	protected function buildExecute()
	{
		$exec = parent::buildExecute();
		$exec->setWorkDirectory($this->repository->getPath());
		return $exec;
	}



	/**
	 * Zpracovat výstup. Rozprazsuje řádek, vyfiltruje jej zda je větší jak revize a naformátuje jej do výstupu.
	 *
	 * @param array of string Položky branch + id:hash
	 *
	 * @return string
	 */
	protected function __formatOutput(array $output)
	{
		return reset($output);
	}

}
