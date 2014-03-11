# PDOm
PDOm - PDO Wrapper with MySQL Helper

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
require_once './pdom.bootstrap.php'
```

Now execute SELECT query:
```php
$user = pdom('users.14'); // same as "SELECT * FROM users WHERE id = '14'"
echo $user->fullname; // print record field value
```

## PDOm Commands
Here is a list of simple PDOm commands: