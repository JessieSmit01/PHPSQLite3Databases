<?php
/**************************************
 * File Name: testToDos.php
 * User: cst231
 * Date: 2019-11-06
 * Project: CWEB280
 *This class will be used to ensure that the Auto Incrementing works
 *
 **************************************/

require_once "Car.php"; //require car class
require_once 'Movie.php'; //require movie class
require_once 'Pet.php'; //require pet class
require_once 'Repository.php'; //require repository
//TEST 3. AUTOINCREMENT
$car = new Car();
$car->carID = 3; //repository handles this id by ignoring it and sets the id to the next incrementing value in the db
$car->make = 'Ford';
$car->model = 'Mustang';
$car->year = 1995;

$repo = new Repository('test.db'); //instantiate repository

$repo->insert($car); //car is inserted into database - bind values are working correctly same with the auto increment


$newcar = $repo->select($car); //get the car and vardump it to show it exists
var_dump($newcar); //vardump the returned Car object with carID of 1

$newcar->make = "1234"; //change the make

$repo->delete($newcar); //deleted the car based on carID - bind value works correct when binding carID
//also vardumps the bind types. bind types are integer values that tells the statement bind value to bind the data as.

