<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: checkMailApiKey.php - check if the furnished API key is valid in the DB
//
// Input JSON:
//   {"mailApiKey":"a02cb1f0377a3164c819ed8979a10a60"}
//
// Output JSON:
//   {"errorCode":0,
//    "dataFound":true;
//    "apiKeyId":101,
//    "active":1,
//    "email":"janedoe@email.com",
//    "name":"Jane Doe"}
//
//   {"errorCode":0,
//    "dataFound":false}
//
//   {"errorCode":1,
//    "error":"Long exception message"}
//
// Functions:
//    None
//
// Query Parameters:
//    None
//
// Custom Headers:
//    ApiKey: Must contain magic value for this service to be employed
//
// Session Variables:
//    None
//
// Stored Procedures:
//   checkMailApiKey - check if the furnished Mail API key is active in the DB
//
// JavaScript functions:
//   None
//
// Revisions:
//     1. Sundar Krishnamurthy          sundar@passion8cakes.com       10/06/2020      Initial file created.

ini_set('session.cookie_httponly', TRUE);           // Mitigate XSS
ini_set('session.session.use_only_cookies', TRUE);  // No session fixation
ini_set('session.cookie_lifetime', FALSE);          // Avoid XSS, CSRF, Clickjacking
ini_set('session.cookie_secure', TRUE);             // Never let this be propagated via HTTP/80

// Include functions.php that contains all our functions
require_once("../functions.php");

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

// Verify that we have a valid API key that is being used to post to this service, and request emanates from same server.
if (($_SERVER["REQUEST_METHOD"] === "POST") &&
    (isset($_SERVER["HTTP_APIKEY"])) &&
    ($_SERVER["HTTP_APIKEY"] === "$$API_KEY$$") &&                     // $$ API_KEY $$
    ($_SERVER["SERVER_ADDR"] === $_SERVER["REMOTE_ADDR"])) {

    $postBody = utf8_decode(urldecode(file_get_contents("php://input")));

    // We found a valid body to process
    if ($postBody !== "") {
        $dataFound    = false;

        $bitmask      = 0;
        $errorCode    = 0;
        $errorMessage = null;
        $query        = null;
        $dump         = false;

        $responseJson = array();

        $request      = json_decode($postBody, true);

        if (array_key_exists("dump", $request)) {
            $dump = boolval($request["dump"]);
        }   //  End if (in_array("dump", $postBody))

        if (array_key_exists("mailApiKey", $request)) {
            $mailApiKey   = $request["mailApiKey"];

            if (strlen($mailApiKey) === 32) {
                $bitmask = 1;
            }   //  End if (strlen($mailApiKey) === 32)
        }   //  End if (in_array("mailApiKey", $postBody))

        // We have valid data coming in
        if ($bitmask === 1) {

            // Update session key and value in DB for sessionId
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

                    $useMailApiKey = mysqli_real_escape_string($con, $mailApiKey);

                    if (strlen($useMailApiKey) > 32) {
                        $useMailApiKey = substr($useMailApiKey, 0, 32);
                    }   //  End if (strlen($useMailApiKey) > 32)

                    // This is the query we will run to check the validity of mailApiKey in the DB
                    $query = "call checkMailApiKey('$useMailApiKey');";

                    // Result of query
                    $result = mysqli_query($con, $query);

                    // Unable to fetch result, display error message
                    if (!$result) {
                        $errorCode     = 3;
                        $errorMessage  = "Invalid query: " . mysqli_error($con) . "<br/>";
                        $errorMessage .= ("Whole query: " . $query);
                    } else if ($row = mysqli_fetch_assoc($result)) {

                        if ($row["active"] != null) {
                            $dataFound                = true;
                            $responseJson["active"]   = intval($row["active"]);
                            $responseJson["apiKeyId"] = intval($row["apiKeyId"]);
                            $responseJson["email"]    = $row["email"];
                            $responseJson["name"]     = $row["name"];
                        }   //  End if ($row["active"] != null)

                        // Free result
                        mysqli_free_result($result);
                    }   //  End if (!$result)
                }   //  End if (!$db_selected)

                // Close connection
                mysqli_close($con);
            }   //  End if (!$con)

            $responseJson["errorCode"] = $errorCode;
            $responseJson["dataFound"] = $dataFound;

            if ($errorMessage != null) {
                $responseJson["error"] = $errorMessage;
            }   //  End if ($errorMessage != null)

            if (($dump === true) && ($query !== null)) {
                $responseJson["query"] = $query;
            }   //  End if (($dump === true) && ($query !== null))

            // Send result back
            header('Content-Type: application/json; charset=utf-8');
            print(utf8_encode(json_encode($responseJson)));
        } else {
            $errorCode    = 4;
            $errorMessage = "checkMailApiKey: Not all parameters were found to process this input";
        }   //  End if ($bitmask === 1)
    }   //  End if ($postBody !== "")
}   //  End if (($_SERVER["REQUEST_METHOD"] === "POST") &&

ob_end_flush();
?>
