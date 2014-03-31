<?php

require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();


/*!
	@class Food Nudge API
*/
$app = new \Slim\Slim();

/*
Add connection checking etc! Respond with status messages TODO!!!!!
*/

/*
Global variables - need to be moved to a higher directory for security!!!!
*/

/*!All the variables are self documenting by name*/

/*!@param sessionKey
The session key generated as a response from the Tesco API.
*/
$sessionKey;

/*!@param tescoUsername
The username required to login to the Tesco API.
*/
$tescoUsername = "kunal.mandalia@hotmail.com";

/*!@param 
The password for use of the Tesco API.
*/
$tescoPass = "therock1";

/*!@param tescoDevKey
The tesco developer key, as distributed by the Tesco API.
*/
$tescoDevKey = "4Ew9aC50PHIvET3dfFTe";

/*!@param tescoAppKey
The Tesco API application key required to acces the API.
*/
$tescoAppKey = "2E48578A7C28112D2F84";

/*!@param hostName
The IP address of the MySQL database
*/
$hostName = "138.251.206.58";

/*!@param sqlUsername
The MySQL username to access the database.
*/
$sqlUsername = "km842";

/*!@param password
The MySQL password to access the database.
*/
$password = "8/c328D5";

/*!@param schemaName
The schema name on the database where the tables are located.
*/
$schemaName = "km842_db";

/*!@param googleApiKey
The API key required to access the Google Places API.
*/
$googleApiKey = "AIzaSyCK7y0TT49xIi3IafMxbsmvrykDLlYNpMA";


/*!
 @Function getConnection
 @result
Returns a database connection using global variables.
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

/*!
 @Function createSessionKey
 @result
Returns an instance of a session key that updates the global sessionKey variable.
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

/*!
 @Function destroyConnection
 @param db
An instance of the database that should be nulled.
 @result
A null connection. 
  */
 function destroyConnection ($db) {
    $db = null;
}

/*!
 @Function getAllReferences
 @param latitude
The latitude of the location.
 @param longitude
The longitude of the location.
 @param maxResults
The maximun number of results possible from the Google Places API.
 @result
An array of location data.
  */
function getAllReferences ($latitude, $longitude, $maxResults = 60, $nextToken = false) {
    global $googleApiKey;

    $references = array();
    $nextStr = "";
    if ($nextToken) {
        $nextStr = "pagetoken={$nextToken}";
    }
    $googleUrl = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$latitude},{$longitude}&rankby=distance&keyword=pointsofinterest&sensor=true&key={$googleApiKey}&{$nextStr}";
    // echo "\n{$googleUrl}";
    sleep(2);
    $dataArray = json_decode(file_get_contents($googleUrl));
    if (isset($dataArray->{'status'}) && $dataArray->{'status'} == "OK") {
            array_push($references, $dataArray->{'results'});
        if (!empty($dataArray->{'next_page_token'}) && count($references) < $maxResults) {
            // echo "\n{$dataArray['next_page_token']}";
            $nextArray = getAllReferences($latitude, $longitude, $maxResults-20, $dataArray->{'next_page_token'});   
            $references = array_merge($references, (array)$nextArray);
        }
        return $references;
    }

}


/*!
 @Function locations
 @result
Returns a JSON encoded array to the user.
  */
$app->get('/locations/', function() {
    $latitude = Slim\Slim::getInstance()->request()->get('lat');
    $longitude = Slim\Slim::getInstance()->request()->get('long');
    global $googleApiKey;      
    $references = getAllReferences($latitude, $longitude);
    echo json_encode($references);    
});

/*!
 @Function hello
 @result
Returns a JSON encoded array of products based on a search parameter.
  */
