<?php

require 'db.php';
$db = new db('mysql:host=localhost;dbname=people;charset=utf8', 'username', 'password');


// insert A
// insert data is expected to be a key / value array
$data = [
    'first_name' => 'Chad',
    'last_name' => 'Burke'
];

// insertId will be returned from mysql, where applicable
$insertId = $db->insert('people', $data);


// insert B
// fields that do not exist [ field_that_doesnt_exist ] in the table will be omitted 
$data = [
    'first_name' => 'Sean',
    'last_name' => 'Burke',
    'field_that_doesnt_exist' => 'Test'
];

// this insert should be the same as insert 'A'
$insertId = $db->insert('people', $data);


// an update works the same as an insert, with the exception of the id being passed
// if a null value is passed, and a column can accept null values then null will be set
// ( clustered-keys are not supported )
$id = 2;

$data = [
    'first_name' => 'Brian'
];

$db->update('people', $data, $id);


// functional equivalent of a fetchAll :
$records = $db->all('select * from people');
var_dump($records);


// returns a single record
$record = $db->one('select * from people');
var_dump($record);


// returns a single value
$name = $db->single("select first_name from people where id = 2");
var_dump($name);


/*

CREATE TABLE  `people` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`first_name` VARCHAR( 75 ) NULL DEFAULT NULL ,
`last_name` VARCHAR( 75 ) NULL DEFAULT NULL
) ENGINE = INNODB;

*/
