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
 * @version 1.0.b - Mar 13, 2014
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <https://github.com/shayanderson/pdom>
 */
namespace Pdom;

/**
 * PDOm core class
 *
 * @author Shay Anderson 03.14 <http://www.shayanderson.com/contact>
 */
class Pdo
{
	/**
	 * Default primary key column name
	 */
	const DEFAULT_PRIMARY_KEY_COLUMN = 'id';

	/**
	 * Current connection ID
	 *
	 * @var int
	 */
	private static $__connection_id = 0;

	/**
	 * Connection ID
	 *
	 * @var int
	 */
	private $__id;

	/**
	 * Error occurred flag
	 *
	 * @var boolean
	 */
	private $__is_error = false;

	/**
	 * Last error string (when error occurs)
	 *
	 * @var string
	 */
	private $__last_error;

	/**
	 * Configuration settings
	 *
	 * @var array
	 */
	protected $_conf = [
		'debug' => false,
		'errors' => true,
		'objects' => true
	];

	/**
	 * Debug log
	 *
	 * @var array
	 */
	protected $_log = [];

	/**
	 * Primary key column name to table map
	 *
	 * @var array
	 */
	protected $_key_map;

	/**
	 * PDO object instance
	 *
	 * @var \PDO
	 */
	private $__pdo;

	/**
	 * Init
	 *
	 * @param int $id
	 * @param string $host
	 * @param string $database
	 * @param string $user
	 * @param string $password
	 * @param array $conf
	 */
	public function __construct($id, $host, $database, $user, $password, $conf)
	{
		$this->__id = $id;

		foreach($conf as $k => $v) // conf setter
		{
			if(isset($this->_conf[$k]) || array_key_exists($k, $this->_conf))
			{
				$this->_conf[$k] = $v;
			}
		}

		try
		{
			$this->__pdo = new \PDO('mysql:host=' . $host . ';dbname=' . $database, $user, $password);
			$this->__pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->_log('Connection "' . $this->__id . '" registered (host: "' . $host
				. '", database: "' . $database . '")');
		}
		catch(PDOException $e)
		{
			$this->_error($e->getMessage());
		}
	}

	/**
	 * Close PDO object connection
	 */
	public function __destruct()
	{
		$this->__pdo = null;
	}

	/**
	 * Init (static)
	 *
	 * @throws \Exception
	 */
	private static function __init()
	{
		if(!class_exists('\\PDO'))
		{
			throw new \Exception('Failed to find \\PDO class, install PDO (PHP Data Objects)');
		}
	}

	/**
	 * Trigger error
	 *
	 * @param string $message
	 * @return void
	 * @throws \Exception
	 */
	protected function _error($message)
	{
		$message = 'Error: ' . $message;
		$this->__is_error = true;
		$this->__last_error = $message;
		$this->_log($message);

		if($this->_conf['errors'])
		{
			throw new \Exception('Pdom: ' . $message);
		}
	}

	/**
	 * Add debug log message
	 *
	 * @param string $message
	 * @return void
	 */
	protected function _log($message)
	{
		if($this->_conf['debug'])
		{
			$this->_log[] = $message;
		}
	}

	/**
	 * Configuration settings getter
	 *
	 * @param null|string $key (null for get all)
	 * @return mixed
	 */
	public function conf($key)
	{
		if(is_null($key)) // get all
		{
			return $this->_conf;
		}

		if(isset($this->_conf[$key]) || array_key_exists($key, $this->_conf)) // getter
		{
			return $this->_conf[$key];
		}
	}

