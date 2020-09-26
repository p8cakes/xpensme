<?php
// Module Name:  ActiveDirectoryDB Web Service
// Project:      Web Service to talk to the Active Directory DB on the MySQL instance
//
// functions.php - define global variables and functions for this website
//
// Functions:
// 1. postedFromSame - Find out if the page referer was same as the form calling post
// 2. getCurrentPageUrl - Get the current page URL
// 3. createMyGuid - Create a random GUID
// 4. verifyEmails - Verify incoming emails
//
// Revisions:
//    1. Sundar Krishnamurthy - sundar@passion8cakes.com               09/26/2020      Coding and commenting started


// Define variables we use thro'out the web app
$global_dbServer = "$$DATABASE_SERVER$$";                       // $$ DATABASE_SERVER $$
$global_dbName = "$$DATABASE_NAME$$";                           // $$ DATABASE_NAME $$
$global_dbUsername = "$$DB_USERNAME$$";                         // $$ DB_USERNAME $$
$global_dbPassword = "$$DB_PASSWORD$$";                         // $$ DB_PASSWORD $$

$global_siteUrl = "$$FULL_URL$$";                               // $$ FULL_URL $$
$global_siteCookieQualifier = "$$SITE_COOKIE_QUALIFIER$$";      // $$ SITE_COOKIE_QUALIFIER $$
$global_useDomain = false;

// Function 1 - Check if the form has been posted from the same page
function postedFromSame($url) {

    $useUrl = "";

    $questionMarkPosition = strpos($url, "?");
    if ($questionMarkPosition === false) {
        $useUrl = $url;
    } else {
        $useUrl = substr($url, 0, $questionMarkPosition);
    }   //  End if ($questionMarkPosition === false)

    $sameForm = false;
    $prefixes = array("", "www.");
    $ports = array("", ':'.$_SERVER["SERVER_PORT"]);

    foreach ($prefixes as $prefix) {
        foreach ($ports as $port) {
            $pageUrl = "http";

            if ((array_key_exists("HTTPS", $_SERVER)) && ($_SERVER["HTTPS"] === "on")) {
                $pageUrl .= "s";
            }   //  End if ((array_key_exists("HTTPS", $_SERVER)) && ($_SERVER["HTTPS"] === "on"))

            $pageUrl .= "://";
            $pageUrl .= $prefix.$_SERVER["SERVER_NAME"].$port.$_SERVER["REQUEST_URI"];

            if ($pageUrl === $useUrl) {
                $sameForm = true;
                break;
            }   //  End if ($pageUrl === $useUrl)
        }   //  End foreach ($ports as $port)
    }   //  End foreach ($prefixes as $prefix)

    if (($sameForm === false) && ($url === "https://www.voilane.com/")) {
        $sameForm = true;
    }   //  End if (($sameForm === false) && ($url === "https://www.voilane.com/"))

    return $sameForm;
}   //  End function postedFromSame($url)


// Function 2: Get the current page URL
function getCurrentPageUrl() {

    global $global_useDomain;

    $pageUrl = "http";
    $wwwPrefix = "";

    if ($global_useDomain) {
        $wwwPrefix = "www.";
    }   //  End if ($global_useDomain)

    if ((array_key_exists("HTTPS", $_SERVER)) && ($_SERVER["HTTPS"] === "on")) {
        $pageUrl .= "s";
    }   //  End if ((array_key_exists("HTTPS", $_SERVER)) && ($_SERVER["HTTPS"] === "on"))

    $pageUrl .= "://";
    if (($_SERVER["SERVER_PORT"] === "80") ||
        ((array_key_exists("HTTPS", $_SERVER)) && ($_SERVER["HTTPS"] === "on") && ($_SERVER["SERVER_PORT"] == 443))) {
        $pageUrl .= $wwwPrefix.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    } else {
        $pageUrl .= $wwwPrefix.$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    }   //  End if (($SERVER["SERVER_PORT"] === "80") ||

    return $pageUrl;
}   //  End function getCurrentPageUrl()


// Function 3 - Create a random GUID
function createMyGuid() {

    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }   //  End if (function_exists('com_create_guid') === true)

    return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(16384, 20479),
                       mt_rand(32768, 49151),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535));
}   //  End function createMyGuid()

// Function 4 - Verify emails
function verifyEmails($inputEmails, $maxLength) {

    $emails      = explode(",", $inputEmails);
    $emailLength = 0;

    $outputEmail = "";

    foreach ($emails as &$email) {

        $processEmail = trim($email);

        if (filter_var($processEmail, FILTER_VALIDATE_EMAIL)) {

            if (($emailLength + strlen($processEmail) + 1) < $maxLength) {

                if ($outputEmail !== "") {
                    $outputEmail .= ",";
                    $emailLength += 1;
                }   //  End if ($outputEmail !== "")

                // Don't let any email addresses be more than 128 characters
                if (strlen($processEmail) < 129) {
                    $outputEmail .= $processEmail;
                    $emailLength += strlen($processEmail);
                }   //  End if (strlen($processEmail) < 129)
            } else {
                // Stop processing more email addresses
                break;   
            }   //  End if (($emailLength + strlen($processEmail) + 1) < $maxLength)
        }   //  End if (filter_var($email, FILTER_VALIDATE_EMAIL))
    }   //  End foreach ($emails as &$email)

    return $outputEmail;
}
?>
