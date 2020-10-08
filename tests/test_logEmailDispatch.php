<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: test_logEmailDispatch.php - test web service that tests that mail dispatch logging in DB gets tested
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
//    1. Sundar Krishnamurthy          sundar@passion8cakes.com       10/08/2020      Initial file created.

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

// Break out of test if key not present in incoming request
if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$")) {     // $$ TEST_QUERY_KEY $$
    exit();
}   //  End if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$"))      // $$ TEST_QUERY_KEY $$

// STEP 1 - Positive use-case
// ********* Call Web Service to check valid writes are being performed *******************
$ch                    = curl_init();
$elements              = array();
$elements["apiKeyId"]  = 314;
$elements["mailId"]    = 102;
$elements["sender"]    = "john.doe@email.com";
$elements["recipient"] = "jane.doe@email.com";
$elements["subject"]   = "Foo Bar One";
$elements["size"]      = 1024;

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/logEmailDispatch.php");

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
    $logId = intval($checkResponse["logId"]);

    if ($logId > 0) {
        echo("1. Pass: Valid LogId value found.<br>");
    } else {
        echo("1. Fail: Invalid input received: " . $response . ".<br>");
    }   //  End if ($logId > 0)
} else {
    echo("ErrorCode: " . $errorCode . "<br>");
    echo("Error: " . $checkResponse["error"]);
}   //  End if ($errorCode === 0)


// STEP 2 - Positive use-case
// ********* Call Web Service to check valid writes are being performed, avoid optional mailId parameter *******************
$ch                    = curl_init();
$elements              = array();
$elements["apiKeyId"]  = 315;
$elements["sender"]    = "jane.doe@email.com";
$elements["recipient"] = "john.doe@email.com";
$elements["subject"]   = "Foo Bar Two";
$elements["size"]      = 1025;

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/logEmailDispatch.php");

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
    $logId = intval($checkResponse["logId"]);

    if ($logId > 0) {
        echo("2. Pass: Valid LogId value found.<br>");
    } else {
        echo("2. Fail: Invalid input received: " . $response . ".<br>");
    }   //  End if ($logId > 0)
} else {
    echo("ErrorCode: " . $errorCode . "<br>");
    echo("Error: " . $checkResponse["error"]);
}   //  End if ($errorCode === 0)


// STEP 3 - Negative use case
// ********* Call Web Service to with a missing parameter, verify that you get back an error message *******************
$ch                    = curl_init();
$elements              = array();
$elements["apiKeyId"]  = 316;
$elements["subject"]   = "Foo Bar Three";
$elements["size"]      = 1026;

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/logEmailDispatch.php");

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

if ($errorCode === 4) {
    echo("3. Pass: Valid errorCode received.<br>");
} else {
    echo("3. Fail: Invalid input received when expecting errorCode: " . $response . ".<br>");
}   //  End if ($errorCode === 4)

ob_end_flush();
?>
