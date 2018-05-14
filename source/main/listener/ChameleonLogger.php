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

require_once 'phing/BuildLogger.php';
require_once 'phing/listener/DefaultLogger.php';
require_once 'phing/listener/AnsiColorLogger.php';
require_once 'phing/listener/HtmlColorLogger.php';
require_once 'phing/system/util/Timer.php';

/**
 * Umožní logovat do více výstupů.
 * Defaultní vypisuje na výstup v ansi formátu. A je-li nastaven html.log, tak vypisuje s jinou úrovní podrobností do html.
 *
 * @author Martin Takáč <martin@takac.name>
 * @package phing.taco.listener
 */
class ChameleonLogger implements BuildLogger
{

	/**
	 * Seznam loggerů.
	 */
	private $loggers = array();



	/**
	 *  -Dchameleon.html.log=<soubor s logem>
	 *  -Dchameleon.html.error=<soubor s chybovkama>
	 */
	function __construct($a)
	{
		if (Phing::getDefinedProperty('chameleon.html.log') || Phing::getDefinedProperty('chameleon.html.error')) {
			$this->loggers['html'] = new HtmlColorLogger();
		}
		$this->loggers['ansi'] = new AnsiColorLogger();
	}



	/**
	 *  Set the msgOutputLevel this logger is to respond to.
	 *
	 *  Only messages with a message level lower than or equal to the given
	 *  level are output to the log.
	 *
	 *  <p> Constants for the message levels are in Project.php. The order of
	 *  the levels, from least to most verbose, is:
	 *
	 *  <ul>
	 *    <li>Project::MSG_ERR</li>
	 *    <li>Project::MSG_WARN</li>
	 *    <li>Project::MSG_INFO</li>
	 *    <li>Project::MSG_VERBOSE</li>
	 *    <li>Project::MSG_DEBUG</li>
	 *  </ul>
	 *
	 *  The default message level for DefaultLogger is Project::MSG_ERR.
	 *
	 * @param int $level The logging level for the logger.
	 * @see BuildLogger#setMessageOutputLevel()
	 */
	function setMessageOutputLevel($level)
	{
		foreach ($this->loggers as $name => $logger) {
			switch ($name) {
				case 'html':
					if ($value = Phing::getDefinedProperty('chameleon.html.level')) {
						$logger->setMessageOutputLevel($value);
					}
					break; // Pokud není nastaven, ať převezme volbu z -verbose | -debug
				default:
					$logger->setMessageOutputLevel($level);
			}
		}
	}



	/**
	 * Sets the output stream.
	 *
	 * @param OutputStream $output
	 * @see BuildLogger#setOutputStream()
	 */
	function setOutputStream(OutputStream $output)
	{
		foreach ($this->loggers as $name => $logger) {
			switch ($name) {
				case 'html':
					if ($filename = Phing::getDefinedProperty('chameleon.html.log')) {
						$out = new OutputStream(fopen($filename, "w"));
					}
					else {
						// Pokud není nastaven, tak zahazujeme.
						$out = new OutputStreamNull();
					}
					$logger->setOutputStream($out);
					break;
				default:
					$logger->setOutputStream($output);
			}
		}
	}



	/**
	 * Sets the error stream.
	 *
	 * @param OutputStream $err
	 * @see BuildLogger#setErrorStream()
	 */
	function setErrorStream(OutputStream $output)
	{
		foreach ($this->loggers as $name => $logger) {
			switch ($name) {
				case 'html':
					if ($filename = Phing::getDefinedProperty('chameleon.html.error')) {
						$out = new OutputStream(fopen($filename, "w"));
					}
					else {
						// Pokud není nastaven, tak zahazujeme.
						$out = new OutputStreamNull();
					}
					$logger->setErrorStream($out);
					break;
				default:
					$logger->setErrorStream($output);
			}
		}
	}



	/**
	 *  Sets the start-time when the build started. Used for calculating
	 *  the build-time.
	 *
	 *  @param BuildEvent The BuildEvent
	 */
	function buildStarted(BuildEvent $event)
	{
		foreach ($this->loggers as $logger) {
			$logger->buildStarted($event);
		}
	}



	/**
	 *  Prints whether the build succeeded or failed, and any errors that
	 *  occured during the build. Also outputs the total build-time.
	 *
	 *  @param  BuildEvent  The BuildEvent
	 *  @see    BuildEvent::getException()
	 */
	function buildFinished(BuildEvent $event)
	{
		foreach ($this->loggers as $logger) {
			$logger->buildFinished($event);
		}
	}



	/**
	 *  Prints the current target name
	 *
	 *  @param  object  The BuildEvent
	 *  @see    BuildEvent::getTarget()
	 */
	function targetStarted(BuildEvent $event)
	{
		foreach ($this->loggers as $logger) {
			$logger->targetStarted($event);
		}
	}



	/**
	 *  Fired when a target has finished. We don't need specific action on this
	 *  event. So the methods are empty.
	 *
	 *  @param  object  The BuildEvent
	 *  @see    BuildEvent::getException()
	 */
	public function targetFinished(BuildEvent $event)
	{
		foreach ($this->loggers as $logger) {
			$logger->targetFinished($event);
		}
	}



	/**
	 *  Fired when a task is started. We don't need specific action on this
	 *  event. So the methods are empty.
	 *
	 *  @param  object  The BuildEvent
	 *  @access public
	 *  @see    BuildEvent::getTask()
	 */
	public function taskStarted(BuildEvent $event)
	{
		foreach ($this->loggers as $logger) {
			$logger->taskStarted($event);
		}
	}



	/**
	 *  Fired when a task has finished. We don't need specific action on this
	 *  event. So the methods are empty.
	 *
	 *  @param  object  The BuildEvent
	 *  @access public
	 *  @see    BuildEvent::getException()
	 */
	public function taskFinished(BuildEvent $event)
	{
		foreach ($this->loggers as $logger) {
			$logger->taskFinished($event);
		}
	}



    /**
     *  Print a message to the stdout.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getMessage()
     */
    public function messageLogged(BuildEvent $event)
    {
 		foreach ($this->loggers as $logger) {
			$logger->messageLogged($event);
		}
   }


}



class OutputStreamNull extends OutputStream
{

	function __construct()
	{}



	function write($buf, $off = null, $len = null)
	{}



	function flush()
	{}



	/**
	 * Returns a string representation of the attached PHP stream.
	 * @return string
	 */
	function __toString()
	{
		return '<<null-output>>';
	}

}
