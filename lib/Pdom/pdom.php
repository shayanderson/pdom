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
 * @version 1.0.b - Mar 12, 2014
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <http://www.shayanderson.com/projects/pdom.htm>
 */
use Pdom\Pdo;

/**
 * PDOm wrapper function
 *
 * @author Shay Anderson 03.14 <http://www.shayanderson.com/contact>
 *
 * @param string $cmd
 * @param mixed $_ (optional values)
 * @return mixed
 * @throws Exception
 *
 * Commands:
 *		add			(also insert, insert new record)
 *		call		(call stored procedure or function)
 *		columns		(show table columns)
 *		count		(count table records)
 *		del			(also delete, delete record(s))
 *		error		(check if error has occurred)
 *		error_last	(get last error, when error has occurred)
 *		id			(get last insert ID)
 *		key			(get/set table primary key column name, default 'id')
 *		keys		(get/set multiple primary key column names)
 *		log			(get debug log, debugging must be turned on)
 *		mod			(also update, update record(s))
 *		optimize	(optimize table)
 *		query		(execute manual query)
 *		repair		(repair table)
 *		tables		(show database tables)
 *		truncate	(truncate table)
 */
function pdom($cmd = 1, $_ = null)
{
	if(is_array($cmd)) // connection/config
	{
		return Pdo::connection($cmd); // register connection, return connection ID
	}
	else if(is_null($cmd)) // debugger, connection/config getter
	{
		$debug = [];

		for($i = 1; $i <= Pdo::connectionId(); $i++)
		{
			$debug[$i] = [
				'conf' => Pdo::connection($i)->conf(null),
				'keys' => Pdo::connection($i)->key(null),
				'log' => Pdo::connection($i)->log()
			];
		}

		return $debug;
	}
	else // parse command
	{
		$args = func_get_args();
		array_shift($args); // rm table

		$id = 1; // default ID
		$params = [];
		$option = null;

		if(preg_match('/^\[(\d+)\].*/', $cmd, $m)) // match '[id]table', connection ID?
		{
			$id = $m[1];
			$cmd = preg_replace('/^\[(\d+)\]/', '', $cmd);
		}

		if(preg_match('/\/([a-z]+)$/', $cmd, $m)) // match '...xyz/option'
		{
			$option = $m[1];
			$cmd = preg_replace('/\/[a-z]+$/', '', $cmd);
		}

		if(strpos($cmd, ':') === false) // SELECT command
		{
			preg_match('/^[\w]+\(([\w\,\s]+)\)/', $cmd, $m); // match 'table(f1, f2)', SELECT columns
			$columns = '*';
			if(isset($m[1]))
			{
				$columns = trim($m[1]);
				$cmd = preg_replace('/\(([\w\,\s]+)\)/', '', $cmd); // match '(f1, f2)', rm columns
			}
			$sql = '';

			if(preg_match('/^[\w]+\.([\w]+)$/', $cmd, $m)) // match 'table.[id]'
			{
				$cmd = substr($cmd, 0, strpos($cmd, '.')); // rm ID

				$sql = ' WHERE ' . Pdo::connection($id)->key($cmd) . ' = '
					. Pdo::connection($id)->quote($m[1]);
			}

			if(isset($args[0]) && is_scalar($args[0])) // SQL statement(s)
			{
				$sql .= ' ' . $args[0];
			}

			if(isset($args[1]) && is_array($args[1])) // SQL statement param(s)
			{
				$params = array_merge($params, $args[1]);
			}

			return Pdo::connection($id)->query('SELECT '
				. ( $option === 'distinct' ? 'DISTINCT ' : '' ) // option
				. $columns . ' FROM ' . $cmd . $sql, $params);
		}
		else // parse command
		{
			$table = substr($cmd, 0, strpos($cmd, ':'));
			$cmd = trim(substr($cmd, strpos($cmd, ':') + 1, strlen($cmd)));

			switch($cmd)
			{
				case 'add': // insert
				case 'insert':
					if(is_object($args[0])) // object add
					{
						$obj_arr = [];
						foreach(get_object_vars($args[0]) as $k => $v)
						{
							$obj_arr[$k] = $v;
						}

						$args[0] = &$obj_arr;
					}

					$values = [];
					foreach($args[0] as $k => $v)
					{
						if(is_array($v)) // plain SQL
						{
							$values[] = $v[0];
						}
						else // named param
						{
							$params[$k] = $v;
							$values[] = ':' . $k;
						}
					}

					return Pdo::connection($id)->query('INSERT '
						. ( $option === 'ignore' ? 'IGNORE ' : '' )
						. 'INTO ' . $table . '('
						. implode(', ', array_keys($args[0])) . ') VALUES('
						. implode(', ', $values) . ')', $params);
					break;

				case 'call': // call SP/SF
					$params_str = '';

					for($i = 1; $i <= count($args) - 1; $i++)
					{
						$sep = empty($params_str) ? '' : ', ';
						if(!is_array($args[$i])) // param
						{
							$params_str .= $sep . '?';
							$params[] = $args[$i];
						}
						else // plain SQL
						{
							$params_str .= $sep . implode('', $args[$i]);
						}
					}

					return Pdo::connection($id)->query('CALL '
						. ( isset($args[0]) ? $args[0] : '' ) . '(' . $params_str . ')', $params);
					break;

				case 'columns': // show columns
					$c = [];
					$r = Pdo::connection($id)->query('SHOW COLUMNS FROM ' . $table);
					if(isset($r) && is_array($r))
					{
						foreach($r as $v)
						{
							$v = array_values((array)$v);
							$c[] = $v[0];
						}
					}
					return $c;
					break;

				case 'count': // count records
					$r = Pdo::connection($id)->query('SELECT COUNT(1) AS count FROM ' . $table
						. ( isset($args[0]) ? ' ' . $args[0] : '' ),
						isset($args[1]) ? $args[1] : null);

					if(isset($r[0]))
					{
						if(is_array($r[0]) && isset($r[0]['count']))
						{
							return (int)$r[0]['count'];
						}
						else if(is_object($r[0]) && isset($r[0]->count))
						{
							return (int)$r[0]->count;
						}
					}

					return 0;
					break;

				case 'del': // delete
				case 'delete':
					return Pdo::connection($id)->query('DELETE '
						. ( $option === 'ignore' ? 'IGNORE ' : '' )
						. 'FROM ' . $table
						. ( isset($args[0]) ? ' ' . $args[0] : '' ),
						isset($args[1]) ? $args[1] : null);
					break;

				case 'error': // error check
					return Pdo::connection($id)->isError();
					break;

				case 'error_last': // get last error
					return Pdo::connection($id)->lastError();
					break;

				case 'id': // get last insert ID
					return Pdo::connection($id)->insertId();
					break;

				case 'key': // set/get table primary key column name
					if(isset($args[0])) // setter
					{
						return Pdo::connection($id)->key($table, $args[0]);
					}

					return Pdo::connection($id)->key($table);
					break;

				case 'keys': // set/get multiple primary key column names
					if(isset($args[0]) && is_array($args[0])) // setter
					{
						foreach($args[0] as $k => $v)
						{
							Pdo::connection($id)->key($k, $v);
						}
					}

					return Pdo::connection($id)->key(null);
					break;

				case 'log': // log getter
					return Pdo::connection($id)->log();
					break;

				case 'mod': // update
				case 'update':
					$values = [];
					foreach($args[0] as $k => $v)
					{
						if(is_array($v)) // plain SQL
						{
							$values[] = $k . ' = ' . $v[0];
						}
						else // named param
						{
							$params[$k] = $v;
							$values[] = $k . ' = :' . $k;
						}
					}

					if(isset($args[2]) && is_array($args[2])) // statement params
					{
						$params = array_merge($params, $args[2]);
					}

					return Pdo::connection($id)->query('UPDATE '
						. ( $option === 'ignore' ? 'IGNORE ' : '' )
						. $table . ' SET ' . implode(', ', $values)
						. ( isset($args[1]) ? ' ' . $args[1] : '' ), $params);
					break;

				case 'optimize': // optimize table
					return Pdo::connection($id)->query('OPTIMIZE TABLE ' . $table);
					break;

				case 'query': // manual query
					return Pdo::connection($id)->query(isset($args[0]) ? $args[0] : '',
						isset($args[1]) ? $args[1] : null);
					break;

				case 'repair': // repair table
					return Pdo::connection($id)->query('REPAIR TABLE ' . $table);
					break;

				case 'tables': // show tables
					$t = [];
					$r = Pdo::connection($id)->query('SHOW TABLES');
					if(isset($r) && is_array($r))
					{
						foreach($r as $v)
						{
							$v = array_values((array)$v);
							$t[] = $v[0];
						}
					}
					return $t;
					break;

				case 'truncate':
					return Pdo::connection($id)->query('TRUNCATE ' . $table);
					break;

				default: // unknown command
					throw new Exception('Invalid command "' . $cmd . '" for table "' . $table . '"');
					break;
			}
		}
	}
}