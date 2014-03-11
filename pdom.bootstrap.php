<?php
/**
 * PDOm - PDO Wrapper with MySQL Helper
 * 
 * Requirements:
 *	- PHP 5.4+
 *	- PHP PDO database extension <http://www.php.net/manual/en/book.pdo.php>
 *	- Database table names cannot include character '/'
 * 
 * @package PDOm
 * @version 1.0.b - Mar 11, 2014
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <http://www.shayanderson.com/projects/pdom.htm>
 */

/**
 * PDOm bootstrap
 */

// import PDOm files
require_once './lib/Pdom/Pdo.php';
require_once './lib/Pdom/pdom.php';

// register database connection
pdom([
	'host' => 'localhost',
	'database' => 'test',
	'user' => 'app',
	'password' => 'pass00',
	'errors' => true,
	'debug' => true,
	// 'objects' => false
]);