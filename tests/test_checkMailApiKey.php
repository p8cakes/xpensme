<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: test_checkMailApiKey.php - test web service that checks furnished API key whether valid for dispatching email
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
//    1. Sundar Krishnamurthy          sundar@passion8cakes.com       10/06/2020      Initial file created.

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
// ********* Call Web Service to check furnished mailApiKey as being valid *******************
$ch                     = curl_init();
$elements               = array();
$elements["mailApiKey"] = "$$MAIL_API_KEY$$";                     // $$ MAIL_API_KEY $$

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/checkMailApiKey.php");

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
    $dataFound = boolval($checkResponse["dataFound"]);

    if ($dataFound === true) {
        $active = intval($checkResponse["active"]);

        if ($active === 1) {
            echo("1. Pass: Valid Mail API key found.<br>");
        } else {
            echo("1. Fail: Inactive Mail API key found for furnished data.<br>");
        }   //  End if ($active === 1)
    } else {
        echo("1. Fail: No data found for furnished input.<br>");
    }   //  End if ($dataFound === true)
} else {
    echo("ErrorCode: " . $errorCode . "<br>");
    echo("Error: " . $checkResponse["error"]);
}   //  End if ($errorCode === 0)

// STEP 2 - Negative use-case
// ********* Call Web Service to check furnished mailApiKey as being invalid *******************
$ch                     = curl_init();
$elements               = array();
$elements["mailApiKey"] = "abcdefghijklmnopqrstuvwxyz012345";

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/checkMailApiKey.php");

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
    $dataFound = boolval($checkResponse["dataFound"]);

    if ($dataFound === false) {
        echo("2. Pass: Invalid Mail API key found, and nothing retrieved.<br>");
    } else {
        echo("2. Fail: Valid data retrieved for test: " . $dataFound . "<br>");
    }   //  End if ($dataFound === false)
} else {
    echo("ErrorCode: " . $errorCode . "<br>");
    echo("Error: " . $checkResponse["error"]);
}

// STEP 3 - Negative use-case
// ********* Call Web Service to check furnished mailApiKey as being invalid with incorrect length *******************
$ch                     = curl_init();
$elements               = array();
$elements["mailApiKey"] = "abcdefghijklmnopqrstuvwxyz";

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/checkMailApiKey.php");

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

if ($response === "") {
    echo("3. Pass: Blank response retrieved for invalid length API key.<br>");
} else {
    echo("3. Fail: Non-empty response retrieved for invalid length API key: " . $response . "<br>");
}   //  End if ($response === "")

// STEP 4 - Negative use-case
// ********* Call Web Service to with no mailApiKey parameter *******************
$ch                     = curl_init();
$elements               = array();

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/checkMailApiKey.php");

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
    echo("4. Pass: Absent mailApiKey parameter has resulted in proper error message.<br>");
} else {
    echo("4. Fail: Absent mailApiKey parameter resulted in this response: " . $errorCode . "<br>");
}   //  End if ($errorCode === 4)

ob_end_flush();
?>
