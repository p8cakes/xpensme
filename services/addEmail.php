<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: addEmail.php - add a new email to the DB - mails table
//
// Input JSON:
// 
//   {
//    "from":"Mr. John Smith",
//    "fromEmail":"johnsmith@gmail.com",
//    "to":"jane.doe@gmail.com",
//    "cc":"jill.doe@gmail.com",
//    "bcc":"jack.doe@gmail.com",
//    "replyTo":"john.doe@gmail.com",
//    "subject":"Test Subject",
//    "prefix":"Test",
//    "body":"Test Body",
//    "ready":true,
//    "hasAttachments":true,
//    "importance":true,
//    "timestamp":"2020-10-10 17:10:00"
//   }
//
// Output JSON:
//   {"errorCode":0,
//    "mailId":133}
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
//   addEmail - insert email into the DB
//
// JavaScript functions:
//   None
//
// Revisions:
//     1. Sundar Krishnamurthy          sundar@passion8cakes.com       10/10/2020      Initial file created.
//     2. Sundar Krishnamurthy          sundar@passion8cakes.com       10/12/2020      Updates and blank checks.

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

        $sender         = null;   // 1
        $senderEmail    = null;   // 2
        $recipients     = null;   // 3
        $ccRecipients   = null;   // 4
        $bccRecipients  = null;   // 5
        $replyTo        = null;   // 6
        $subject        = null;   // 7
        $subjectPrefix  = null;   // 8
        $body           = null;   // 9
        $ready          = 0;      // 10
        $hasAttachments = 0;      // 11
        $importance     = 0;      // 12
        $timestamp      = null;   // 13

        $bitmask        = 0;
        $errorCode      = 0;
        $errorMessage   = null;
        $query          = null;
        $dump           = false;

        $responseJson   = array();

        $request        = json_decode($postBody, true);

        if (array_key_exists("dump", $request)) {
            $dump = boolval($request["dump"]);
        }   //  End if (array_key_exists("dump", $request))

        if (array_key_exists("to", $request)) {   // 3
            $recipients = $request["to"];

            if (($recipients != null) && ($recipients != "")) {
                $bitmask = 1;
            }   //  End if (($recipients != null) && ($recipients != ""))
        }   //  End if (array_key_exists("to", $request))

        if (array_key_exists("subject", $request)) {   // 7
            $subject = $request["subject"];

            if (($subject != null) && ($subject != "")) {
                $bitmask |= 2;
            }   //  End if (($subject != null) && ($subject != ""))
        }   //  End if (array_key_exists("subject", $request))

        if (array_key_exists("body", $request)) {   // 9
            $body = $request["body"];

            if (($body != null) && ($body != "")) {
                $bitmask |= 4;
            }   //  End if (($body != null) && ($body != ""))
        }   //  End if (array_key_exists("body", $request))

        // We found all the mandatory fields
        if ($bitmask === 7) {

            if (array_key_exists("from", $request)) {   // 1
                $sender = $request["from"];
            }   //  End if (array_key_exists("from", $request))

            if (array_key_exists("fromEmail", $request)) {   // 2
                $senderEmail = $request["fromEmail"];
            }   //  End if (array_key_exists("fromEmail", $request))

            if (array_key_exists("cc", $request)) {   // 4
                $ccRecipients = $request["cc"];
            }   //  End if (array_key_exists("cc", $request))

            if (array_key_exists("bcc", $request)) {   // 5
                $bccRecipients = $request["bcc"];
            }   //  End if (array_key_exists("bcc", $request))

            if (array_key_exists("replyTo", $request)) {   // 6
                $replyTo = $request["replyTo"];
            }   //  End if (array_key_exists("replyTo", $request))

            if (array_key_exists("prefix", $request)) {   // 8
                $subjectPrefix = $request["prefix"];
            }   //  End if (array_key_exists("prefix", $request))

            if (array_key_exists("ready", $request)) {   //   10
                $ready = boolval($request["ready"]);

                if ($ready === true) {
                    $ready = 1;
                } else {
                    $ready = 0;
                }   //  End if ($ready === true)
            }   //  End if (array_key_exists("ready", $request))

            if (array_key_exists("hasAttachments", $request)) {   // 11
                $hasAttachments = boolval($request["hasAttachments"]);

                if ($hasAttachments === true) {
                    $hasAttachments = 1;
                } else {
                    $hasAttachments = 0;
                }   //  End if ($hasAttachments === true)
            }   //  End if (array_key_exists("hasAttachments", $request))

            if (array_key_exists("importance", $request)) {   // 12
                $importance = boolval($request["importance"]);

                if ($importance === true) {
                    $importance = 1;
                } else {
                    $importance = 0;
                }   //  End if ($importance === true)
            }   //  End if (array_key_exists("importance", $request))

            if (array_key_exists("timestamp", $request)) {   // 13
                $timestamp = $request["timestamp"];
            }   //  End if (array_key_exists("timestamp", $request))

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

                    $useSender = "null";   // 1

                    if (($sender != null) && ($sender != "")) {
                        $useSender = mysqli_real_escape_string($con, $sender);

                        if (strlen($useSender) > 64) {
                            $useSender = substr($useSender, 0, 64);
                        }   //  End if (strlen($useSender) > 64)

                        $useSender = "'" . $useSender . "'";
                    }   //  End (($sender != null) && ($sender != ""))

                    $useSenderEmail = "null";   // 2

                    if (($senderEmail != null) && ($senderEmail != "")) {
                        $useSenderEmail = mysqli_real_escape_string($con, $senderEmail);

                        if (strlen($useSenderEmail) > 128) {
                            $useSenderEmail = substr($useSenderEmail, 0, 128);
                        }   //  End if (strlen($useSenderEmail) > 128)

                        $useSenderEmail = "'" . $useSenderEmail . "'";
                    }   //  End if (($senderEmail != null) && ($senderEmail != ""))

                    $useRecipients = mysqli_real_escape_string($con, $recipients);   // 3

                    if (strlen($useRecipients) > 4096) {
                        $useRecipients = substr($useRecipients, 0, 4096);
                    }   //  End if (strlen($useRecipients) > 4096)

                    $useCcRecipients = "null";   // 4

                    if (($ccRecipients != null) && ($useCcRecipients != null)) {
                        $useCcRecipients = mysqli_real_escape_string($con, $ccRecipients);

                        if (strlen($useCcRecipients) > 4096) {
                            $useCcRecipients = substr($useCcRecipients, 0, 4096);
                        }   //  End if (strlen($useCcRecipients) > 4096)

                        $useCcRecipients = "'" . $useCcRecipients . "'";
                    }   //  End if (($useCcRecipients != null) && ($useCcRecipients != ""))

                    $useBccRecipients = "null";   // 5

                    if (($bccRecipients != null) && ($bccRecipients != "")) {
                        $useBccRecipients = mysqli_real_escape_string($con, $bccRecipients);

                        if (strlen($useBccRecipients) > 4096) {
                            $useBccRecipients = substr($useBccRecipients, 0, 4096);
                        }   //  End if (strlen($useBccRecipients) > 4096)

                        $useBccRecipients = "'" . $useBccRecipients . "'";
                    }   //  End if (($bccRecipients != null) && ($bccRecipients != ""))

                    $useReplyTo = "null";   // 6

                    if (($replyTo != null) && ($replyTo != "")) {
                        $useReplyTo = mysqli_real_escape_string($con, $replyTo);

                        if (strlen($useReplyTo) > 128) {
                            $useReplyTo = substr($useReplyTo, 0, 128);
                        }   //  End if (strlen($useReplyTo) > 128)

                        $useReplyTo = "'" . $useReplyTo . "'";
                    }   //  End if (($useReplyTo != null) && ($replyTo != ""))

                    $useSubject = mysqli_real_escape_string($con, $subject);   // 7

                    if (strlen($useSubject) > 236) {
                        $useSubject = substr($useSubject, 0, 236);
                    }   //  End if (strlen($useSubject) > 236)

                    $useSubjectPrefix = "null";   // 8

                    if (($subjectPrefix != null) && ($subjectPrefix != "")) {
                        $useSubjectPrefix = mysqli_real_escape_string($con, $subjectPrefix);

                        if (strlen($useSubjectPrefix) > 64) {
                            $useSubjectPrefix = substr($useSubjectPrefix, 0, 64);
                        }   //  End if (strlen($useSubjectPrefix) > 64)

                        $useSubjectPrefix = "'" . $useSubjectPrefix . "'";
                    }   //  End (($subjectPrefix != null) && ($subjectPrefix != ""))

                    $useBody = mysqli_real_escape_string($con, $body);   // 9

                    $useTimestamp = "null";   // 13

                    if ($timestamp != null) {
                        $useTimestamp = "'" . mysqli_real_escape_string($con, $timestamp) . "'";
                    }   //  End if ($timestamp != null)

                    // This is the query we will run to insert new email into the mails table
                    $query = "call addEmail($useSender, $useSenderEmail, '$useRecipients', " .
                                 "$useCcRecipients, $useBccRecipients, $useReplyTo, '$useSubject', " .
                                 "$useSubjectPrefix, '$useBody', $ready, $hasAttachments, " .
                                 "$importance, $useTimestamp);";

                    // Result of query
                    $result = mysqli_query($con, $query);

                    // Unable to fetch result, display error message
                    if (!$result) {
                        $errorCode     = 3;
                        $errorMessage  = "Invalid query: " . mysqli_error($con) . "<br/>";
                        $errorMessage .= ("Whole query: " . $query);
                    } else if ($row = mysqli_fetch_assoc($result)) {

                        $responseJson["mailId"] = intval($row["mailId"]);

                        // Free result
                        mysqli_free_result($result);
                    }   //  End if (!$result)
                }   //  End if (!$db_selected)

                // Close connection
                mysqli_close($con);
            }   //  End if (!$con)
        } else {
            $errorCode    = 4;
            $errorMessage = "addEmail: Not all valid parameters were found to process this input";
        }   //  End if ($bitmask === 7)

        $responseJson["errorCode"] = $errorCode;

        if ($errorMessage != null) {
            $responseJson["error"] = $errorMessage;
        }   //  End if ($errorMessage != null)

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
