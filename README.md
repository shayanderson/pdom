# PDOm
PDOm - PDO Wrapper with MySQL Helper

Documentation topics:
[Quick Start](https://github.com/shayanderson/pdom#quick-start)
[Select](https://github.com/shayanderson/pdom#select), [Select Where](https://github.com/shayanderson/pdom#select-where), [Select with Key](https://github.com/shayanderson/pdom#select-with-key), [Select Distinct](https://github.com/shayanderson/pdom#select-distinct)
[Insert](https://github.com/shayanderson/pdom#insert), [Insert with Insert ID](https://github.com/shayanderson/pdom#insert-with-insert-id), [Insert Ignore](https://github.com/shayanderson/pdom#insert-ignore), [Inserting Objects](https://github.com/shayanderson/pdom#inserting-objects)

## Quick Start
Edit the *pdom.bootstrap.php* file and add your database connection params:
```php
// register database connection
pdom([
	// database connection params
	'host' => 'localhost',
	'database' => 'test',
	'user' => 'myuser',
	'password' => 'mypass',
```

Next, include the bootstrap file in your project file:
```php
require_once './pdom.bootstrap.php';
```

Now execute SELECT query:
```php
$user = pdom('users.14'); // same as "SELECT * FROM users WHERE id = '14'"
echo $user[0]->fullname; // print record field value
```

## PDOm Commands
Here is a list of simple PDOm commands:

### Select
Simple select queries examples:
```php
$r = pdom('users'); // SELECT * FROM users
$r = pdom('users(fullname, email)'); // SELECT fullname, email FROM users
$r = pdom('users', 'LIMIT 1'); // SELECT * FROM users LIMIT 1
```

### Select Where
Select query with named parameters:
```php
// SELECT fullname, email FROM users WHERE is_active = '1' AND fullname = 'Shay Anderson'
$r = pdom('users(fullname, email)', 'WHERE is_active = :active AND fullname = :name LIMIT 2', ['active' => 1, 'name' => 'Shay Anderson']);
```
Select query with question mark parameters:
```php
// SELECT fullname, email FROM users WHERE is_active = 1 AND fullname = 'Shay Anderson' LIMIT 2
$r = pdom('users(fullname, email)', 'WHERE is_active = ? AND fullname = ? LIMIT 2', [1, 'Shay Anderson']);
```

### Select with Key
Select queries with primary key value:
```php
$r = pdom('users.2'); // SELECT * FROM users WHERE id = '2'

// using plain SQL in query example
// SELECT fullname, is_active FROM users WHERE id = '2' AND created > NOW()
$r = pdom('users(fullname, is_active).2', 'AND created > ? LIMIT 1', ['NOW()']);
```

### Select Distinct
Select distinct example query:
```php
$r = pdom('users(fullname)/distinct'); // SELECT DISTINCT fullname FROM users
```

### Insert
Simple insert example:
```php
// INSERT INTO users (fullname, is_active, created) VALUES('Name Here', '1', NOW())
$affected_rows = pdom('users:add', ['fullname' => 'Name Here', 'is_active' => 1, 'created' => ['NOW()']]);

// can also use action ':insert'
// pdom('users:insert', ...);
```

### Insert with Insert ID
Insert query and get insert ID:
```php
// INSERT INTO users (fullname, is_active, created) VALUES('Name Here', '1', NOW())
pdom('users:add', ['fullname' => 'Name Here', 'is_active' => 1, 'created' => ['NOW()']]);

// get insert ID
$insert_id = pdom(':id');
```

### Insert Ignore
Insert ignore query example:
```php
// INSERT IGNORE INTO users (user_id, fullname) VALUES('3', 'Name Here')
pdom('users:add/ignore', ['user_id' => 3, 'fullname' => 'Name Here']);
```

### Inserting Objects
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

### Update
Simple update query example:
```php
// UPDATE users SET fullname = 'Shay Anderson' WHERE user_id = '2'
$affected_rows = pdom('users:mod', ['fullname' => 'Shay Anderson'], 'WHERE user_id = :user_id', ['user_id' => 2]);

// can also use action ':update'
// pdom('users:update', ...);
```

### Update Ignore
Update ignore query example:
```php
// UPDATE IGNORE users SET user_id = '3' WHERE user_id = 6
$affected_rows = pdom('users:mod/ignore', ['user_id' => 3], 'WHERE user_id = 6');
```

### Delete
Delete query examples:
```php
// delete all
$affected_rows = pdom('users:del'); // DELETE FROM users

// can also use action ':delete'
// pdom('users:delete', ...);

// DELETE FROM users WHERE is_active = 1
$affected_rows = pdom('users:del', 'WHERE is_active = 1');

// DELETE FROM users WHERE user_id = '29'
$affected_rows = pdom('users:del', 'WHERE user_id = ?', [29]);
```

### Delete Ignore
Delete ignore query example:
```php
// DELETE IGNORE FROM users WHERE user_id = 60
$affected_rows = pdom('users:del/ignore', 'WHERE user_id = 60');
```

### Execute Query
Execute manual query example:
```php
// execute any query using the 'query' command
$r = pdom(':query', 'SELECT * FROM users LIMIT 2');

// use params with manual query:
$r = pdom(':query', 'SELECT * FROM users WHERE user_id = ?', [2]);
```

### Count Query
Get back a count (integer) query example:
```php
// returns int of all records
$count = pdom('users:count'); // SELECT COUNT(1) FROM users

// SELECT COUNT(1) FROM users WHERE is_active = 1
$count = pdom('users:count', 'WHERE is_active = 1');

// SELECT COUNT(1) FROM users WHERE user_id > '2' AND is_active = '1'
$count = pdom('users:count', 'WHERE user_id > ? AND is_active = ?', [2, 1]);
```

### Call Stored Procedure/Function (Routines)
Call SP/SF example:
```php
pdom('users:call', 'sp_name'); // CALL sp_name()

// Call SP/SF with params:
// CALL sp_addUser('Name Here', '1', NOW())
pdom('users:call', 'sp_addUser', 'Name Here', 1, ['NOW()']);

// Call SP/SF with params and out param
pdom(':query', 'SET @out = "";'); // set out param
// CALL sp_addUser('Name Here', '1', NOW(), @out)
pdom('users:call', 'sp_addUserGetId', 'Name Here', 1, ['NOW()'], ['@out']);
// get out param value
$r = pdom(':query', 'SELECT @out;');
```

### Custom Table Primary Key Column Name
By default the primary key column named used when selecting with key is 'id'.
 This can be changed using the 'key' or 'keys' command:
```php
// register 'user_id' as primary key column name for table 'users'
pdom('users:key', 'user_id');

// now 'WHERE id = 2' becomes 'WHERE user_id = 2'
$r = pdom('users.2'); // SELECT * FROM users WHERE user_id = '2'

// also can use 'keys' command to register multiple key column names:
pdom(':keys', [
	'users' => 'user_id',
	'orders' => 'order_id'
]);
```

### Show Tables
Show database tables query example:
```php
$tables = pdom(':tables'); // returns array of tables
```

### Show Table Columns
Show table columns query example:
```php
$columns = pdom('users:columns'); // returns array of table column names
```

### Truncate Table
Trunacte table query example:
```php
pdom('users:truncate'); // truncate table 'users'
```

### Optimize Table
Optimize table query example:
```php
pdom('users:optimize'); // optimize table 'users'
```

### Repair Table
Repair table query example:
```php
pdom('users:repair'); // repair table 'users'
```

### Debug Log
Get debug log array example:
```php
$log = pdom(':log'); // returns array of debug log messages
```

### Error Checking
Check if error has occurred example:
```php
if(pdom(':error'))
{
	// do something
}
```

### Get Last Error
Get last error string example:
```php
if(pdom(':error'))
{
	echo pdom(':error_last');
}
```

### Debugging
To display all registered connections, mapped keys, debug log and errors use:
```php
print_r( pdom(null) ); // returns array with debug info
```

### Query Options
Query options are used like: *table:command/[option]* and can be used with SELECT commands and these commands:
*add/insert*, *call*, *del/delete*, *mod/update*

Example of option use:
```php
$r = pdom('users(fullname)/distinct'); // DISTINCT option
```

The *query* option can be used to return the query string only, without executing the query (for debugging), for example:
```php
$r = pdom('users(fullname)/distinct/query'); // returns string 'SELECT DISTINCT fullname FROM users'
```

Options can be chained together to complete valid MySQL statements:
```php
// UPDATE LOW_PRIORITY IGNORE users SET fullname = 'Shay Anderson' WHERE user_id = '2'
$affected_rows = pdom('users:mod/low_priority/ignore', ['fullname' => 'Shay Anderson'], 'WHERE user_id = :user_id', ['user_id' => 2]);
```

### Multiple Database Connections
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
	'id' => 5 // manually set ID
]);
```
Now to use different connections:
```php
// select from connection 1 / default connection
$r = pdom('users.2'); // SELECT * FROM users WHERE id = '2'

// select from connection 2, where '[n]' is connection ID
$r2 = pdom('[2]users.2'); // SELECT * FROM users WHERE id = '2'
```

### Pagination
Pagination is easy to use for large select queries, here is an example:
```php
// set current page number, for this example use GET parameter 'pg'
$pg = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;

// next set 10 Records Per Page (rpp) and current page number
pdom(':pagination', ['rpp' => 10, 'page' => $pg]);

// execute SELECT query with pagination (SELECT query cannot contain LIMIT clause)
// SELECT DISTINCT id, fullname FROM users WHERE LENGTH(fullname) > '0' LIMIT x, y
$r = pdom('users(id, fullname)/distinct/pagination', 'WHERE LENGTH(fullname) > ?', [0]);
// $r['pagination'] contains pagination values: rpp, page, next, prev, offset
// $r['rows'] contains selected rows
```
