<?php
/**************************************
 * File Name: Repository.php
 * User: cst231
 * Date: 2019-10-16
 * Project: CWEB280
 *This class will be responsible for executing SQL commands to a table in the database
 * It will need an Entity object that correlates to a table in the  database
 *
 **************************************/


//When you dont know the name of the file you want to include/require use an autoloader
spl_autoload_register(function($entityClassName){
    //look for the entity child class in this folder use relative path
    //all files will be stored in the cst231cweb280a2 folder so we do not need to look outside of the folder
    require_once  $entityClassName . '.php' ;
});


/**
 * Class Repository This class will be used to run commands used in an SQLite3 database
 *
 */
class Repository extends \SQLite3
{
    private $lastStatement; //used for debug purposes. keeps track of last statement ran.

    /**
     * This method will return the last SQL statement ran by the repository
     * @return mixed
     */
    public function getLastStatement()
    {
        //RULE 6: We add the sql string to the lastStatement for easier debugging
        return $this->lastStatement;
    }

    /***
     * This method is to be used once when setting up a web app on a server to ensure that all required tables are created for the web app to work properly
     * @param $entities - CWEB280 Assignment 2 - this function will take in an array of entities and create tables for eaach entity.
     *
     */
    public function createTables($entities)
    {

        //go through each entity passed in
        foreach ($entities as $entityNum=>$entity)
        {
            //get the table name from the entity passed in
            $tbl = $entity->getClassName();
            $dropTable = 'DROP TABLE IF EXISTS ' . $tbl; //need to drop the table if it exists. table name is retrieved from the entity's class name
            $this->exec($dropTable); //execute the statement to drop the table
            $tblCreate = 'CREATE TABLE ' . $entity->getClassName() . '('; //template for the beginning of each create table. Taken from the sql-createtable.php
            $colDefinitions = $entity->getColDefinitions(); //get the column definitions for this entity
            $i = 0; //incrementing integer used to grab each colDefinition row below in the while loop

            while($i < sizeof($colDefinitions))
            {
                //add the column name, datatype, datatype size, pk or not, nullable (or not) and if the column is auto incrementing to the $tblCreate for EACH entity property.
                //this is grabbed from the entity's colDefinitions attribute, which is a 2D array that keeps track of its column definitions.
                //if size is null (used for primary incrementing key), the () after the datatype will not be added because this creates an error.
                //if pk value is true, it will add PRIMARY KEY to the string otherwise it will not
                //if the column is  primary key, it will not add NOT NULL because primary keys are automatically required.
                //If autoincrement is true, it will add AUTOINCREMENT at the end of the tblCreate String
                $tblCreate .= $colDefinitions[$i]['colName']. ' ' . $colDefinitions[$i]['datatype'] . ($colDefinitions[$i]['size'] != null ?
                    '(' . $colDefinitions[$i]['size'] . ') ' : ' ') . ($colDefinitions[$i]['pk'] ? 'PRIMARY KEY ' : '') . ($colDefinitions[$i]['null'] || $colDefinitions[$i]['pk'] ? '' : " NOT NULL ") .  ($colDefinitions[$i]['autoincrement'] ? 'AUTOINCREMENT,': ',' ); //insert AUTOINCREMENT at end if need to auto increment
                //https://www.tutorialspoint.com/sqlite/sqlite_using_autoincrement.htm used this site to help me understand how to declare autoincrement in sqlite3
                //I also used 'DB Browser for SQLite' to help me perfect this SQLite3 CREATE TABLE command.
                //also used the sql-createtable.php file done in class to help construct the create table command
                //having the '([number]) after the INTEGER datatype gave errors so I had checks for this, DB Browser for SQLite helped
                //me solve this error.
                $i++; //increment the value row number
            }

            //get rid of the leading ',' at the end of the tblCreate string.
            $tblCreate = substr($tblCreate, 0, strlen($tblCreate) - 1);
            //add a ')' to the end of the string to complete the TABLE CREATE statement
            $tblCreate .= ')';
            $this->exec($tblCreate); //execute the finalized create table command for this single entity
            //The table will be created
        }

    }

    /**
     * This method will bind the values of each given field into the stmt provided and bind it using the bind type from the bindTypes array.
     * @param $fields - fields to bind values form
     * @param $stmt - statement to bind to
     * @param $bindTypes - bind values for each field
     * @param $sql - used for debugging, pass in current sql to keep lastStatement updated.
     * @return mixed - returns the result of stmt when executed.
     */
    function bindValues($fields, $stmt, $bindTypes, $sql)
    {
        $i = 1; //incrementing value
        foreach($fields as $field=>$val) //go through each field that needs to be bound
        {
            $sql .= " [$i:$field=$val] "; //part of rule 6: show what position, field and value were bound
            $stmt->bindValue($i, $val,  $bindTypes[$field]); //bind the value of the filter and its bind data type
            $i++; //increment i
        }
        $this->lastStatement = $sql; //keep track of last statement

        return $stmt; //return the result of the statement.
    }

