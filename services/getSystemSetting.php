<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: getSystemSetting.php - Get system setting for specified name or key
//
// Input JSON:
// {"name":"logAllCalls",
//  "dump":true}
//
// Output JSON:
//   {"errorCode":0,
//    "name":"logAllCalls",
//    "value":"0"
//    "query":"SQL queries run"}
//
// Output JSON:
//   {"errorCode":1,
//    "error":"Long exception stack trace"}
//
// Functions:
//    None
//
// Query Parameters:
//    name: name of setting whose value is desired
//
// Custom Headers:
//    ApiKey: Must contain magic value for this service to be employed
//
// Session Variables:
//    None
//
// Stored Procedures:
//    getSystemSetting - stored procedure to find the value for desired key or name
//
// JavaScript functions:
//    None
//
// Revisions:
//    1. Sundar Krishnamurthy          sundar_k@hotmail.com       10/24/2020      Initial file created.

ini_set('session.cookie_httponly', TRUE);           // Mitigate XSS
ini_set('session.session.use_only_cookies', TRUE);  // No session fixation
ini_set('session.cookie_lifetime', FALSE);          // Avoid XSS, CSRF, Clickjacking
ini_set('session.cookie_secure', TRUE);             // Never let this be propagated via HTTP/80

// Include functions.php that contains all our functions
require_once("../functions.php");

// Include ipfunctions.php to convert IP address to decimal
require_once("../ipfunctions.php");

// Start output buffering on
ob_start();

// Start the initial session
session_start();

// First off, check if the application is being used by someone not typing the actual server name in the header
if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier) {
    // Transfer user to same page, served over HTTPS and full-domain name
    header("Location: https://" . $global_siteCookieQualifier . $_SERVER["REQUEST_URI"]);
    exit();
}   //  End if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier)

// Authorized client that is asking for settings for a user landing on the page for the first time
if (($_SERVER["REQUEST_METHOD"] === "POST") &&
    (isset($_SERVER["HTTP_APIKEY"])) &&
    ($_SERVER["HTTP_APIKEY"] === "$$API_KEY$$") &&                     // $$ API_KEY $$
    ($_SERVER["SERVER_ADDR"] === $_SERVER["REMOTE_ADDR"])) {

    $postBody = utf8_decode(urldecode(file_get_contents("php://input")));

    // We found a valid body to process
    if ($postBody !== "") {

        $rowFound     = false;
        $bitmask      = 0;
        $dump         = false;
        $query        = null;

        $errorCode    = 0;
        $errorMessage = null;

        $name         = null;

        $responseJson = array();
 
        $userRequest  = json_decode($postBody, true);

        // Check if name is part of the input json set
        if (array_key_exists("name", $userRequest)) {

            $name = trim($userRequest["name"]);

            if (($name !== "") && ($name !== null)) {
                $bitmask  = 1;
            }   //  End if (($name !== "") && ($name !== null))
        }   // End if (array_key_exists("name", $userRequest))

        // Check if dump is part of the input json set
        if (array_key_exists("dump", $userRequest)) {

            $dump = boolval($userRequest["dump"]);
        }   // End if (array_key_exists("dump", $userRequest))

        // We found all that we need to read from the DB
        if ($bitmask === 1) {

            // Log the request coming in
            // Connect to DB
            $con = mysqli_connect($global_dbServer, $global_dbUsername, $global_dbPassword);

            // Unable to connect, display error message
            if (!$con) {
                $errorCode    = 1;
                $errorMessage = "Could not connect to database server.";
            } else {
                // DB selected will be selected Database on server
                $db_selected = mysqli_select_db($con, $global_dbName);

                // Unable to use DB, display error message
                if (!$db_selected) {
                    $errorCode    = 2;
                    $errorMessage = "Could not connect to the database.";
                } else {
                    // All individual parameters need to be checked

                    // name
                    $useName = mysqli_real_escape_string($con, $name);

                    if (strlen($useName) > 32) {
                        $useName = substr($useName, 0, 32);
                    }   //  End if (strlen($useName) > 32)

                    // This is the query we will run to get setting for specified name
                    $query  = "call getSystemSetting('$useName');";

                    // Result of query
                    $result = mysqli_query($con, $query);

                    // Unable to fetch result, display error message
                    if (!$result) {
                        $errorCode     = 3;
                        $errorMessage  = "Invalid query: " . mysqli_error($con) . "<br/>";
                        $errorMessage .= ("Whole query: " . $query);

                    } else if ($row = mysqli_fetch_assoc($result)) {

                        if (array_key_exists("name", $row)) {

                            $rowFound                = true;
                            $responseJson["name"]    = $row["name"];
                            $responseJson["value"]   = $row["value"];
                            $responseJson["enabled"] = intval($row["enabled"]);
                        }   //  End if (array_key_exists("name", $row))

                        // Free result
                        mysqli_free_result($result);
                    }   //  End if (!$result)
                }   //  End if (!$db_selected)

                // Close connection
                mysqli_close($con);
            }   //  End if (!$con)
        } else {
            $errorCode    = 4;
            $errorMessage = "getSystemSetting: Not all parameters were found to process this input";
        }   //  End if ($bitmask === 1)

        $responseJson["errorCode"] = $errorCode;

        // Some error occured
        if ($errorMessage !== null) {
            $responseJson["error"] = $errorMessage;
        } elseif ($rowFound == false) {
            $responseJson["value"] = "[NOT FOUND]";
        }   //  End if ($errorMessage !== null)

        if (($dump === true) && ($query !== null)) {
            $responseJson["query"] = $query;
        }   //  End if (($dump === true) && ($query !== null))

        // Send result back
        header('Content-Type: application/json; charset=utf-8');
        print(utf8_encode(json_encode($responseJson)));
    }   //  End if ($postBody !== "")
}   //  End if (($_SERVER["REQUEST_METHOD"] === "POST") &&   

ob_end_flush();
?>
