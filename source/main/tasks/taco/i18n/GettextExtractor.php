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

namespace Taco\Tools\Gettext;



require_once __dir__ . '/filters/IFilter.php';



/**
 * GettextExtractor tool
 *
 * @author	 Karel Klima
 * @author	 Martin Takáč
 */
class GettextExtractor
{
	const LOG_FILE = '/extractor.log';
	const ESCAPE_CHARS = '"';


	/** @var resource */
	protected $logHandler;



	/** @var array */
	protected $inputFiles = array();


	/** @var array */
	protected $filters = array(
		'php' => array('PHP'),
		'phtml'	=> array('PHP', 'NetteLatte')
	);


	/** @var array */
	protected $comments = array(
		'Gettext keys exported by GettextExtractor'
	);


	/** @var array */
	protected $meta = array(
		'Content-Type' => 'text/plain; charset=UTF-8',
		'Plural-Forms' => 'nplurals=2; plural=(n != 1);'
	);



	/** @var array */
	protected $data = array();


	/** @var array */
	protected $filterStore = array();




	/**
	 * Close the log hangdler if needed
	 */
	public function __destruct()
	{
		if (is_resource($this->logHandler)) fclose($this->logHandler);
	}



	/**
	 * Writes messages into log or dumps them on screen
	 * @param string $message
	 */
	public function log($message)
	{
		if (is_resource($this->logHandler)) {
			fwrite($this->logHandler, $message . "\n");
		}
		else {
//			echo $message . "\n";
		}
	}



	/**
	 * Exception factory
	 * @param string $message
	 * @throws Exception
	 */
	protected function throwException($message)
	{
		$message = $message ? $message : 'Something unexpected occured. See GettextExtractor log for details';
		$this->log($message);
		//echo $message;
		throw new Exception($message);
	}



	/**
	 * Scans given files or directories and extracts gettext keys from the content
	 * @param string|array $resource
	 * @return GettetExtractor
	 */
	public function scan(\PhingFile $file)
	{
		$this->log('Extracting data from file ' . $file->getPath());
		$inputFile = $file->getPath();
		$info = pathinfo($file->getPath());
		foreach ($this->filters as $extension => $filters) {
			// Check file extension
			if ($info['extension'] !== $extension) {
				continue;
			}

			$this->log('Processing file ' . $inputFile);

			foreach ($filters as $filterName) {
				$filter = $this->getFilter($filterName);
				$filterData = $filter->extract($inputFile);
				$this->log('  Filter ' . $filterName . ' applied');
				$this->data = array_merge_recursive($this->data, $filterData);
			}
		}
	}



	/**
	 * Gets an instance of a GettextExtractor filter
	 * @param string $filter
	 * @return iFilter
	 */
	public function getFilter($filter)
	{
		$filter = $filter . 'Filter';

		if (isset($this->filterStore[$filter])) return $this->filterStore[$filter];

		if (!class_exists($filter)) {
			$filter_file = __dir__ . DIRECTORY_SEPARATOR . 'filters' . DIRECTORY_SEPARATOR . $filter . '.php';
			if (!file_exists($filter_file)) {
				$this->throwException('ERROR: Filter file ' . $filter_file . ' not found');
			}
			require_once $filter_file;
			if (!class_exists($filter)) {
				$this->throwException('ERROR: Class ' . $filter . ' not found');
			}
		}

		$this->filterStore[$filter] = new $filter;
		$this->log('Filter ' . $filter . ' loaded');
		return $this->filterStore[$filter];
	}



	/**
	 * Assigns a filter to an extension
	 * @param string $extension
	 * @param string $filter
	 * @return GettextExtractor
	 */
	public function setFilter($extension, $filter)
	{
		if (isset($this->filters[$extension]) && in_array($filter, $this->filters[$extension])) return $this;
		$this->filters[$extension][] = $filter;
		return $this;
	}



	/**
	 * Removes all filter settings in case we want to define a brand new one
	 * @return GettextExtractor
	 */
	public function removeAllFilters()
	{
		$this->filters = array();
		return $this;
	}



	/**
	 * Adds a comment to the top of the output file
	 * @param string $value
	 * @return GettextExtractor
	 */
	public function addComment($value) {
		$this->comments[] = $value;
		return $this;
	}

	/**
	 * Gets a value of a meta key
	 * @param string $key
	 */
	public function getMeta($key)
	{
		return isset($this->meta[$key]) ? $this->meta[$key] : NULL;
	}

	/**
	 * Sets a value of a meta key
	 * @param string $key
	 * @param string $value
	 * @return GettextExtractor
	 */
	public function setMeta($key, $value)
	{
		$this->meta[$key] = $value;
		return $this;
	}



	/**
	 * Saves extracted data into gettext file
	 * @param string $outputFile
	 * @param array $data
	 * @return GettextExtractor
	 */
	public function save($outputFile, $data = null)
	{
		$data = $data ? $data : $this->data;

		// Output file permission check
		if (file_exists($outputFile) && !is_writable($outputFile)) {
			$this->throwException('ERROR: Output file is not writable!');
		}

		$handle = fopen($outputFile, "w");

		fwrite($handle, $this->formatData($data));

		fclose($handle);

		$this->log("Output file '$outputFile' created");

		return $this;
	}



	/**
	 * Formats fetched data to gettext syntax
	 * @param array $data
	 * @return string
	 */
	protected function formatData($data)
	{
		$output = array();
		foreach ($this->comments as $comment) {
			$output[] = '# ' . $comment;
		}
		$output[] = '# Created: ' . date('c');
		$output[] = 'msgid ""';
		$output[] = 'msgstr ""';
		foreach ($this->meta as $key => $value) {
			$output[] = '"' . $key . ': ' . $value . '\n"';
		}
		$output[] = '';

		ksort($data);

		foreach ($data as $key => $files)
		{
			ksort($files);
			foreach ($files as $file)
				$output[] = '# ' . $file;
			$output[] = 'msgid "' . $this->addSlashes($key) . '"';
			/*if (preg_match($this->pluralMatchRegexp, $key, $matches)) { // TODO: really export plurals? deprecated for now
				$output[] = 'msgid_plural "' . addslashes($key) . '"';
				//$output[] = 'msgid_plural ""';
				$output[] = 'msgstr[0] "' . addslashes($key) . '"';
				$output[] = 'msgstr[1] "' . addslashes($key) . '"';
			} else {
				$output[] = 'msgstr "' . addslashes($key) . '"';
			}*/
			$output[] = 'msgstr "' . $this->addSlashes($key) . '"';
			$output[] = '';
		}

		return join("\n", $output);
	}

	/**
	 * Escape a sring not to break the gettext syntax
	 * @param string $string
	 * @return string
	 */
	public function addSlashes($string)
	{
		return addcslashes($string, self::ESCAPE_CHARS);
	}

}
