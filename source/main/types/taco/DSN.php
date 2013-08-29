<?php

require_once "phing/types/DataType.php";


/**
 * This Type represents a DB Connection.
 */
class DSN extends DataType
{

	private $url;
	private $driver;
	private $host;
	private $database;
	private $username;
	private $password;
	private $persistent = false;



	/**
	 * Sets the URL part: mysql://localhost/mydatabase
	 */
	public function setUrl($url)
	{
		$this->url = $url;
		if (! preg_match('~(\w+)\:\/\/([\w_.-]+)(\/[\w_.-]+)?~', $url, $matches)) {
			throw new \RuntimeException("Invalid url: [$url].");
		}

		$this->driver = $matches[1];
		$this->host = $matches[2];
		$this->database = $matches[3];
	}



	/**
	 * Sets username to use in connection.
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}



	/**
	 * Sets password to use in connection.
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}



	/**
	 * Set whether to use persistent connection.
	 * @param boolean $persist
	 */
	public function setPersistent($persist)
	{
		$this->persistent = (boolean) $persist;
	}



	public function getUrl(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getUrl($p);
		}
		return $this->url;
	}



	public function getDriver(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getDriver($p);
		}
		return $this->driver;
	}



	public function getHost(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getHost($p);
		}
		return $this->host;
	}



	public function getDatabase(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getDatabase($p);
		}
		return $this->database;
	}



	public function getUsername(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getUsername($p);
		}
		return $this->username;
	}



	public function getPassword(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getPassword($p);
		}
		return $this->password;
	}



	public function getPersistent(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getPersistent($p);
		}
		return $this->persistent;
	}



	/**
	 * Gets a combined hash/array for DSN as used by PEAR.
	 * @return array
	 */
	public function getPEARDSN(Project $p)
	{
		if ($this->isReference()) {
			return $this->getRef($p)->getPEARDSN($p);
		}

		include_once 'DB.php';
		$dsninfo = DB::parseDSN($this->url);
		$dsninfo['username'] = $this->username;
		$dsninfo['password'] = $this->password;
		$dsninfo['persistent'] = $this->persistent;

		return $dsninfo;
	}



	/**
	 * Your datatype must implement this function, which ensures that there
	 * are no circular references and that the reference is of the correct
	 * type (DSN in this example).
	 *
	 * @return DSN
	 */
	public function getRef(Project $p)
	{
		if ( !$this->checked ) {
			$stk = array();
			array_push($stk, $this);
			$this->dieOnCircularReference($stk, $p);
		}
		$o = $this->ref->getReferencedObject($p);
		
		if ( !($o instanceof DSN) ) {
			throw new BuildException($this->ref->getRefId()." doesn't denote a DSN");
		}

		return $o;
	}


}
