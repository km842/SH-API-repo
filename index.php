<?php

require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();


/**
 * 1.) Get a list of all objects based on user search.
 * 2.) Insert user into  a the database using key.
 * 3.) Insert diary entry
 */
$app = new \Slim\Slim();

/*
Add connection checking etc! Respond with status messages TODO!!!!!
*/

function getConnection () {
    // $hostName = "km842.host.cs.st-andrews.ac.uk";
    $hostName = "138.251.206.58";
    $sqlUsername = "km842";
    $password = "8/c328D5";
    $schemaName = "km842_db";

    if (isset($hostName, $sqlUsername, $password, $schemaName)) {
        $db = new PDO ("mysql:host=$hostName;dbname=$schemaName;port=3306", $sqlUsername, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
     } else {
        echo "not connected!";
        }
    } 
    

// GET route
$app->get(
    '/hello',
    function () {
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
