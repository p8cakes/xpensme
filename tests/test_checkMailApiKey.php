<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: test_addEmail.php - test web service to save email to mails table
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
//    1. Sundar Krishnamurthy          sundar@passion8cakes.com       10/10/2020      Initial file created.

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

// Break out of test if key not present in incoming request
if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$")) {     // $$ TEST_QUERY_KEY $$
    exit();
}   //  End if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$"))      // $$ TEST_QUERY_KEY $$

// First off, check if the application is being used by someone not typing the actual server name in the header
if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier) {
    // Transfer user to same page, served over HTTPS and full-domain name
    header("Location: https://" . $global_siteCookieQualifier . $_SERVER["REQUEST_URI"]);
    exit();
}   //  End if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier)

// STEP 1 - Positive use-case
// ********* Call Web Service with valid minimum parameters to save data to mails table ********** //
$ch                  = curl_init();

$elements            = array();
$elements["to"]      = "sundar_k@hotmail.com";
$elements["subject"] = "Hello,World - Subject";
$elements["body"]    = "Hello,World - Body";
$elements["ready"]   = true;

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/addEmail.php");

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
    $mailId = intval($checkResponse["mailId"]);

    if ($mailId > 0) {
        echo("1. Pass. Mail was successfully saved with mailId: " . $mailId . "<br>");
    } else {
        echo("1. Fail. Mail was unable to be sent: " . $response . "<br>");
    }   //  End if ($mailSent === true)
} else {
    echo("1. Fail. Received error: " . $checkResponse["error"] . "<br>");
}   //  End if ($errorCode === 0)

// STEP 2 - Positive use-case
// ********* Call Web Service with all parameters furnished ********** //
$ch                     = curl_init();

$elements               = array();
$elements["to"]         = "sundar_k@hotmail.com";
$elements["subject"]    = "Hello,World Two - Subject";
$elements["body"]       = "Hello,World Two - Body";
$elements["cc"]         = "sundarnut@gmail.com";
$elements["bcc"]        = "sundar@passion8cakes.com";
$elements["prefix"]     = "Foo Bar";
$elements["timestamp"]  = "2020-11-01 15:35:59";
$elements["importance"] = true;
$elements["ready"]      = true;

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/addEmail.php");

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
    $mailId = intval($checkResponse["mailId"]);

    if ($mailId > 0) {
        echo("2. Pass. Mail was successfully saved with mailId: " . $mailId . "<br>");
    } else {
        echo("2. Fail. Mail was unable to be sent: " . $response . "<br>");
    }   //  End if ($mailSent === true)
} else {
    echo("2. Fail. Received error: " . $checkResponse["error"] . "<br>");
}   //  End if ($errorCode === 0)

ob_end_flush();
?>