	/**
	 * PDO connection getter/setter
	 *
	 * @staticvar boolean $is_init
	 * @staticvar array $connections
	 * @param int $connection
	 * @return \self (or array|boolean)
	 * @throws \Exception
	 */
	public static function &connection($connection = 1)
	{
		static $is_init = false;
		static $connections = [];

		if(is_null($connection)) // connection keys getter
		{
			$keys = array_keys($connections);
			return $keys;
		}

		if(!$is_init) // init handler
		{
			self::__init();
			$is_init = true;
		}

		if(is_array($connection)) // register
		{
			if(isset($connection['host']) && isset($connection['database'])
				&& isset($connection['user']) && isset($connection['password'])) // verify valid connection
			{
				if(isset($connection['id']) && is_int($connection['id'])) // manual connection ID
				{
					$id = $connection['id'];

					if(isset($connections[$id]))
					{
						throw new \Exception('Connection ID "' . $id . '" already exists');
						return false;
					}
				}
				else // auto ID
				{
					$id = ++self::$__connection_id;

					while(isset($connections[$id])) // enforce unique ID
					{
						$id = ++self::$__connection_id;
					}
				}

				$connections[$id] = new self($id, $connection['host'],
					$connection['database'], $connection['user'], $connection['password'], $connection);

				return $id;
			}
		}
		else // getter
		{
			if(isset($connections[$connection]))
			{
				return $connections[$connection];
			}

			throw new \Exception('Connection "' . $connection . '" does not exist');
		}
	}

	/**
	 * Last insert ID getter
	 *
	 * @return int
	 */
	public function insertId()
	{
		return $this->__pdo->lastInsertId();
	}

	/**
	 * Error has occurred flag getter
	 *
	 * @return boolean
	 */
	public function isError()
	{
		return $this->__is_error;
	}

	/**
	 * Table primary key column name getter/setter
	 *
	 * @param string $table
	 * @param string $key_column
	 * @return string
	 */
	public function key($table, $key_column = null)
	{
		if(is_null($table)) // get all
		{
			return $this->_key_map;
		}

		if(!is_null($key_column)) // setter
		{
			$this->_key_map[$table] = $key_column;
			return $key_column;
		}

		if(isset($this->_key_map[$table]))
		{
			return $this->_key_map[$table];
		}

		return self::DEFAULT_PRIMARY_KEY_COLUMN; // default
	}

	/**
	 * Last error message getter
	 *
	 * @return string
	 */
	public function lastError()
	{
		return $this->__last_error;
	}

	/**
	 * Debug log getter
	 *
	 * @return array
	 */
	public function log()
	{
		return $this->_log;
	}

	/**
	 * Execute query
	 *
	 * @param string $query
	 * @param array|null $params (prepared statement params)
	 * @return mixed (array|boolean|int|object)
	 */
	public function query($query, $params = null)
	{
		$this->_log('Query: ' . $query);
		if(is_array($params) && count($params) > 0)
		{
			$q_params = [];
			foreach($params as $k => $v)
			{
				if(is_array($v))
				{
					$this->_error('Invalid query parameter(s) type: array (only use scalar values)');
					return false;
				}

				$q_params[] = $k . ' => ' . $v;
			}

			$this->_log('(Query params: ' . implode(', ', $q_params) . ')');
		}

		try
		{
			$sh = $this->__pdo->prepare($query);
			if($sh->execute( is_array($params) ? $params : null ))
			{
				if(preg_match('/^(select|show|describe|optimize|pragma|repair)/i', $query)) // fetch
				{
					return $sh->fetchAll( $this->conf('objects') ? \PDO::FETCH_CLASS : \PDO::FETCH_ASSOC );
				}
				else if(preg_match('/^(delete|insert|update)/i', $query)) // modify
				{
					return $sh->rowCount();
				}
				else // other
				{
					return true;
				}
			}
			else
			{
				$this->_error($sh->errorInfo());
			}
		}
		catch(\PDOException $e)
		{
			$this->_error($e->getMessage());
		}

		return false;
	}

	/**
	 * Quote/escape string for safe usage
	 *
	 * @param string $string
	 * @return string
	 */
	public function quote($string)
	{
		return $this->__pdo->quote($string);
	}
}