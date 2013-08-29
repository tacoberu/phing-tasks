<?php

//	Umístění vendoru.
defined('VENDOR_DIR') || define('VENDOR_DIR', __dir__ . '/../../vendor');

//	Umístění kořenu testovacího prostředí.
defined('PHING_TEST_BASE') || define('PHING_TEST_BASE', VENDOR_DIR . '/phing/phing/test');

set_include_path(
	//	Cesty k regulérním třídám.
	realpath(PHING_TEST_BASE . '/../classes') . PATH_SEPARATOR .
	//	Cesty k třídám testů.
	realpath(PHING_TEST_BASE . '/classes') . PATH_SEPARATOR .
	// trunk version of phing classes should take precedence
	get_include_path()
);
require_once PHING_TEST_BASE . '/classes/phing/BuildFileTest.php';
require_once 'phing/Phing.php';

Phing::setProperty('phing.home', realpath(VENDOR_DIR . '/../temp'));
Phing::startup();

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT);

require_once VENDOR_DIR . '/autoload.php';