$app->get(
    '/hello/',
    function () {
        //ALWAYS USE THIS METHOD TO GET SLIM INSTANCE FOR ALL REQUESTS!
        $searchKey = Slim\Slim::getInstance()->request()->get('id');

        // Needed to get session key from Tesco so that we can use their api.
        global $tescoUsername, $tescoPass, $tescoDevKey, $tescoAppKey;  
        $url = "https://secure.techfortesco.com/groceryapi/restservice.aspx?command=LOGIN&email={$tescoUsername}&password={$tescoPass}&developerkey={$tescoDevKey}&applicationkey={$tescoAppKey}";
        $urlContents = json_decode(file_get_contents($url));

        if ($urlContents->StatusCode == 0) {
           global $sessionKey;
           $sessionKey = $urlContents->SessionKey;
           $search = "http://www.techfortesco.com/groceryapi/restservice.aspx?command=PRODUCTSEARCH&searchtext={$searchKey}&page=1&sessionkey={$sessionKey}";
           $searchResults = json_decode(file_get_contents($search), true);
           echo json_encode($searchResults);
    }
});

/* Gets a specific product's details based on a product ID.
Returns json of product details.
*/
/*!
 @Function product
 @result
Returns JSON encoded details of a specific product based on an ID.
  */
$app->get('/product/', function () {
    $searchKey = Slim\Slim::getInstance()->request()->get('id');
    $sessionKey = createSessionKey(); 
    $productInfoURL = "http://www.techfortesco.com/groceryapi/restservice.aspx?command=PRODUCTSEARCH&searchtext={$searchKey}&extendedinfo=Y&page=1&sessionkey={$sessionKey}";
    $productInfo = json_decode(file_get_contents($productInfoURL), true);
    echo json_encode($productInfo);
});


/* POST method from InitialViewController that inserts a user to the database.
*/
/*!
 @Function post
 @result
Adds a user to the database from the InitialViewController
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

/*!
 @Function checkProductEntry
 @param productId
The product id of the product to be checked.  
 @result
Nothing
  */
function checkProductEntry($productId) {
    $db = getConnection();
    $sqlStatement = "SELECT productId FROM Products WHERE productId = {$productId}";
    try {
        $stmt = $db->prepare($sqlStatement);
        $stmt->execute();
        $result = $stmt->fetchAll();
        // echo count($result);
        // echo "here";
        // method working!!!!!
        if (count($result) == 0) {
            //add to database helper method here!
            addProductToDatabase($productId); 
            echo "added";
        }
    } catch (PDOException $e) {
        echo "executing";
        echo $e->getMessage();
    }
}

/*Helper method that adds a product to the database.
*/
/*!
 @Function addProductToDatabase
 @param productId
The product ID of the product to be added to the database.
 @result
Returns a JSON encoded array to the user.
  */
function addProductToDatabase ($productId) {
    //get data from tesco and then add to database
    $sessionKey = createSessionKey();
    $productInfoURL = "http://www.techfortesco.com/groceryapi/restservice.aspx?command=PRODUCTSEARCH&searchtext={$productId}&extendedinfo=Y&page=1&sessionkey={$sessionKey}";
    $result = json_decode(file_get_contents($productInfoURL));
    // echo json_encode($result);
    if (isset($result->Products[0]->Name)) {
    $name = $result->Products[0]->Name;
    $calories = $result->Products[0]->RDA_Calories_Count;
    $salt = $result->Products[0]->RDA_Salt_Grammes;
    $fat = $result->Products[0]->RDA_Fat_Grammes;
    $satFat = $result->Products[0]->RDA_Saturates_Grammes;
    $sugar = $result->Products[0]->RDA_Sugar_Grammes;
    echo $result->Products[0]->RDA_Sugar_Grammes;

// check other 100g entries, add those instead!    

    $sql = "INSERT INTO Products (productId, name, calories, salt, fat, saturates, sugar) VALUES (:id, :name, :calories,
         :salt, :fat, :satFat, :sugar)";

    try {
        $db = getConnection();
        echo "get";
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
        echo "inserted into products";
    } catch (PDOException $e) {
        echo $e->getMessage();
        }
    } else {
        echo "hacked";
    }

}

