<?php
// Module Name:  xm website
// Project:      xm website to track everyday expenses
//
// File: mailSender.php - send mail
//
// Input JSON:
// {"to":"john.doe@abc.com",
//    "subject":"Get Organized reminder",
//    "body":"I tried telling you many times, please <b>get organized</b>."}
//
// Input JSON:
// {
//  "to": "john.doe@abc.com",
//  "from": "Jane Doe",
//  "fromEmail": "jane@someemail.com",
//  "replyTo":"john@email.com"
//  "subject": "Get Organized Reminder",
//  "body": "I tried telling you many times, please get organized<\/b>.",
//  "cc": "jane1@foo.com,jane2@bar.com",
//  "bcc": "jane@somesite.com",
//  "importance": true,
//  "attachments": [{
//      "name": "dot.gif",
//      "data": "Long base 64 string"
//   }, {
//      "name": "helloworld.txt",
//      "data": "aGVsbG8sd29ybGQ="
//   }]
// }
//
//  from:       Sender name
//  fromEmail:  Sender email
//  replyTo:    Reply-to email
//  to:         Email address of recipient
//  subject:    Subject of email
//  body:       Body of email (can include HTML tags)
//  cc:         Comma separated email addresses for cc targets
//  bcc:        Comma separated email addresses for bcc targets
//  importance: 1 (if needed)
//  attachment: One or more files to be dispatched, each would have:
//      name  : Actual name of file
//      data  : Long base 64 string
//
//
// JSON Response:
//    {"errorCode":0,
//     "mailSize":7384,
//     "mailSent":true}
//
//    {"errorCode":4,
//     "error":"sendmail daemon: Not all input was supplied to process this input: 5"}
//
// Web Services:
//     None
//
// Functions:
//     None
//
// Query Parameters:
//     None
//
// Custom Headers:
//     ApiKey: Must contain magic value for this service to be employed
//
// Session Variables:
//     None
//
// Stored Procedures:
//     None
//
// JavaScript functions:
//     None
//
// Comments:
//     No dump flag as there is no DB interaction.
//
// Revisions:
//     1. Sundar Krishnamurthy          sundar_k@hotmail.com       10/10/2017      Initial file created.

ini_set('session.cookie_httponly', TRUE);           // Mitigate XSS
ini_set('session.session.use_only_cookies', TRUE);  // No session fixation
ini_set('session.cookie_lifetime', FALSE);          // Avoid XSS, CSRF, Clickjacking
ini_set('session.cookie_secure', TRUE);             // Never let this be propagated via HTTP/80

