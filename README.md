# pdo-mysql-helper
A small class to add helper methods [ insert, update, all, one ] to the php pdo library.

One nuisance I typically encounter while developing is an evolution of table structure as an application progresses.  Whenever a field is added or removed I need to go through and check my model, insert and update statements.  This helper class assists by taking care of the insert and update statements for me, allowing me to focus on more important aspects.

# Some examples as to how it works :

Given an array of data :
```php
$data = [
	'first_name' => 'Chad',
	'last_name' => 'Burke'
];
```

An insert statement to the table `people` would look like this :
```php
$insertId = $db->insert('people', $data);
```

Additionally, if fields that don't exist in your table somehow creep into your data array :
```php
$data = [
	'first_name' => 'Chad',
	'last_name' => 'Burke',
	'field_that_doesnt_exist' => 'Test'
];
```

The statement will still execute .. because I'm checking the data being passed against the `show columns` result in mysql and omitting any values that shouldn't belong.

**Updates** are just as easy :
```php
$id = 2;

$data = [
	'first_name' => 'Brian'
];

$db->update('people', $data, $id);
```

( above ) You simply pass the primary key, just the value, and the helper will, using `show columns`, find the appropriate column name (this is possible because a table can only have one primary key).  If you don't have a primary key then you wouldn't want to use this method.

Other niceties :

Selecting all records, returns an array :
```php
$records = $db->all('select * from people');
```

Selecting one record :
```php
$record = $db->one('select * from people');
```

Selecting one value :
```php
$first_name = $db->single("select first_name from people where id = 2");
```

You can find an example in example.php.

Note that you'll still want to be validating and sanitizing data before using these methods.  I find this type of setup to work well when paired with ExtJS or any other front-end framework that utilizes models.
