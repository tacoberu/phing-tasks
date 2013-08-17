<?php
/**
 * $Id: XmlLogger.php 552 2009-08-29 12:18:13Z mrook $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/BuildLogger.php';
require_once 'phing/listener/DefaultLogger.php';
require_once 'phing/listener/AnsiColorLogger.php';
require_once 'phing/listener/HtmlColorLogger.php';
require_once 'phing/system/util/Timer.php';

/**
 * Umožní logovat do více výstupů.
 *
 * @author Martin Takáč <martin@takac.name>
 * @package phing.listener
 */ 
class ChameleonLogger implements BuildLogger
{

	/**
	 * Seznam loggerů.
	 */
	private $loggers = array();



	/**
	 *  -Dchameleon.file.html.log=<soubor s logem>
	 *  -Dchameleon.file.html.error=<soubor s chybovkama>
	 */
	public function __construct($a)
	{
		if (Phing::getDefinedProperty('chameleon.html.log')) {
			$this->loggers['html'] = new HtmlColorLogger();
		}
		$this->loggers['ansi'] = new AnsiColorLogger();
	}



	// abstract method of BuildLogger



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
	public function setMessageOutputLevel($level)
	{
		foreach ($this->loggers as $loggers) {
			switch ($name) {
				case 'html':
					if ($value = Phing::getDefinedProperty('chameleon.html.level')) {
						$loggers->setMessageOutputLevel($value);
						break;
					}
				default:
					$loggers->setMessageOutputLevel($level);
			}
		}
	}



	/**
	 * Sets the output stream.
	 *
	 * @param OutputStream $output
	 * @see BuildLogger#setOutputStream()
	 */
	public function setOutputStream(OutputStream $output)
	{
		foreach ($this->loggers as $name => $loggers) {
			switch ($name) {
				case 'html':
					$filename = Phing::getDefinedProperty('chameleon.html.log');
					$loggers->setOutputStream(new OutputStream(fopen($filename, "w")));
					break;
				default:
					$loggers->setOutputStream($output);
			}
		}
	}



	/**
	 * Sets the error stream.
	 *
	 * @param OutputStream $err
	 * @see BuildLogger#setErrorStream()
	 */
	public function setErrorStream(OutputStream $output)
	{
		foreach ($this->loggers as $name => $loggers) {
			switch ($name) {
				case 'html':
					if ($filename = Phing::getDefinedProperty('chameleon.html.error')) {
						$loggers->setErrorStream(new OutputStream(fopen($filename, "w")));
						break;
					}
				default:
					$loggers->setErrorStream($output);
			}
		}
	}



	/**
	 *  Sets the start-time when the build started. Used for calculating
	 *  the build-time.
	 *
	 *  @param  object  The BuildEvent
	 *  @access public
	 */
	public function buildStarted(BuildEvent $event)
	{
		foreach ($this->loggers as $loggers) {
			$loggers->buildStarted($event);
		}
	}



	/**
	 *  Prints whether the build succeeded or failed, and any errors that
	 *  occured during the build. Also outputs the total build-time.
	 *
	 *  @param  object  The BuildEvent
	 *  @see    BuildEvent::getException()
	 */
	public function buildFinished(BuildEvent $event)
	{
		foreach ($this->loggers as $loggers) {
			$loggers->buildFinished($event);
		}
	}



	/**
	 *  Prints the current target name
	 *
	 *  @param  object  The BuildEvent
	 *  @access public
	 *  @see    BuildEvent::getTarget()
	 */
	public function targetStarted(BuildEvent $event)
	{
		foreach ($this->loggers as $loggers) {
			$loggers->targetStarted($event);
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
		foreach ($this->loggers as $loggers) {
			$loggers->targetFinished($event);
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
		foreach ($this->loggers as $loggers) {
			$loggers->taskStarted($event);
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
		foreach ($this->loggers as $loggers) {
			$loggers->taskFinished($event);
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
 		foreach ($this->loggers as $loggers) {
			$loggers->messageLogged($event);
		}
   }


}