require_once("../phpmailer/class.phpmailer.php");
require_once("../phpmailer/class.smtp.php");

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

    $apiKey   = $_SERVER["HTTP_APIKEY"];

    // We found a valid body to process
    if ($postBody !== "") {

        $mailRequest    = json_decode($postBody, true);

        $active         = false;
        $fromEmail      = $global_email_username;
        $replyTo        = $global_email_username;
        $fromName       = "Voilane Mailer (do not reply)";                           // $$ FROM_NAME $$;
        $ccAddress      = null;
        $bccAddress     = null;
        $attachments    = null;
        $toAddress      = null;
        $toAddressValue = null;
        $subject        = null;
        $body           = null;

        $bitmask        = 0;
        $errorCode      = 0;
        $errorMessage   = null;
        $mailSent       = false;

        // Start processing the JSON string for data
        // To: field for the email is mandatory
        if (array_key_exists("to", $mailRequest)) {
            $toAddressValue = $mailRequest["to"];
            $bitmask        = 1;
            $toAddress      = explode(",", $toAddressValue);
        }   //  End if (array_key_exists("to", $mailRequest))

        // Subject: field for the email is mandatory
        if (array_key_exists("subject", $mailRequest)) {
            $subject  = $mailRequest["subject"];
            $bitmask |= 2;
        }   //  if (array_key_exists("subject", $mailRequest))

        // Body: field for the email is mandatory
        if (array_key_exists("body", $mailRequest)) {
            $body     = $mailRequest["body"];
            $mailSize = strlen($body);
            $bitmask |= 4;
        }   //  End if (array_key_exists("body", $mailRequest))

        // Proceed only if you found all three mandatory parameters furnished
        if ($bitmask === 7) {

            // From field if we need to send the mail as someone else
            if (array_key_exists("from", $mailRequest)) {
                $fromName = $mailRequest["from"];
            }   //  End if (array_key_exists("from", $mailRequest)) {

            // FromEmail field if we need to send the mail as someone else
            if (array_key_exists("fromEmail", $mailRequest)) {
                $fromEmail = $replyTo = $mailRequest["fromEmail"];
            }   //  End if (array_key_exists("fromEmail", $mailRequest)) {

            // Reply-To field if we need to reply back email address
            if (array_key_exists("replyTo", $mailRequest)) {
                $replyTo = $mailRequest["replyTo"];
            }   //  End if (array_key_exists("replyTo", $mailRequest)) {

            // Cc: field - we would need a new query to process additional parameters
            if (array_key_exists("cc", $mailRequest)) {
                $ccAddressValue = $mailRequest["cc"];
                $ccAddress      = explode(",", $ccAddressValue);
            }   //  End if (array_key_exists("cc", $mailRequest))

            // Bcc: field - we would need a new query to process additional parameters
            if (array_key_exists("bcc", $mailRequest)) {
                $bccAddressValue = $mailRequest["bcc"];
                $bccAddress      = explode(",", $bccAddressValue);
            }   //  End if (array_key_exists("bcc", $mailRequest))

            // Prefix field - add this in square braces, before every subject
            if ((array_key_exists("prefix", $mailRequest)) && ($mailRequest["prefix"] != "")) {
                $subject = "[" . $mailRequest["prefix"] . "] " . $subject;
            }   //  End if ((array_key_exists("prefix", $mailRequest)) && ($mailRequest["prefix"] != ""))

            if (array_key_exists("replyTo", $mailRequest)) {
                $replyTo = $mailRequest["replyTo"];
            }   //  End if (array_key_exists("replyTo", $mailRequest))

            if (array_key_exists("attachments", $mailRequest)) {
                $attachments = $mailRequest["attachments"];
            }   //  End if (array_key_exists("attachments", $mailRequest))

            $mail             = new PHPMailer();
            $mail->SMTPDebug  = false;
            $mail->isSMTP();
            $mail->Host       = "$$EMAIL_SERVER$$";                        // $$ EMAIL_SERVER $$

            $mail->SMTPAuth   = true;
            $mail->Username   = $global_email_username;
            $mail->Password   = $global_email_password;

            $mail->CharSet    = "UTF-8";
            $mail->SMTPSecure = "tls";
            $mail->Port       = 25;

            // Set the values for mailer object, from to etc.
            $mail->From       = $fromEmail;
            $mail->FromName   = $fromName;
            $mail->AddReplyTo($replyTo);
            $mail->Subject    = $subject;
            $mail->Body       = $body;
            $mail->IsHTML(true);

            // Size of text/body we send via email
            $mailSize = strlen($body);

            foreach ($toAddress as &$toEmail) {
                $mail->AddAddress(trim($toEmail));
            }   //  End foreach ($toAddress as &$toEmail)

            if ($ccAddress != null) {
                foreach ($ccAddress as &$ccEmail) {
                    $mail->AddCC(trim($ccEmail));
                }   //  End foreach ($ccAddress as &$ccEmail)
            }   //  End foreach ($ccAddress as &$ccEmail)

            if ($bccAddress != null) {
                foreach ($bccAddress as &$bccEmail) {
                    $mail->AddBCC(trim($bccEmail));
                }   //  End foreach ($bccAddress as &$bccEmail)
            }   //  End foreach ($bccAddress as &$bccEmail)

            if (array_key_exists("importance", $mailRequest)) {
                $importance = boolval($mailRequest["importance"]);

                if (($importance === true) || ($importance === 1)) {
                    $mail->Priority = 1;
                }   //  End if (($importance === true)) || ($importance === 1))
            }   //  End if (array_key_exists("importance", $mailRequest))

            // We have attachments, add each one individually and dispatch this email
            if (($attachments != null) && (is_array($attachments))) {
                // Iterate and add each attachment
                foreach($attachments as &$attachment) {
                    // Verify that Filename and Data fields are furnished
                    if ((array_key_exists("name", $attachment)) &&
                        (array_key_exists("data", $attachment))) {

                        $filename = $attachment["name"];
                        $mimeType = "application/octet-stream";

                        // We found a period in the filename
                        if ((strrchr($filename, ".")) !== false) {
                            $extension = strtolower(substr(strrchr($filename, '.'), 1));

                            switch ($extension) {
                                case "3gp": $mimeType = "video/3gpp"; break;
                                case "7z": $mimeType  = "application/x-7z-compressed"; break;
                                case "apk": $mimeType = "application/vnd.android.package-archive"; break;
                                case "asm": case "s": $mimeType = "text/x-asm"; break;
                                case "avi": $mimeType = "video/x-ms-video"; break;
                                case "azw": $mimeType = "application/vnd.amazon.ebook"; break;
                                case "bmp": $mimeType = "image/bmp"; break;
                                case "c": $mimeType = "text/x-c"; break;
                                case "cpp": $mimeType = "text/plain"; break;
                                case "cab": $mimeType = "application/vnd.ms-cab-compressed"; break;
                                case "class": $mimeType = "application/java-vm"; break;
                                case "crl": $mimeType = "application/pkix-crl"; break;
                                case "css": $mimeType = "text/css"; break;
                                case "csv": $mimeType = "text/csv"; break;
                                case "curl": $mimeType = "text/vnd.curl"; break;
                                case "dmg": $mimeType = "application/x-apple-diskimage"; break;
                                case "doc": $mimeType = "application/msword"; break;
                                case "docx": $mimeType = "application/vnd.openxmlformats-officedocument.wordprocessingml.document"; break;
                                case "eml": $mimeType = "message/rfc822"; break;
                                case "exe": $mimeType = "application/x-msdownload"; break;
                                case "flv": $mimeType = "video/x-flv"; break;
                                case "gif": $mimeType = "image/gif"; break;
                                case "gtar": $mimeType = "application/x-gtar"; break;
                                case "h": $mimeType = "text/plain"; break;
                                case "htm": case "html": $mimeType = "text/html"; break;
                                case "ico": $mimeType = "image/x-icon"; break;
                                case "ics": $mimeType = "text/calendar"; break;
                                case "jar": $mimeType = "application/java-archive"; break;
                                case "java": $mimeType = "text/x-java-source,java"; break;
                                case "jpe": case "jpg": case "jpeg": $mimeType = "image/jpeg"; break;
                                case "js": $mimeType = "application/javascript"; break;
                                case "json": $mimeType = "application/json"; break;
                                case "m3u": $mimeType = "audio/x-mpegurl"; break;
                                case "m4v": $mimeType = "audio/x-mpegurl"; break;
                                case "movie": $mimeType = "video/x-sgi-movie"; break;
                                case "mp3": $mimeType = "audio/mpeg"; break;
                                case "mpga": $mimeType = "audio/mpeg"; break;
                                case "mpp": $mimeType = "application/vnd.ms-project"; break;
                                case "pdf": $mimeType = "application/pdf"; break;
                                case "pki": $mimeType = "application/pkixcmp"; break;
                                case "png": $mimeType = "image/png"; break;
                                case "ppt": case "pptx": $mimeType = "application/vnd.ms-powerpoint"; break;
                                case "ps1": $mimeType = "text/plain"; break;
                                case "rtf": $mimeType = "application/rtf"; break;
                                case "sh": $mimeType = "application/x-sh"; break;
                                case "swf": $mimeType = "application/x-shockwave-flash"; break;
                                case "tar": $mimeType = "application/x-tar"; break;
                                case "tif": case "tiff": $mimeType = "image/tiff"; break;
                                case "txt": $mimeType = "text/plain"; break;
                                case "vcd": case "vcf": $mimeType = "text/x-vcard"; break;
                                case "vsd": $mimeType = "application/vnd.visio"; break;
                                case "vsdx": $mimeType = "application/vnd.visio2013"; break;
                                case "wav": $mimeType = "audio/x-wav"; break;
                                case "wm": $mimeType = "video/x-ms-wm"; break;
                                case "wma": $mimeType = "audio/x-ms-wma"; break;
                                case "wmv": $mimeType = "video/x-ms-wmv"; break;
                                case "wri": $mimeType = "application/x-mswrite"; break;
                                case "wsdl": $mimeType = "application/wsdl+xml"; break;
                                case "xap": $mimeType = "application/x-silverlight-app"; break;
                                case "xbm": $mimeType = "image/x-xbitmap"; break;
                                case "xhtml": $mimeType = "application/xhtml+xml"; break;
                                case "xls": case "xlsx": $mimeType = "application/vnd.ms-excel"; break;
                                case "xml": $mimeType = "application/xml"; break;
                                case "xop": $mimeType = "application/xop+xml"; break;
                                case "xslt": $mimeType = "application/xslt+xml"; break;
                                case "yaml": $mimeType = "text/yaml"; break;
                                case "zip": $mimeType = "application/zip"; break;
                            }   //  End switch ($extension)

                            $attachmentData  = base64_decode($attachment["data"]);
                            $mailSize       += strlen($attachmentData);

                            $mail->AddStringAttachment($attachmentData, $filename, "base64", $mimeType);
                        }   //  End if ((strrchr($filename, ".")) !== false)
                    }   //  End if ((array_key_exists("filename", $attachment)) &&
                }   //  End foreach($attachments as &$attachment)
            }   //  End if (($attachments != null) && (is_array($attachments))) {

            $mail->Send();
            $mailSent = true;
        } else {
            $errorCode    = 4;
            $errorMessage = "sendmail daemon: Not all input was supplied to process this input: " . $bitmask;
        }   //  End if ($bitmask === 7)
 
        $responseJson["errorCode"] = $errorCode;
        $responseJson["mailSent"] = $mailSent;

        // Attach non-zero mailSize
        if ($mailSize > 0) {
            $responseJson["mailSize"] = $mailSize;
        }   //  End if ($mailSize > 0)

        if ($errorMessage != null) {
            $responseJson["error"] = $errorMessage;
        }   //  End if ($errorMessage != null)

        // Send result back
        header('Content-Type: application/json; charset=utf-8');
        print(utf8_encode(json_encode($responseJson)));
    }   //  End if ($postBody !== "")
}   //  End if (($_SERVER["REQUEST_METHOD"] === "POST") &&

ob_end_flush();
?>
