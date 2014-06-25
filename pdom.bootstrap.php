<?php
/**
 * PDOm bootstrap
 */

// import PDOm files
require_once './lib/Pdom/Pdo.php';
require_once './lib/Pdom/pdom.php';

// register database connection
pdom([
	// database connection params
	'host' => 'localhost',
	'database' => 'test',
	'user' => 'myuser',
	'password' => 'mypass',

	// display errors (default true)
	// 'errors' => false,

	// debug messages and errors to log (default true)
	// 'debug' => false,

	// return objects instead of arrays (default true)
	// 'objects' => false
]);