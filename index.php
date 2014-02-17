<?php

require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();


/**
 * 1.) Get a list of all objects based on user search. DONE
 * 2.) Insert user into  a the database using key. DONE
 * 3.) Insert diary entry. TODO
 * 4.) Config files outside of directory for passwords, logins etc.
 * 5.) Alter database to allow multiple entries of products on the same day - currently throwing error!

 MAke dummy test tesco account!
 */
$app = new \Slim\Slim();

/*
Add connection checking etc! Respond with status messages TODO!!!!!
*/

/*
Global variables - need to be moved to a higher directory for security!!!!
*/

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


/*Create session key for tesco api
*/
function createSessionKey () {
    global $tescoUsername, $tescoPass, $tescoDevKey, $tescoAppKey, $sessionKey;

        $url = "https://secure.techfortesco.com/groceryapi/restservice.aspx?command=LOGIN&email={$tescoUsername}&password={$tescoPass}&developerkey={$tescoDevKey}&applicationkey={$tescoAppKey}";
        // echo $url;
        $urlContents = json_decode(file_get_contents($url));

        if ($urlContents->StatusCode == 0) {
            return $urlContents->SessionKey;
        }
}

/*Destroys a database connection*/
function destroyConnection ($db) {
    $db = null;
}

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

/* Gets a specific product's details based on a product ID.
*/
$app->get('/product/', function () {
    $searchKey = Slim\Slim::getInstance()->request()->get('id');
    // global $tescoUsername, $tescoPass, $tescoDevKey, $tescoAppKey, $sessionKey;

    $sessionKey = createSessionKey(); 
    echo "fjkasdkjaskjdb";
    echo $sessionKey;
    $productInfoURL = "http://www.techfortesco.com/groceryapi/restservice.aspx?command=PRODUCTSEARCH&searchtext={$searchKey}&extendedinfo=Y&page=1&sessionkey={$sessionKey}";
    $productInfo = json_decode(file_get_contents($productInfoURL), true);
    echo json_encode($productInfo);
});


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
    echo json_encode($newUser);
    }
);

function checkProductEntry($productId) {
    $db = getConnection();
    $sqlStatement = "SELECT productId FROM Products WHERE productId = {$productId}";
    try {
        $stmt = $db->prepare($sqlStatement);
        $stmt->execute();
        $result = $stmt->fetchAll();
        echo count($result);

        if (count($result == 0)) {
            //add to database helper method here!
            addProductToDatabase($productId);
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

function addProductToDatabase ($productId) {
    //get data from tesco and then add to database
    $sessionKey = createSessionKey();
    $productInfoURL = "http://www.techfortesco.com/groceryapi/restservice.aspx?command=PRODUCTSEARCH&searchtext={$productId}&extendedinfo=Y&page=1&sessionkey={$sessionKey}";
    $result = json_decode(file_get_contents($productInfoURL));
    $name = $result->Products[0]->Name;
    $calories = $result->Products[0]->RDA_Calories_Count;
    $salt = $result->Products[0]->RDA_Salt_Grammes;
    $fat = $result->Products[0]->RDA_Fat_Grammes;
    $satFat = $result->Products[0]->RDA_Saturates_Grammes;
    $sugar = $result->Products[0]->RDA_Sugar_Grammes;

    $sql = "INSERT INTO Products (productId, name, calories, salt, fat, saturates, sugar) VALUES (:id, :name, :calories,
        :salt, :fat, :satFat, :sugar)";

    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $productId);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':calories', $calories);
        $stmt->bindParam(':salt', $salt);
        $stmt->bindParam(':fat', $fat);
        $stmt->bindParam(':satFat', $satFat);
        $stmt->bindParam(':sugar', $sugar);
        $stmt->execute();
        $db = null;
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

/* POST method that inserts an entry into the diary database. CURRENTLY THROWING ERROR FOR SAME PRODUCT AND PERSON ENTRY.
*/
$app->post('/insertIntoDiary', function () use ($app) {
    $request = Slim\Slim::getInstance()->request();
    $entry = json_decode($request->getBody());
    $user = $entry->id;
    $product = $entry->productCode;
    $date = $entry->date;
    // echo $user;
    // echo $product;
    // echo $date;

    checkProductEntry($product);
        $sql = "INSERT INTO Diary (dateConsumed, Products_productId, Person_personId) VALUES (:date, :product, :user)";
        try {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':product', $product);
            $stmt->bindParam(':user', $user);
            $stmt->execute();
            $db = null;
            echo "inserted";
        } catch (PDOException $e) {
             echo $e->getMessage();
            }
        
    }
);
/*Gets a set of inique dates that the user has used the application
    SEND PERSON ID USING USER DEFAULTS
*/
$app->get('/getDates', function () {
$sqlStatement = "SELECT DISTINCT dateConsumed FROM Diary WHERE Person_personId=100 ORDER BY dateConsumed DESC";
try {
    $db = getConnection();
    $stmt = $db->prepare($sqlStatement);
    $stmt->execute();
    $result = $stmt->fetchAll();
    echo json_encode($result);
} catch (PDOException $e) {
    echo $e->getMessage();
}
});

/*Gets a list of product names based on the days they were eaten. send date id. can also add search fazcility to view controller?
    ADD USER DEFAULTS IN WHERE FOR SQL STATEMENT
*/
$app->get('/productsFromDate', function() {
    $dateKey = Slim\Slim::getInstance()->request()->get('date');
    $sqlStatement = "SELECT productId, name FROM Products WHERE productId IN (SELECT Products_productId FROM Diary WHERE dateConsumed= {$dateKey})";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sqlStatement);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $db = null;
        echo json_encode($result);
    } catch (PDOException $e) {
        $e->getMessage();
    }
});

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
