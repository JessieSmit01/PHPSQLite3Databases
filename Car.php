<?php
/**************************************
 * File Name: Car.php
 * User: cst231
 * Date: 2019-11-04
 * Project: CWEB280
 *This class will be a child of Entity
 *
 **************************************/
//require, require_once effectively copy paste code from another file into this file
require_once 'Entity.php'; //this class extends entity so need to have it required to be able to declare the Extends

//Extends is similar to inheritance in other languages
class Car Extends Entity
{
    public $carID; //primary key for car
    public $make; //make of the car max size 50 characters
    public $model;//model of the car max size 20 characters
    public $year; //year of the car max value 2050 -set as 2050 to future-proof this value until 2050


    /**
     * Car constructor. sets this entities pkName as carID
     * * input column definitions
     * input bind types
     */
    public function __construct()
    {
        $this->pkName = 'carID'; //set the pkName of this object to carID
        $this->inputColDefinitions(); //load column definitions
        $this->inputBindTypes(); //load the bind types
    }

    /**
     * This method will load the bind types for the associated field name by calling this Entities setBindType function
     * SQLite3 bind values were used in class so I used intellisense and Ctrl + q to help me select the proper SQLite3 bind value.
     */
    public function inputBindTypes()
    {
        $this->setBindType('carID', SQLITE3_INTEGER); //add bind type for carID
        $this->setBindType('make', SQLITE3_TEXT);  //add bind type for make
        $this->setBindType('model', SQLITE3_TEXT);  //add bind type for model
        $this->setBindType('year', SQLITE3_INTEGER);  //add bind type for year
    }

    /**
     * This function will set up the column definition for the Movie Object.
     * Only to be called when creating a new table in the database
     *  https://www.sqlite.org/datatype3.html used this as a resource to help me ensure that I was choosing the correct datatypes for this entity
     */
    public function inputColDefinitions()
    {
        $this->setCols('carID', 'INTEGER', null, true, false, true); //col definition for carID
        $this->setCols('make', 'VARCHAR', 50, false, false, false); //col definition for make
        $this->setCols('model', 'VARCHAR', 20,false, false, false); //col definition for model
        $this->setCols('year', 'INTEGER', 2050, false, false, false); //col definition for year


    }


}