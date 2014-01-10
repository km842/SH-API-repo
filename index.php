<?php

require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();


/**
 * 1.) Get a list of all objects based on user search.
 * 2.) Insert user into  a the database using key.
 * 3.) Insert diary entry.
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


// GET route
$app->get(
    '/hello/',
    function () {
        //ALWAYS USE THIS METHOD TO GET SLIM INSTANCE FOR ALL REQUESTS!
        $searchKey = Slim\Slim::getInstance()->request()->get('id');

        global $tescoUsername, $tescoPass, $tescoDevKey, $tescoAppKey;  
        $url = "https://secure.techfortesco.com/groceryapi/restservice.aspx?command=LOGIN&email={$tescoUsername}&password={$tescoPass}&developerkey={$tescoDevKey}&applicationkey={$tescoAppKey}";
        echo $url;
        $urlContents = json_decode(file_get_contents($url));

        if ($urlContents->StatusCode == 0) {
           global $sessionKey;
           $sessionKey = $urlContents->SessionKey;
           echo "\n{$urlContents->StatusCode}\n{$sessionKey}";
           echo "\nin here";

           $search = "http://www.techfortesco.com/groceryapi/restservice.aspx?command=PRODUCTSEARCH&searchtext={$searchKey}&page=1&sessionkey={$sessionKey}";
           $searchResults = json_decode(file_get_contents($search), true);

           foreach ($searchResults['Products'] as $data) {
               echo "{$data["Name"]}\n";
           }
        } else {
            echo $urlContents->StatusCode;
            echo "\nnot working!";
        }
    }
);

// POST route
$app->post(
    '/post', function () use ($app){
    $request = Slim\Slim::getInstance()->request();
    $newUser = json_decode($request->getBody());

    $id = $newUser->id;
    $name = $newUser->name;
    $dob = $newUser->dob;
    $height = $newUser->height;
    $weight = $newUser->weight;

    echo "$id\n";
    echo json_encode(array('user'=> $newUser));
    }
);

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