/* POST method that inserts an entry into the diary database. 
*/
/*!
 @Function insertIntoDiary
 @result
Inserts a a product into the MySQL database.
  */
$app->post('/insertIntoDiary', function () use ($app) {
    $request = Slim\Slim::getInstance()->request();
    $entry = json_decode($request->getBody());
    $user = $entry->id;
    $product = $entry->productCode;
    $date = $entry->date;
    $diaryId;

    checkProductEntry($product);

    echo "{$user}\n{$product} \n{$date}";


    $sql = "SELECT DiaryId FROM Diary WHERE Person_personId = '{$user}' AND Products_productId = {$product}";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        var_dump($result);
        if (!$result) {
            echo "broken in here";

            $insertSql = "INSERT INTO Diary (Products_productId, Person_personId) VALUES (:product, :user)";
            echo $user;
            echo "{$product}";
            $insertStmt = $db->prepare($insertSql);
            $insertStmt->bindParam(':product', $product);
            $insertStmt->bindParam(':user', $user, PDO::PARAM_STR, 36);
            $insertStmt->execute();
            echo "broken";
            $diaryId = $db->lastInsertId();
            echo "{$diaryId}";
        } else {
            echo "now here";
            $diaryId = $result[0]['DiaryId'];
            // echo "else";
        }
         addToLog($diaryId, $date, $db);
    //     echo $diaryId;
    //     try {
    //     $insert2 = "INSERT INTO Log (DiaryId, dateConsumed) VALUES (:diaryId, :date)";
    //     $insert2stmt = $db->prepare($insert2);
    //     $insert2stmt->bindParam(':date', $date);
    //     $insert2stmt->bindParam(':diaryId', $diaryId);
    //     $insert2stmt->execute();
    //     } catch (PDOException $f) {
    //         echo "FAiling hard";
    //         echo ($f->getMessage());
    //         echo "failed";
    //     }
    //     var_dump ($result);
    //     echo($diaryId);
    } catch (PDOException $e) {
        echo ($e->getMessage());
    }
    //     $db = null;
});

/*Inserts product and user data into the database*/
/*!
 @Function addToLog
 @param diaryId
The diary id of the product and user.
 @param dateConsumed
The date that the product was consumed.
 @param db
The database object required to perform database options.   
 @result
Returns a JSON encoded array to the user.
  */
function addToLog($diaryId, $dateConsumed, $db) {
    echo "\n{$diaryId}\n{$dateConsumed}";
    $sql = "INSERT INTO Log (DiaryId, dateConsumed) VALUES (:diaryId, :date)";
    
    try {
        echo "loggin";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':date', $dateConsumed, PDO::PARAM_STR, 12);
        $stmt->bindParam(':diaryId', $diaryId);
        $stmt->execute();
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

}


/*Gets a set of inique dates that the user has used the application
    SEND PERSON ID USING USER DEFAULTS
*/
$app->get('/getDates', function () {
$sqlStatement = "SELECT DISTINCT l.dateConsumed FROM Log l INNER JOIN Diary d ON l.DiaryId = d.DiaryId WHERE Person_personId = 100";
try {
    $db = getConnection();
    $stmt = $db->prepare($sqlStatement);
    $stmt->execute();
    $result = $stmt->fetchAll();
    $db = null;
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
    $sqlStatement = "SELECT p.productId, p.name FROM Products p INNER JOIN Diary d ON p.productId = d.Products_productId
    INNER JOIN Log l ON d.DiaryId = l.DiaryId WHERE d.Person_personId = 100 AND l.dateConsumed = {$date}";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sqlStatement);
        $stmt->bindParam(':date', $dateKey);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $db = null;
        echo json_encode($result);
    } catch (PDOException $e) {
        $e->getMessage();
    }
});

$app->get('/test', function() {
   $db = getConnection();
   addToLog(27, '2011-11-11', $db);
});

$app->run();
