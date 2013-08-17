<?php
/*
 *  $Id: c3ac5fcdf4d7cdb199d57b021e3f015c9c7fd3f8 $
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

require_once __dir__ . '/GettextExtractor.php';

require_once 'phing/tasks/system/MatchingTask.php';
include_once 'phing/util/SourceFileScanner.php';
include_once 'phing/mappers/MergeMapper.php';
include_once 'phing/util/StringHelper.php';

/**
 * This is a FileSet with the option to specify permissions.
 *
 * Permissions are currently not implemented by PEAR Archive_Tar,
 * but hopefully they will be in the future.
 *
 * @package   phing.tasks.ext
 */
class GettextFileSet extends FileSet
{

	private $files = null;

	private $mode = 0100644;

	private $userName = "";
	private $groupName = "";
	private $fullpath = "";
	private $preserveLeadingSlashes = false;




	/**
	 *  Get a list of files and directories specified in the fileset.
	 *  @return array a list of file and directory names, relative to
	 *	the baseDir for the project.
	 */
	public function getFiles(Project $p, $includeEmpty = true)
	{
		if ($this->files === null) {

			$ds = $this->getDirectoryScanner($p);
			$this->files = $ds->getIncludedFiles();

			if ($includeEmpty) {

				// first any empty directories that will not be implicitly added by any of the files
				$implicitDirs = array();
				foreach ($this->files as $file) {
					$implicitDirs[] = dirname($file);
				}

				$incDirs = $ds->getIncludedDirectories();

				// we'll need to add to that list of implicit dirs any directories
				// that contain other *directories* (and not files), since otherwise
				// we get duplicate directories in the resulting tar
				foreach ($incDirs as $dir) {
					foreach ($incDirs as $dircheck) {
						if (!empty($dir) && $dir == dirname($dircheck)) {
							$implicitDirs[] = $dir;
						}
					}
				}

				$implicitDirs = array_unique($implicitDirs);

				// Now add any empty dirs (dirs not covered by the implicit dirs)
				// to the files array.

				foreach ($incDirs as $dir) { // we cannot simply use array_diff() since we want to disregard empty/. dirs
					if ($dir != "" && $dir != "." && !in_array($dir, $implicitDirs)) {
						// it's an empty dir, so we'll add it.
						$this->files[] = $dir;
					}
				}
			} // if $includeEmpty

		} // if ($this->files===null)

		return $this->files;
	}




	/**
	 * A 3 digit octal string, specify the user, group and
	 * other modes in the standard Unix fashion;
	 * optional, default=0644
	 * @param string $octalString
	 */
	public function setMode($octalString)
	{
		$octal = (int) $octalString;
		$this->mode = 0100000 | $octal;
	}



	public function getMode()
	{
		return $this->mode;
	}



	/**
	 * The username for the tar entry
	 * This is not the same as the UID, which is
	 * not currently set by the task.
	 */
	public function setUserName($userName)
	{
		$this->userName = $userName;
	}



	public function getUserName()
	{
		return $this->userName;
	}



	/**
	 * The groupname for the tar entry; optional, default=""
	 * This is not the same as the GID, which is
	 * not currently set by the task.
	 */
	public function setGroup($groupName)
	{
		$this->groupName = $groupName;
	}



	public function getGroup()
	{
		return $this->groupName;
	}



	/**
	 * If the fullpath attribute is set, the file in the fileset
	 * is written with that path in the archive. The prefix attribute,
	 * if specified, is ignored. It is an error to have more than one file specified in
	 * such a fileset.
	 */
	public function setFullpath($fullpath)
	{
		$this->fullpath = $fullpath;
	}



	public function getFullpath()
	{
		return $this->fullpath;
	}



	/**
	 * Flag to indicates whether leading `/'s should
	 * be preserved in the file names.
	 * Optional, default is <code>false</code>.
	 * @return void
	 */
	public function setPreserveLeadingSlashes($b)
	{
		$this->preserveLeadingSlashes = (boolean) $b;
	}



	public function getPreserveLeadingSlashes()
	{
		return $this->preserveLeadingSlashes;
	}



}
