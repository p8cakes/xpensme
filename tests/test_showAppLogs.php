<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: test_showAppLogs.php - test web service that gets last 3 entries from appLogs table
//
// Functions:
//    None
//
// Query Parameters:
//    s: Magic key that should be present in query-string for this test to succeed
//
// Custom Headers:
//    None
//
// Session Variables:
//    None
//
// Stored Procedures:
//    None
//
// JavaScript functions:
//    None
//
// Revisions:
//    1. Sundar Krishnamurthy          sundar@passion8cakes.com       09/25/2020      Initial file created.

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

$count = 3;      // Default value - fetch 3 records

// Break out of test if key not present in incoming request
if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$")) {     // $$ TEST_QUERY_KEY $$
    exit();
}   //  End if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$"))      // $$ TEST_QUERY_KEY $$

// See if count has been specified - c parameter
if (isset($_GET["c"])) {
    $count = intval($_GET["c"]);

    // Limit number of retrieved records to 50
    if ($count > 50) {
        $count = 50;
    }   //  End if ($count > 50)
}   //  End if (isset($_GET["c"]))

// First off, check if the application is being used by someone not typing the actual server name in the header
if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier) {
    // Transfer user to same page, served over HTTPS and full-domain name
    header("Location: https://" . $global_siteCookieQualifier . $_SERVER["REQUEST_URI"]);
    exit();
}   //  End if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier)

// STEP 1 - Positive use-case
// ********* Call Web Service with number of rows required, see how many you get back (latest logs) ********** //
$ch               = curl_init();

$elements          = array();
$elements["count"] = $count;
$elements["dump"]  = true;

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/showAppLogs.php");

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'ApiKey: $$API_KEY$$',           // $$ API_KEY $$
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'));

curl_setopt($ch, CURLOPT_SSLVERSION, 6);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode(utf8_encode(json_encode($elements))));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

session_write_close();

$response = curl_exec($ch);

curl_close($ch);

$checkResponse = json_decode(utf8_decode($response), true);
$errorCode     = intval($checkResponse["errorCode"]);

if ($errorCode === 0) {
    $rows = $checkResponse["rows"];

    if ($rows == null) {
        echo("No logs found");
    } else {

        echo("<table border='1'><tbody><th>ID</th><th>log</th><th>Created</th></tr>");

        foreach ($rows as &$row) {
            echo("<tr><td>");
            echo($row["id"]);
            echo("</td><td>");
            echo($row["log"]);
            echo("</td><td>");
            echo($row["created"]);
            echo("</td></tr>");                        
        }   //  End foreach ($rows as &$row)

        echo("</tbody></table>");
    }   //  End if ($rows == null)
} else {
    echo("ErrorCode: " . $errorCode . "<br>");
    echo("Error: " . $checkResponse["error"]);
}

ob_end_flush();
?>