    /**
     * This method will take in an array of fields and values and a table name and
     * return an entity created from those fields and values
     * @param $result - 2D array of fields and values after calling a GET from the database
     * @param $tbl - the table name of the data - equal to the class name. Used to create the appropriate object
     * @return mixed - returns an entity built with the information from the database
     *
     */
    function toEntity($result, $tbl)
    {
        while($tableRow = $result->fetchArray(SQLITE3_ASSOC)) //go through each row as an associative array
        {
            //Create an instance of the entity child object -but we don't know the name of the class
            //OR do WE!!!
            $entityInstance = new $tbl (); //create a new entity of type $tbl
            foreach ($tableRow as $field=>$valInDB) //go through each field and value in table row
            {
                //set the entity property to corresponding value from the database
                $entityInstance->$field = $valInDB;
            }
            //return the entity created
            return $entityInstance;

        }
    }



    /***
     * This method will take in an entity and insert it into the table
     * @param $entity
     * @return int - Statement error returns -1, result error returns 0, success returns 1
     */
    public function insert($entity)
    {

        //figure out what table to insert to
        //RULE 1: Entity child class name is the table name.
        $tbl = $entity->getClassName();

        //figure out the fields/columns in the table
        //RULE 2: the entity child will have properties that correspond to the columns in the table
        $properties = get_object_vars($entity); //get the properties and values in the entity child and return an associative array
        //DONE
        $incrementingFields = $entity->getIncrementingFields(); //Get the incrementing fields from the entity
        $bindTypes = $entity->getBindTypes(); //get the bind types form the entity

        foreach ($incrementingFields as $field) //go through each incrementing field in the entity
        {
            //Used unset in class it destroys the variable taken in
            //we dont need to do anything to incrementing values since they automatically increment
            //so dont need to bind them or set them.
            //ignores any values given for auto incrementing fields basically and sets the auto incrementing value
            //to the next value in the autoincrement sequence.
            unset($properties[$field]); //remove that field from the $properties array
        }

        //figure out where that data is for each field/column
        //RULE 3: the $properties array has keys and values, the values are the data we will insert to the table.
        //create a prepared statement (with the exact number of ? - number of columns)
        $fieldString = implode(',', array_keys($properties)); //implode used in class
        //string of '?,' repeated for the amount of properties - 1 plus the amount of autoincrementing fields.
        //do not need a q mark for auto incrementing fields
        $qMarkString = str_repeat("?,", count($properties) -1) . '?';

        $sql = "INSERT INTO $tbl ($fieldString) VALUES($qMarkString)"; //start of the sql INSERT statement
        $this->lastStatement = $sql; //part of the RULE 6 : to make debugging easier

        //MINICISE: 20 Create the prepared INSERT statement use double quotes
        $stmt = $this->prepare($sql);

        //if statement false then exit function by returning false
        if(!$stmt)
        {
            return -1; //return error value
        }

        //bind the values to the statement
        //REDUCED - reduced the binding into a single function.
        $result = $this->bindValues($properties, $stmt, $bindTypes, $sql);
        //execute the statement
        $result = $stmt->execute();
        $stmt->close(); //we dont need to loop through result so we can close here

        //return some sort of indication of success or failure
        //RULE 4: if result is an object return 1, other wise return 0
        return $result ? 1 : 0;

    }

    //RULE 8: In a select statement the entity properties that contain a value will become part of the where clause

