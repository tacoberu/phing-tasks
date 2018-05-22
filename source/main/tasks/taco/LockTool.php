<?php

namespace Taco\PhingTasks;

use DateTime, DateInterval;


/**
 * @author Martin Takáč <martin@takac.name>
 */
class LockTool
{

	const DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Maximálně čekají.
	 */
	private $expired = 'PT1H';


	/**
	 * Kde se bude uchovávat zámak.
	 */
	private $path = null;


	function __construct(\Project $project, $dir)
	{
		$this->path = (string) $dir;
		$this->project = $project;
	}



	function unlock()
	{
		$filename = $this->prepareLockFilename();
		if (file_exists($filename)) {
			if ($this->path) {
				$dict = (object) json_decode(file_get_contents($filename));
				$start = DateTime::createFromFormat(self::DATE_FORMAT, $dict->created);
				$end = new DateTime();
				file_put_contents($this->path . '/lock.statistics', self::toSeconds($start->diff($end)));
			}
			unlink($filename);
		}
	}


/*
	function getPid()
	{
		$filename = $this->prepareLockFilename();
		$f = @fopen($filename, 'x');
		if ($f == False) {
			$dict = (object) json_decode(file_get_contents($filename));
			$expired = DateTime::createFromFormat(self::DATE_FORMAT, $dict->created);
			$expired->add(new DateInterval($this->expired));
			if ($expired > new DateTime) {
				$end = $this->fetchStatistics(DateTime::createFromFormat(self::DATE_FORMAT, $dict->created)) ?: $expired;
				$dict->expired = $expired->format(self::DATE_FORMAT);
				$dict->end = $end->format(self::DATE_FORMAT);
				$dict->filename = $filename;
				return $dict;
			}
			unlink($filename);
			$f = @fopen($filename, 'x');
		}
		//~ fwrite($f, $this->prepareLockContent());
		//~ fclose($f);

		$dict = (object) json_decode(file_get_contents($filename));
		$dict->handler = $f;
		return $dict;
	}*/



	function lockPid()
	{
		$filename = $this->prepareLockFilename();
		$f = @fopen($filename, 'x');
		if ($f == False) {
			if ( ! $dict = json_decode(file_get_contents($filename))) {
				unlink($filename);
				return False;
			}
			$dict = (object) $dict;
			$expired = DateTime::createFromFormat(self::DATE_FORMAT, $dict->created);
			$expired->add(new DateInterval($this->expired));
			if ($expired > new DateTime) {
				$end = $this->fetchStatistics(DateTime::createFromFormat(self::DATE_FORMAT, $dict->created)) ?: $expired;
				$dict->expired = $expired->format(self::DATE_FORMAT);
				$dict->end = $end->format(self::DATE_FORMAT);
				$dict->filename = $filename;
				return $dict;
			}
			unlink($filename);
			$f = @fopen($filename, 'x');
		}
		fwrite($f, $this->prepareLockContent());
		fclose($f);
	}



	/**
	 *	Vygeneruje název souboru sloužící coby zámek.
	 */
	private function prepareLockFilename()
	{
		if ($this->path) {
			return $this->path . '/lock.pid';
		}
		else {
			return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phing-' . md5($this->project->getProperty('phing.file')) . '.pid';
		}
	}



	/**
	 *	Vygeneruje obsah souboru sloužící coby zámek.
	 */
	private function prepareLockContent()
	{
		return json_encode([
			'phing.file' => $this->project->getProperty('phing.file'),
			'phing.version' => $this->project->getPhingVersion(),
			'project.name' => $this->project->getName(),
			'project.description' => $this->project->getDescription(),
			'created' => (new DateTime())->format(self::DATE_FORMAT)
		], JSON_PRETTY_PRINT);
	}



	private function fetchStatistics($start)
	{
		if ($this->path && file_exists($this->path . '/lock.statistics')) {
			$end = (int) trim(file_get_contents($this->path . '/lock.statistics'));
			if (empty($end)) {
				return;
			}
			$start->add(new DateInterval("PT{$end}S"));
			return $start;
		}
	}



	private static function toSeconds(DateInterval $diff)
	{
		return ($diff->y * 365 * 24 * 60 * 60) +
			   ($diff->m * 30 * 24 * 60 * 60) +
			   ($diff->d * 24 * 60 * 60) +
			   ($diff->h * 60 * 60) +
			   ($diff->i * 60) +
			   $diff->s;
	}

}
