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
 * @version 1.1.b - May 06, 2014
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <https://github.com/shayanderson/pdom>
 */
use Pdom\Pdo;

/**
 * PDOm wrapper function
 *
 * @author Shay Anderson 03.14 <http://www.shayanderson.com/contact>
 *
 * @staticvar array $pagination
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
function pdom($cmd, $_ = null)
{
	if(is_array($cmd)) // connection/config
	{
		return Pdo::connection($cmd); // register connection, return connection ID
	}
	else if(is_null($cmd)) // debugger, connection/config getter
	{
		$debug = [];

		foreach(Pdo::connection(null) as $k)
		{
			$debug[$k] = [
				'conf' => Pdo::connection($k)->conf(null),
				'keys' => Pdo::connection($k)->key(null),
				'log' => Pdo::connection($k)->log()
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
		static $pagination = ['rpp' => 10, 'page' => 1];
		$option = null;
		$is_return_qs = $is_pagination = false;

		if(preg_match('/^\[(\d+)\].*/', $cmd, $m)) // match '[id]table', connection ID?
		{
			$id = $m[1];
			$cmd = preg_replace('/^\[(\d+)\]/', '', $cmd);
		}

		if(preg_match_all('/\/([a-zA-Z_]+)/', $cmd, $m)) // match '...xyz/option'
		{
			foreach($m[1] as $opt)
			{
				$opt = strtoupper($opt);
				if($opt === 'QUERY') // return query only
				{
					$is_return_qs = true;
				}
				else if($opt === 'PAGINATION')
				{
					$is_pagination = true;
				}
				else
				{
					$option .= ' ' . $opt;
				}
			}
			$cmd = preg_replace('/\/[a-zA-Z_]+/', '', $cmd);
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

			$q = 'SELECT' . $option . ' ' . $columns . ' FROM ' . $cmd . $sql;

			if($is_pagination)
			{
				// match 'LIMIT x, y (OFFSET z)?', only allow if no LIMIT
				if(!preg_match('/LIMIT[\s\d,]+(OFFSET[\s\d]+)?$/i', $q))
				{
					$p = ['rpp' => $pagination['rpp'], 'page' => $pagination['page'],
						'next' => 0, 'prev' => 0, 'offset' => 0];
					$p['offset'] = ($p['page'] - 1) * $p['rpp'];
					$q .= ' LIMIT ' . $p['offset'] . ', ' . ($p['rpp'] + 1);
				}
				else // LIMIT already exists
				{
					throw new \Exception('Failed to apply pagination to query'
						. ', LIMIT clause already exists in query');
				}
			}

			if($is_return_qs)
			{
				return $q;
			}
			else
			{
				if(!$is_pagination)
				{
					return Pdo::connection($id)->query($q, $params);
				}
				else
				{
					$r = Pdo::connection($id)->query($q, $params);

					if(count($r) > $p['rpp'])
					{
						array_pop($r); // pop last row (more rows)
						$p['next'] = $p['page'] + 1;
					}

					if($p['page'] > 1)
					{
						$p['prev'] = $p['page'] - 1;
					}

					return ['pagination' => Pdo::connection($id)->conf('objects')
						? (object)$p : $p, 'rows' => &$r];
				}
			}
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
							if(isset($v[0]) && strlen($v[0]) > 0)
							{
								$values[] = $v[0];
							}
						}
						else // named param
						{
							$params[$k] = $v;
							$values[] = ':' . $k;
						}
					}

					$q = 'INSERT' . $option . ' INTO ' . $table . '('
						. implode(', ', array_keys($args[0])) . ') VALUES('
						. implode(', ', $values) . ')';

					if($is_return_qs)
					{
						return $q;
					}
					else
					{
						return Pdo::connection($id)->query($q, $params);
					}
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
						else if(isset($args[$i]) && strlen($args[$i]) > 0) // plain SQL
						{
							$params_str .= $sep . implode('', $args[$i]);
						}
					}

					$q = 'CALL ' . ( isset($args[0]) ? $args[0] : '' ) . '(' . $params_str . ')';

					if($is_return_qs)
					{
						return $q;
					}
					else
					{
						return Pdo::connection($id)->query($q, $params);
					}
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
						$r[0] = (array)$r[0];
						if(isset($r[0]['count']))
						{
							return (int)$r[0]['count'];
						}
					}

					return 0;
					break;

				case 'del': // delete
				case 'delete':
					$q = 'DELETE' . $option . ' FROM ' . $table
						. ( isset($args[0]) ? ' ' . $args[0] : '' );

					if($is_return_qs)
					{
						return $q;
					}
					else
					{
						return Pdo::connection($id)->query($q, isset($args[1]) ? $args[1] : null);
					}
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
					if(is_array($args[0]) || is_object($args[0]))
					{
						foreach($args[0] as $k => $v)
						{
							if(is_array($v)) // plain SQL
							{
								if(isset($v[0]) && strlen($v[0]) > 0)
								{
									$values[] = $k . ' = ' . $v[0];
								}
							}
							else // named param
							{
								$params[$k] = $v;
								$values[] = $k . ' = :' . $k;
							}
						}
					}
					else
					{
						throw new \Exception('Update failed: using scalar value for'
							. ' setting columns and values (use array or object)');
					}

					if(isset($args[2]) && is_array($args[2])) // statement params
					{
						$params = array_merge($params, $args[2]);
					}

					$q = 'UPDATE' . $option . ' ' . $table . ' SET ' . implode(', ', $values)
						. ( isset($args[1]) ? ' ' . $args[1] : '' );

					if($is_return_qs)
					{
						return $q;
					}
					else
					{
						return Pdo::connection($id)->query($q, $params);
					}
					break;

				case 'optimize': // optimize table
					return Pdo::connection($id)->query('OPTIMIZE TABLE ' . $table);
					break;

				case 'pagination': // pagination params getter/setter
					if(isset($args[0]) && is_array($args[0])) // setter
					{
						foreach($args[0] as $k => $v)
						{
							if(isset($pagination[$k]))
							{
								$v = (int)$v;
								if($v > 0)
								{
									$pagination[$k] = $v;
								}
							}
						}
					}

					return $pagination;
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