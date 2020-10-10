<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: test_mailSender.php - test web service that verifies that mailSender script works properly
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
// ********* Call Web Service with valid minimum parameters to send email ********** //
$ch                  = curl_init();

$elements            = array();
$elements["to"]      = "sundar@passion8cakes.com";
$elements["subject"] = "Hello,World - Subject";
$elements["body"]    = "Hello,World - Body";

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/mailSender.php");

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
    $mailSent = boolval($checkResponse["mailSent"]);

    if ($mailSent === true) {
        echo("1. Pass. Mail was successfully sent with " . $checkResponse["mailSize"] . " bytes.<br>");
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
$elements["to"]         = "sundar@passion8cakes.com";
$elements["subject"]    = "Hello,World with Attachments - Subject";
$elements["body"]       = "Hello,World with Attachments - Body";
$elements["prefix"]     = "Foo Bar";
$elements["importance"] = true;

$attachmentOne          = array();
$attachmentOne["name"]  = "dot2.gif";
$attachmentOne["data"]  =
    "R0lGODlhEAAQAHAAACH5BAEAAPwALAAAAAAQABAAhwAAAAAAMwAAZgAAmQAAzAAA/wArAAArMwArZgA" .
    "rmQArzAAr/wBVAABVMwBVZgBVmQBVzABV/wCAAACAMwCAZgCAmQCAzACA/wCqAACqMwCqZgCqmQCqzAC" .
    "q/wDVAADVMwDVZgDVmQDVzADV/wD/AAD/MwD/ZgD/mQD/zAD//zMAADMAMzMAZjMAmTMAzDMA/zMrADMr" .
    "MzMrZjMrmTMrzDMr/zNVADNVMzNVZjNVmTNVzDNV/zOAADOAMzOAZjOAmTOAzDOA/zOqADOqMzOqZjOqm" .
    "TOqzDOq/zPVADPVMzPVZjPVmTPVzDPV/zP/ADP/MzP/ZjP/mTP/zDP//2YAAGYAM2YAZmYAmWYAzGYA/2" .
    "YrAGYrM2YrZmYrmWYrzGYr/2ZVAGZVM2ZVZmZVmWZVzGZV/2aAAGaAM2aAZmaAmWaAzGaA/2aqAGaqM2a" .
    "qZmaqmWaqzGaq/2bVAGbVM2bVZmbVmWbVzGbV/2b/AGb/M2b/Zmb/mWb/zGb//5kAAJkAM5kAZpkAmZkA" .
    "zJkA/5krAJkrM5krZpkrmZkrzJkr/5lVAJlVM5lVZplVmZlVzJlV/5mAAJmAM5mAZpmAmZmAzJmA/5mqA" .
    "JmqM5mqZpmqmZmqzJmq/5nVAJnVM5nVZpnVmZnVzJnV/5n/AJn/M5n/Zpn/mZn/zJn//8wAAMwAM8wAZs" .
    "wAmcwAzMwA/8wrAMwrM8wrZswrmcwrzMwr/8xVAMxVM8xVZsxVmcxVzMxV/8yAAMyAM8yAZsyAmcyAzMy" .
    "A/8yqAMyqM8yqZsyqmcyqzMyq/8zVAMzVM8zVZszVmczVzMzV/8z/AMz/M8z/Zsz/mcz/zMz///8AAP8A" .
    "M/8AZv8Amf8AzP8A//8rAP8rM/8rZv8rmf8rzP8r//9VAP9VM/9VZv9Vmf9VzP9V//+AAP+AM/+AZv+Am" .
    "f+AzP+A//+qAP+qM/+qZv+qmf+qzP+q///VAP/VM//VZv/Vmf/VzP/V////AP//M///Zv//mf//zP///w" .
    "AAAAAAAAAAAAAAAAieAO0JHEiwoEB49hBOkzYtFcOEEAdOczXtVTZsFlFNIwhv4rRsqLKlyiYt2ytpECm" .
    "aLPkKW0WXrzbam4ZRmkWbIFdms2ez4kdjo4BmU6nSJrZsojox62SsWUWcJj+mAnqMUzNOJ2+WpOhS1LGg" .
    "H7N5tJdVpFhsIylSFPgxJFq0JC2aHJgtZ8yhdR0WHLlV6tGDEe1FHSoT4UDDBiPCCwgAOw==";

$attachmentTwo           = array();
$attachmentTwo["name"]   = "helloworld.txt";
$attachmentTwo["data"]   = "aGVsbG8sd29ybGQ=";

$elements["attachments"] = array($attachmentOne, $attachmentTwo);

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/mailSender.php");

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
    $mailSent = boolval($checkResponse["mailSent"]);

    if ($mailSent === true) {
        echo("2. Pass. Mail was successfully sent with " . $checkResponse["mailSize"] . " bytes.<br>");
    } else {
        echo("2. Fail. Mail was unable to be sent: " . $response . "<br>");
    }   //  End if ($mailSent === true)
} else {
    echo("2. Fail. Received error: " . $checkResponse["error"] . "<br>");
}   //  End if ($errorCode === 0)


// STEP 2 - Negative use-case
// ********* Call Web Service with not all parameters furnished ********** //
$ch                     = curl_init();

$elements               = array();
$elements["to"]         = "sundar@passion8cakes.com";
$elements["subject"]    = "Hello,World that should not be sent - Subject";

curl_setopt($ch, CURLOPT_URL, $global_siteUrl . "services/mailSender.php");

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
    echo("3. Pass. Mail was stopped with error message " . $checkResponse["error"] . "<br>");
} else {
    echo("3. Fail. Mail was sent: " . $response . "<br>");
}   //  End if ($errorCode === 4)

ob_end_flush();
?>
