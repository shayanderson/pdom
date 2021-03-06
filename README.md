### This project is no longer supported and has been moved to [Xap](https://github.com/shayanderson/xap)

# PDOm
#### PDO Wrapper with MySQL Helper

Here is a list of PDOm commands:

- [`add`](https://github.com/shayanderson/pdom#insert) - insert record (can also use `insert`)
- [`call`](https://github.com/shayanderson/pdom#call-stored-procedurefunction-routines) - call stored procedure or function
- [`columns`](https://github.com/shayanderson/pdom#show-table-columns) - show table columns
- [`commit`](https://github.com/shayanderson/pdom#transactions) - commit transaction
- [`count`](https://github.com/shayanderson/pdom#count-query) - count table records
- [`del`](https://github.com/shayanderson/pdom#delete) - delete record(s) (can also use `delete`)
- [`error`](https://github.com/shayanderson/pdom#error-checking) - check if error has occurred
- [`error_last`](https://github.com/shayanderson/pdom#get-last-error) - get last error, when error has occurred
- [`id`](https://github.com/shayanderson/pdom#insert-with-insert-id) - get last insert ID
- [`key`](https://github.com/shayanderson/pdom#custom-table-primary-key-column-name) - get/set table primary key column name (default 'id')
- [`log`](https://github.com/shayanderson/pdom#debug-log) - get debug log (debugging must be turned on)
- [`mod`](https://github.com/shayanderson/pdom#update) - update record(s) (can also use `update`)
- [`query`](https://github.com/shayanderson/pdom#execute-query) - execute manual query
- [`replace`](https://github.com/shayanderson/pdom#insert) - replace record
- [`rollback`](https://github.com/shayanderson/pdom#transactions) - rollback transaction
- [`tables`](https://github.com/shayanderson/pdom#show-tables) - show database tables
- [`transaction`](https://github.com/shayanderson/pdom#transactions) - start transaction

PDOm also supports:

- [Custom primary key name](https://github.com/shayanderson/pdom#custom-table-primary-key-column-name)
- [Custom log handler](https://github.com/shayanderson/pdom#custom-log-handler)
- [Custom error handler](https://github.com/shayanderson/pdom#custom-error-handler)
- [Query options](https://github.com/shayanderson/pdom#query-options)
- [Multiple database connections](https://github.com/shayanderson/pdom#multiple-database-connections)
- [Pagination](https://github.com/shayanderson/pdom#pagination)
- [Record Class](https://github.com/shayanderson/pdom#record-class)

## Quick Start
Edit the `pdom.bootstrap.php` file and add your database connection params:
```php
// register database connection
pdom([
	// database connection params
	'host' => 'localhost',
	'database' => 'test',
	'user' => 'myuser',
	'password' => 'mypass',
	'errors' => true, // true: throw Exceptions, false: no Exceptions, use error methods
	'debug' => true // turn logging on/off
```

Next, include the bootstrap file in your project file:
```php
require_once './pdom.bootstrap.php';
```

Now execute SELECT query:
```php
try
{
	$user = pdom('users.14'); // same as "SELECT * FROM users WHERE id = '14'"
	if($user) echo $user->fullname; // print record field value
}
catch(\Exception $ex)
{
	// warn here
}
```

## Commands

#### Select
Simple select queries examples:
```php
$r = pdom('users'); // SELECT * FROM users
$r = pdom('users(fullname, email)'); // SELECT fullname, email FROM users
$r = pdom('users LIMIT 1'); // SELECT * FROM users LIMIT 1
```

#### Select Where
Select query with named parameters:
```php
// SELECT fullname, email FROM users WHERE is_active = '1' AND fullname = 'Shay Anderson'
$r = pdom('users(fullname, email) WHERE is_active = :active AND fullname = :name LIMIT 2', 
	['active' => 1, 'name' => 'Shay Anderson']);
```
Select query with question mark parameters:
```php
// SELECT fullname, email FROM users WHERE is_active = 1 AND fullname = 'Shay Anderson' LIMIT 2
$r = pdom('users(fullname, email) WHERE is_active = ? AND fullname = ? LIMIT 2', 
	[1, 'Shay Anderson']);
```

#### Select with Key
Select queries with primary key value:
```php
$r = pdom('users.2'); // SELECT * FROM users WHERE id = '2'
// test if record exists + display value for column 'fullname'
if($r) echo $r->fullname;

// using plain SQL in query example
// SELECT fullname, is_active FROM users WHERE id = '2' AND fullname = 'Shay'
$r = pdom('users(fullname, is_active).2 WHERE fullname = ? LIMIT 1', ['Name']);
```
When selecting with key use integer values only, for example:
```php
$r = pdom('users.' . (int)$id);
```
>The default primary key column name is `id`, for using different primary key column name see [custom table primary key column name](https://github.com/shayanderson/pdom#custom-table-primary-key-column-name)

#### Select Distinct
Select distinct example query:
```php
$r = pdom('users(fullname)/distinct'); // SELECT DISTINCT fullname FROM users
```

#### Insert
Simple insert example:
```php
// INSERT INTO users (fullname, is_active, created) VALUES('Name Here', '1', NOW())
$affected_rows = pdom('users:add', ['fullname' => 'Name Here', 'is_active' => 1,
	'created' => ['NOW()']]);

// can also use action ':insert'
// pdom('users:insert', ...);
```
> The `replace` command can also be used, for example:
```php
// REPLACE INTO users (id, fullname, is_active, created) VALUES(5, 'Name Here', '1', NOW())
$affected_rows = pdom('users:replace', ['id' => 5 'fullname' => 'Name Here',
	'is_active' => 1, 'created' => ['NOW()']]);
```

#### Insert with Insert ID
Insert query and get insert ID:
```php
// INSERT INTO users (fullname, is_active, created) VALUES('Name Here', '1', NOW())
pdom('users:add', ['fullname' => 'Name Here', 'is_active' => 1, 'created' => ['NOW()']]);

// get insert ID
$insert_id = pdom(':id');
```

#### Insert Ignore
Insert ignore query example:
```php
// INSERT IGNORE INTO users (user_id, fullname) VALUES('3', 'Name Here')
pdom('users:add/ignore', ['user_id' => 3, 'fullname' => 'Name Here']);
```

#### Inserting Objects
Insert into table using object instead of array:
```php
// note: all class public properties must be table column names
class User
{
	public $user_id = 70;
	public $fullname = 'Name';
	public $created = ['NOW()'];
}

$affected_rows = pdom('users:add', new User);
```

#### Update
Simple update query example:
```php
// UPDATE users SET fullname = 'Shay Anderson' WHERE user_id = '2'
$affected_rows = pdom('users:mod WHERE user_id = :user_id', ['fullname' => 'Shay Anderson'],
	['user_id' => 2]);

// can also use action ':update'
// pdom('users:update', ...);
```

#### Update Ignore
Update ignore query example:
```php
// UPDATE IGNORE users SET user_id = '3' WHERE user_id = 6
$affected_rows = pdom('users:mod/ignore WHERE user_id = 6', ['user_id' => 3]);
```

#### Delete
Delete query examples:
```php
// delete all
$affected_rows = pdom('users:del'); // DELETE FROM users

// can also use action ':delete'
// pdom('users:delete', ...);

// DELETE FROM users WHERE is_active = 1
$affected_rows = pdom('users:del WHERE is_active = 1');

// DELETE FROM users WHERE user_id = '29'
$affected_rows = pdom('users:del WHERE user_id = ?', [29]);
```

#### Delete Ignore
Delete ignore query example:
```php
// DELETE IGNORE FROM users WHERE user_id = 60
$affected_rows = pdom('users:del/ignore WHERE user_id = 60');
```

#### Execute Query
Execute manual query example:
```php
// execute any query using the 'query' command
$r = pdom(':query SELECT * FROM users LIMIT 2');

// use params with manual query:
$r = pdom(':query SELECT * FROM users WHERE user_id = ?', [2]);
```

#### Count Query
Get back a count (integer) query example:
```php
// returns int of all records
$count = pdom('users:count'); // SELECT COUNT(1) FROM users

// SELECT COUNT(1) FROM users WHERE is_active = 1
$count = pdom('users:count WHERE is_active = 1');

// SELECT COUNT(1) FROM users WHERE user_id > '2' AND is_active = '1'
$count = pdom('users:count WHERE user_id > ? AND is_active = ?', [2, 1]);
```

#### Call Stored Procedure/Function (Routines)
Call SP/SF example:
```php
pdom(':call sp_name'); // CALL sp_name()

// Call SP/SF with params:
// CALL sp_addUser('Name Here', '1', NOW())
pdom(':call sp_addUser', 'Name Here', 1, ['NOW()']);

// Call SP/SF with params and out param
pdom(':query SET @out = "";'); // set out param
// CALL sp_addUser('Name Here', '1', NOW(), @out)
pdom(':call sp_addUserGetId', 'Name Here', 1, ['NOW()'], ['@out']);
// get out param value
$r = pdom(':query SELECT @out;');
```

#### Transactions
Transactions are easy, for example:
```php
pdom(':transaction'); // start transaction (autocommit off)
pdom('users:add', ['fullname' => 'Name 1']);
pdom('users:add', ['fullname' => 'Name 2']);

if(!pdom(':error')) // no error
{
	if(pdom(':commit')) ... // no problem, commit + continue with logic
}
else // error
{
	pdom(':rollback'); // problem(s), rollback
	// warn client
}
```
> When [errors are on](https://github.com/shayanderson/pdom#quick-start), use *try/catch* block like:
```php
try
{
	pdom(':transaction'); // start transaction (autocommit off)
	pdom('users:add', ['fullname' => 'Name 1']);
	pdom('users:add', ['fullname' => 'Name 2']);
	if(pdom(':commit')) ... // no problem, commit + continue with logic
}
catch(\Exception $ex)
{
	pdom(':rollback'); // problem(s), rollback
	// warn client
}
```

#### Show Tables
Show database tables query example:
```php
$tables = pdom(':tables'); // returns array of tables
```

#### Show Table Columns
Show table columns query example:
```php
$columns = pdom('users:columns'); // returns array of table column names
```

#### Debug Log
Get debug log array example:
```php
$log = pdom(':log'); // returns array of debug log messages
```
> Debug mode must be enabled for this example

#### Error Checking
Check if error has occurred example:
```php
if(pdom(':error'))
{
	// do something
}
```
> For error checking errors must be disabled, otherwise exception is thrown

#### Get Last Error
Get last error string example:
```php
if(pdom(':error'))
{
	echo pdom(':error_last');
}
```
> For getting last error message errors must be disabled, otherwise exception is thrown

#### Debugging
To display all registered connections, mapped keys, debug log and errors use:
```php
print_r( pdom(null) ); // returns array with debug info
```

## Advanced
#### Custom Table Primary Key Column Name
By default the primary key column named used when selecting with key is 'id'.
 This can be changed using the 'key' or 'keys' command:
```php
// register 'user_id' as primary key column name for table 'users'
pdom('users:key user_id');

// now 'WHERE id = 2' becomes 'WHERE user_id = 2'
$r = pdom('users.2'); // SELECT * FROM users WHERE user_id = '2'

// also register multiple key column names:
pdom(':key', [
	'users' => 'user_id',
	'orders' => 'order_id'
]);
```

#### Custom Log Handler
A custom log handler can be used when setting a database connection, for example:
```php
// register database connection
pdom([
	// database connection params
	'host' => 'localhost',
	...
	'debug' => true, // debugging must be enabled for log handler
	// register custom log handler (must be callable)
	'log_handler' => function($msg) { echo '<b>Message:</b> ' . $msg . '<br />'; }
```
Now all PDOm log messages will be sent to the custom callable log handler.

#### Custom Error Handler
A custom error handler can be used when setting a database connection, for example:
```php
// register database connection
pdom([
	// database connection params
	'host' => 'localhost',
	...
	'errors' => true, // errors must be enabled for error handler
	// register custom error handler (must be callable)
	'error_handler' => function($err) { echo '<b>Error:</b> ' . $err . '<br />'; }
```
Now all PDOm error messages will be sent to the custom callable error handler.

#### Query Options
Query options are used like: `table:command/[option]` and can be used with `SELECT` commands and these commands:
`add/insert`, `call`, `del/delete`, `mod/update`

Example of option use:
```php
$r = pdom('users(fullname)/distinct'); // DISTINCT option
```

Options can be chained together to complete valid MySQL statements:
```php
// UPDATE LOW_PRIORITY IGNORE users SET fullname = 'Shay Anderson' WHERE user_id = '2'
$affected_rows = pdom('users:mod/low_priority/ignore WHERE user_id = :user_id',
	['fullname' => 'Shay Anderson'], ['user_id' => 2]);
```

##### Query Option
The `query` option can be used to return the query string only, without executing the query (for debugging), for example:
```php
$r = pdom('users(fullname)/distinct/query'); // returns string 'SELECT DISTINCT fullname FROM users'
```

##### First Option
The `first` option can be used to return the first record only, for example:
```php
$user = pdom('users/first WHERE is_active = 1');
if($user) echo $user->fullname;
```
This can simplify using the first record only instead of having to use:
```php
if(isset($user[0])) echo $user[0]->fullname;
```

#### Multiple Database Connections
Using multiple database connections is easy, register database connections in bootstrap:
```php
// connection 1 (default connection)
pdom(['host' => 'host1.server.com',
	// more here
]);

// connection 2
pdom(['host' => 'host2.server.com',
	// more here
]);

// or manually set connection ID
pdom(['host' => 'host5.server.com',
	// more here
	'id' => 5 // manually set ID (int only)
]);
```
> **Note:** manually set ID must be integer

Now to use different connections:
```php
// select from connection 1 / default connection
$r = pdom('users.2'); // SELECT * FROM users WHERE id = '2'

// select from connection 2, where '[n]' is connection ID
$r2 = pdom('[2]users.2'); // SELECT * FROM users WHERE id = '2'
```

#### Pagination
Pagination is easy to use for large select queries, here is an example:
```php
// set current page number, for this example use GET parameter 'pg'
$pg = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;

// next set 10 Records Per Page (rpp) and current page number
pdom(':pagination', ['rpp' => 10, 'page' => $pg]);

// execute SELECT query with pagination (SELECT query cannot contain LIMIT clause)
// SELECT DISTINCT id, fullname FROM users WHERE LENGTH(fullname) > '0' LIMIT x, y
$r = pdom('users(id, fullname)/distinct/pagination WHERE LENGTH(fullname) > ?', [0]);
// $r['pagination'] contains pagination values: rpp, page, next, prev, offset
// $r['rows'] contains selected rows
```

#### Record Class
The \Pdom\Record class can be used to simplify record actions, here are examples:
```php
// make sure to include \Pdom\Record class file in bootstrap

// create User class to extend \Pdom\Record class (normally this would be in separate file)
class User extends \Pdom\Record
{
	const KEY = 'user_id'; // define the primary key column name
	const TABLE = 'users'; // define the table name

	// set table columns (minus primary key column)
	// the '@column' annotation is used to set class property as column
	/** @column */
	public $fullname;
	/** @column */
	public $is_active;
	/** @column */
	public $ts_created;
}

// set User object
$user = new User;

// select example:
// load data for user with ID '10'
$user->user_id = 10;
$user->select();
// or if User class does not override __construct() method you can simply do:
// $user = new User(10); // autoloads user data with ID '10'

// do something with loaded data:
echo $user->fullname;

// insert example:
$user = new User;
$user->fullname = 'Shay Anderson';
$user->is_active = 1;
$user->ts_created = ['NOW()']; // array tells pdom to use plain SQL
if($user->add()) // do insert
{
	echo 'User added';
}

// update example:
$user = new User(10); // load user data
$user->fullname = 'New Name'; // update fullname
if($user->save()) // do update
{
	echo 'User updated';
}

// delete example:
$user = new User;
$user->user_id = 10; // do not pre-load data
if($user->delete()) // do delete
{
	echo 'User deleted';
}

// other useful \Pdom\Record methods are:
$column_names = $user->getColumnNames(); // get array of column names
$columns_and_values = &$user->getColumns(); // get array of column names (array keys) and values
$is_column = $user->isColumn('fullname'); // check if column exists
$is_record = $user->isRecord(); // check if record already exists using primary key column value
$user->setColumns(['fullname' => 'Some value', 'is_active' => 0]); // populate column values
```