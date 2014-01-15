<?php

require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();


/**
 * 1.) Get a list of all objects based on user search. DONE
 * 2.) Insert user into  a the database using key. TODO
 * 3.) Insert diary entry. TODO
 * 4.) Config files outside of directory for passwords, logins etc.

 MAke dummy test tesco account!
 */
$app = new \Slim\Slim();

/*
Add connection checking etc! Respond with status messages TODO!!!!!
*/

/*
Global variables*/

$sessionKey;
$tescoUsername = "kunal.mandalia@hotmail.com";
$tescoPass = "therock1";
$tescoDevKey = "4Ew9aC50PHIvET3dfFTe";
$tescoAppKey = "2E48578A7C28112D2F84";

$hostName = "138.251.206.58";
$sqlUsername = "km842";
$password = "8/c328D5";
$schemaName = "km842_db";
/*
*/

/*Creates connection to database
NEEDS TO RETURN STATUS MESSAGE!
*/
function getConnection () {
    global $hostName, $sqlUsername, $password, $schemaName;

    if (isset($hostName, $sqlUsername, $password, $schemaName)) {
        $db = new PDO ("mysql:host=$hostName;dbname=$schemaName;port=3306", $sqlUsername, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
     } else {
        echo "not connected!";
        }
    } 
/*
*/


/*Destroys a database connection*/
function destroyConnection ($db) {
    $db = null;
}
/*
*/

/*GET methods that returns a list of products based on a search parameter - a id or text*/
$app->get(
    '/hello/',
    function () {
        //ALWAYS USE THIS METHOD TO GET SLIM INSTANCE FOR ALL REQUESTS!
        $searchKey = Slim\Slim::getInstance()->request()->get('id');

        // Needed to get session key from Tesco so that we can use their api.
        global $tescoUsername, $tescoPass, $tescoDevKey, $tescoAppKey;  
        $url = "https://secure.techfortesco.com/groceryapi/restservice.aspx?command=LOGIN&email={$tescoUsername}&password={$tescoPass}&developerkey={$tescoDevKey}&applicationkey={$tescoAppKey}";
        // echo $url;
        $urlContents = json_decode(file_get_contents($url));

        if ($urlContents->StatusCode == 0) {
           global $sessionKey;
           $sessionKey = $urlContents->SessionKey;
           // echo "\n{$urlContents->StatusCode}\n{$sessionKey}";
           // echo "\nin here\n";

           $search = "http://www.techfortesco.com/groceryapi/restservice.aspx?command=PRODUCTSEARCH&searchtext={$searchKey}&page=1&sessionkey={$sessionKey}";
           $searchResults = json_decode(file_get_contents($search), true);

        //    foreach ($searchResults['Products'] as $data) {
        //        echo "{$data["Name"]}\n";
        //    }
        // } else {
        //     echo $urlContents->StatusCode;
        //     echo "\nnot working!";
        // }

           echo json_encode($searchResults);
    }
}
);

/* POST method from InitialViewController that inserts a user to the database.
*/
$app->post(
    '/post', function () use ($app){
    $request = Slim\Slim::getInstance()->request();
    $newUser = json_decode($request->getBody());

    $id = $newUser->id;
    $name = $newUser->name;
    $dob = $newUser->dob;
    $height = $newUser->height;
    $weight = $newUser->weight;


    $sqlStatement = "INSERT INTO Person (personId, name, dob, height, weight) VALUES (:id, :name, :dob, :height, :weight)";

    try {
        $db = getConnection();
        $stmt = $db->prepare($sqlStatement);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':height', $height);
        $stmt->bindParam(':weight', $weight);
        $stmt->execute();

        $db = null;
    } catch (PDOException $e) {
        $e->getMessage();
    }



    // echo "$id\n";
    // echo "$name\n";
    // echo "$dob\n";
    // echo "$height\n";
    // echo "$weight\n";

    // echo $newUser;
    echo json_encode($newUser);
    }
);
/*
*/

// PUT route
$app->put(
    '/put',
    function () {
        echo 'This is a PUT route';
    }
);

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete(
    '/delete',
    function () {
        echo 'This is a DELETE route';
    }
);

$app->run();
