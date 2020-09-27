<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: test_getSystemSetting.php - test web service that fetches data for system-setting name
//
// Functions:
//    None
//
// Query Parameters:
//    s: Magic key that should be present in query-string for this test to succeed
//    n: Name of parameter whose value is sought, default: logAllCalls.
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
//    1. Sundar Krishnamurthy          sundar@passion8cakes.com       09/26/2020      Initial file created.

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

$name = "logAllCalls";

// Break out of test if key not present in incoming request
if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$")) {     // $$ TEST_QUERY_KEY $$
    exit();
}   //  End if ((!isset($_GET["s"])) || ($_GET["s"] !== "$$TEST_QUERY_KEY$$"))      // $$ TEST_QUERY_KEY $$

// Read name from n parameter
if (isset($_GET["n"])) {
    $name = $_GET["n"];
}   //  End if ((isset($_GET["n"]))

// First off, check if the application is being used by someone not typing the actual server name in the header
if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier) {
    // Transfer user to same page, served over HTTPS and full-domain name
    header("Location: https://" . $global_siteCookieQualifier . $_SERVER["REQUEST_URI"]);
    exit();
}   //  End if (strtolower($_SERVER["HTTP_HOST"]) !== $global_siteCookieQualifier)

// STEP 1 - Positive use-case
// ********* Call Web Service with provided name, use "logAllCalls" if none provided ********** //
$ch               = curl_init();

$elements         = array();
$elements["name"] = $name;
$elements["dump"] = true;

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/getSystemSetting.php");

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
    $value = $checkResponse["value"];
    echo("$name: $value");
} else {
    echo("ErrorCode: " . $errorCode . "<br>");
    echo("Error: " . $checkResponse["error"]);
}

ob_end_flush();
?>