    /**
     * This method will take in an entity and grab all entity's that relate to this entity form the entity table
     * @param $entity - entity to select form the database
     * @return int|mixed - returns an object if the select is successful or integer declaring error if not
     */
    public function select($entity){
        //get the table name
        $tbl = $entity->getClassName();

        //get the fields comma separated string
        $properties = get_object_vars($entity); //used in class
        //create a string made up of each property in the $properties array with a ',' in between
        $fieldString = implode(',', array_keys($properties)); //used in class

        //figure out the where clause - default = 1=1
        $filter = []; //stores all non-empty properties
        $whereString = ""; //Example: $whereString = 'familyName=? AND givenName=? ....
        foreach($properties as $field=>$val) //go through each property
        {
            if(!empty($val)) //check for the empty property -used in class
            {
                $and = count($filter) > 0 ? 'AND' : ''; //check for more than one filter
                $filter[$field] = $val; //add the value to the associated array $filter in its key field
                $whereString .= " $and $field=?"; //add onto the where string
            }
        }//Example: id=? AND familyName=? AND givenName=?
        $whereString = empty($whereString) ? '1=1' : $whereString; //if no where string then use 1=1 which will return all table rows

        //MINICISE: 21: create the prepared statement Like we stated in Rule 6 and rule 7
        //figure out the sql statement and save to last statement
        $sql = "SELECT $fieldString FROM $tbl WHERE $whereString"; //select statement
        $this->lastStatement = $sql; //for easier debug - part of rule 6
        //prepare the statement
        $stmt = $this->prepare($sql);
        if(!$stmt){return -1;} //part of rule 7
        //bind the values for the where clause if needed

        $bindTypes = $entity->getBindTypes(); //get the bind types for the entity

        //bind the values to the statement
        //REDUCED - changed the binding for each loop into a function
        $stmt = $this->bindValues($filter, $stmt, $bindTypes, $sql);
        //made the bind loop we did a few times into its own method to reduce code

        //execute the statement
        $result = $stmt->execute(); //execute statement

        if(!$result){$stmt->close(); return 0;} //statement could not be executed
        //loop through the result and store the queried table rows into a new 2d array
        //REDUCED - changed the loop of going through object data taken from the database and converting it into an entity,
        //into its own method to reduce code.
        $resultArray = $this->toEntity($result, $tbl); //instantiate array

        //close the statement
        $stmt->close();

        //return the 2d array
        return $resultArray;
    }

    /**
     * This method will take in an entity and update its fields based on its primary key in the table
     * @param $entity - entity to update
     * @return int - whether succeed or fail
     */
    public function update($entity)
    {
        //minicise 28: fix the id to use pkName
        $tableName = $entity->getClassName(); //get table name
        $properties = get_object_vars($entity); //gets each property associated to the entity and its value and puts it into an associative array.
        $pkName = $entity->getPkName(); //get the primary key name for the entity
        $pkValue = $properties[$pkName]; //get the entity's primary key
        unset($properties[$pkName]); //remove the entity's primary key field and value from th $properties array, dont need to update it

        $fieldString = implode('=?, ', array_keys($properties)) . '=?'; //Creates a string of values that we can update the row with in the database
        $where = "$pkName =?"; //where string is used to search by the entities primary key name
        $sql = "UPDATE $tableName SET $fieldString WHERE  $where"; //our sql string that is used for the update. uses the tableName, fieldString and where string to completely update the specified entity in the database
        $stmt = $this->prepare($sql); //need to prepare the statement
        if(!$stmt){return -1;}; //if there is an error in preparing the statement, stmt will equal false so return -1 and exit.
        $i = sizeof($properties) + 1; //the ? position in the fieldString string
        $bindTypes = $entity->getBindTypes(); //get the bind types form the entity
        //bind values to single statement
        //REDUCED - reduced binding into a single method.
        $resultUpdate = $this->bindValues($properties, $stmt, $bindTypes, $sql);//DONE

        //need to bind the id last since the where string is searching by id and id is no longer in the properties array
        //get the bind value stored in the entity's bindTypes array under the key of its pkName.

        $this->lastStatement .= " [$i:$pkName=$pkValue] "; //part of rule 6: show what position, field and value were bound

        // //set this as the last statement executed --for debug
        if(!$resultUpdate){ //resultUpdate failed
            return 0; //0 indicates an error executing the statement
        }
        return 1; //if resultUpdate successful, return 1 to indicate success.
    }

    /**
     * This method will take in an entity and delete it from the appropriate table
     * @param $entity - entity to delete
     * @return int - whether succeed or fail
     */
    public function delete($entity)
    {
        //get table name
        $tableName = $entity->getClassName();

        //firgure out primary key field name
        $pkName = $entity->getPkName(); //Example: 'studentID'
        //figure out where clause
        $where = "$pkName=?"; //where pkName = pkValue


        //prepare statement using rules
        $sql = "DELETE FROM $tableName WHERE $where"; //delete statement
        $this->lastStatement = $sql; //set the last statement
        $stmt = $this->prepare($sql); //prepare the statement
        if(!$stmt){return -1;} //statement could not prepare return error
        $bindTypes = $entity->getBindTypes(); //get the bindTypes associative array from the object

        //Bind values in where clause - remember the rules
        $sql .= " [1:$pkName=" . $entity->$pkName . " ] "; //update the $sql
        //get the bind type for the key of this entities pkName.
        //use the pkName as the bindType key. ***NOTE: Learned this little hack during class***
        //bind the value and bind type of the primary key of this entity to the statement
        $stmt->bindValue(1, $entity->$pkName, $bindTypes[$pkName]); //DONE
        //execute statement - rememeber the rules
        $this->lastStatement = $sql; //set last statement
        $result = $stmt->execute(); //get the result of the statement executed
        $stmt->close(); //close the statement

        return $result? 1 : 0;
        //return indication of success - remember the rules

    }

}