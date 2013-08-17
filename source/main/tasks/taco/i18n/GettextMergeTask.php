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
require_once __dir__ . '/GettextFileSet.php';

require_once 'phing/tasks/system/MatchingTask.php';
include_once 'phing/util/SourceFileScanner.php';
include_once 'phing/mappers/MergeMapper.php';
include_once 'phing/util/StringHelper.php';

/**
 * Creates a tar archive using PEAR Archive_Tar.
 *
 * @author	Hans Lellelid <hans@xmpl.org> (Phing)
 * @author	Stefano Mazzocchi <stefano@apache.org> (Ant)
 * @author	Stefan Bodewig <stefan.bodewig@epost.de> (Ant)
 * @author	Magesh Umasankar
 * @version   $Id: c3ac5fcdf4d7cdb199d57b021e3f015c9c7fd3f8 $
 * @package   phing.tasks.ext
 */
class GettextMergeTask extends MatchingTask
{

	/**
	 *	Jméno projektu.
	 */
	private $file;

	/**
	 *	Cesta k souborům.
	 */
	private $path;

	/**
	 *
	 */
	private $type = 'LC_MESSAGES';

	/**
	 *	Jméno lokalizace
	 */
	private $language = 'en_GB';

	private $baseDir;

	private $includeEmpty = true; // Whether to include empty dirs in the TAR

	private $filesets = array();
	private $fileSetFiles = array();



	/**
	 * Add a new fileset
	 * @return FileSet
	 */
	public function createGettextFileSet()
	{
		$this->fileset = new GettextFileSet();
		$this->filesets[] = $this->fileset;
		return $this->fileset;
	}



	/**
	 * Add a new fileset.  Alias to createGettextFileSet() for backwards compatibility.
	 * @return FileSet
	 * @see createGettextFileSet()
	 */
	public function createFileSet()
	{
		return $this->createGettextFileSet();
	}




	/**
	 * Set is the name/location of where to create the tar file.
	 * @param PhingFile $destFile The output of the tar
	 */
	public function setSource(PhingFile $m)
	{
		$this->file = $m;
	}



	/**
	 * @param
	 */
	public function setLanguage($m)
	{
		$this->language = $m;
	}



	/**
	 * This is the base directory to look in for things to tar.
	 * @param PhingFile $baseDir
	 */
	public function setBasedir(PhingFile $baseDir)
	{
		$this->baseDir = $baseDir;
	}



	/**
	 * Set the include empty dirs flag.
	 * @param  boolean  Flag if empty dirs should be tarred too
	 * @return void
	 * @access public
	 */
	public function setIncludeEmptyDirs($bool)
	{
		$this->includeEmpty = (boolean) $bool;
	}



	/**
	 * do the work
	 * @throws BuildException
	 */
	public function main()
	{
		if ($this->file === null) {
			throw new BuildException("po file must be set!", $this->getLocation());
		}

		if ($this->file->exists() && $this->file->isDirectory()) {
			throw new BuildException("po file is a directory!", $this->getLocation());
		}

		if ($this->file->exists() && !$this->file->canWrite()) {
			throw new BuildException("Can not write to the specified po file!", $this->getLocation());
		}

//		if (!$file->getParentFile()->exists()) {
//			if (!$file->getParentFile()->getParentFile()->exists()) {
//				$file->getParentFile()->getParentFile()->mkdir();
//			}
//			$file->getParentFile()->mkdir();
//		}

		// shouldn't need to clone, since the entries in filesets
		// themselves won't be modified -- only elements will be added
		$savedFileSets = $this->filesets;

		try {
			if ($this->baseDir !== null) {
				if (!$this->baseDir->exists()) {
					throw new BuildException("basedir '" . (string) $this->baseDir . "' does not exist!", $this->getLocation());
				}
				if (empty($this->filesets)) { // if there weren't any explicit filesets specivied, then
											  // create a default, all-inclusive fileset using the specified basedir.
					$mainFileSet = new GettextFileSet($this->fileset);
					$mainFileSet->setDir($this->baseDir);
					$this->filesets[] = $mainFileSet;
				}
			}

			if (empty($this->filesets)) {
				throw new BuildException("You must supply either a basedir "
										 . "attribute or some nested filesets.",
										 $this->getLocation());
			}

			$this->log("Merge po gettext: " . $this->file->__toString(), Project::MSG_INFO);

			foreach ($this->filesets as $fs) {
				$files = $fs->getFiles($this->project, $this->includeEmpty);
				if (count($files) > 1 && strlen($fs->getFullpath()) > 0) {
					throw new BuildException("fullpath attribute may only "
											 . "be specified for "
											 . "filesets that specify a "
											 . "single file.");
				}
				$fsBasedir = $fs->getDir($this->project);
				for ($i = 0, $fcount = count($files); $i < $fcount; $i++) {
					$f = new PhingFile($fsBasedir, $files[$i]);
					passthru('msgmerge -U --no-wrap ' . $f->getPath() . ' ' . $this->file->getPath());
					$this->log("Merging file [" . $f->getPath() . '].', Project::MSG_VERBOSE);
				}
			}
		}
		catch (IOException $ioe) {
			$msg = "Problem scanning TAR: " . $ioe->getMessage();
			$this->filesets = $savedFileSets;
			throw new BuildException($msg, $ioe, $this->getLocation());
		}

		$this->filesets = $savedFileSets;
	}


}
