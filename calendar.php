<?php
    /**
     * Copyright (C) 2016 Marnix Looijmans.
     * All Rights Reserved.
     *
     * This code is subject to the XYZ License.
     * You may use, distribute and modify this code
     * under the terms of the XYZ license.
     */

    // Set appropriate headers for ics file.
    header("Content-type: text/calendar; charset=utf-8");
    header("Content-Disposition: attachment; filename=zermelo.ics");

    /**
     * Escape characters in a given string.
     *
     * @param String $string The string to escape.
     * @return String The escaped string.
     */
    function escapeCharacters($string) {
        return preg_replace('/([\,;])/','\\\$1', $string);
    }

    /**
     * Convert a timestamp to format for ics files.
     *
     * @param int $timestamp The timestamp to convert.
     * @return String A string containing the time formatted for ics files.
     */
    function timestampToText($timestamp) {
        return date("Ymd\THis", $timestamp);
    }

    // Retrieve and store the get parameters.
    $institution = (is_string($_GET["institution"])) ? $_GET["institution"] : null;
    $startTime = (is_numeric($_GET["start"]) && !is_float($_GET["start"])) ? (int) $_GET["start"] : null;
    $endTime = (is_numeric($_GET["end"]) && !is_float($_GET["end"])) ? (int) $_GET["end"] : null;
    $accessToken = (is_string($_GET["access_token"])) ? $_GET["access_token"] : null;

    // Now check for any invalid parameters.
    if ($institution === null || $startTime === null || $endTime === null || $accessToken === null) {
        // Exit the script. This will return an empty ics file.
        exit();
    }

    // Define the domain name separately, because we are gonna need it later.
    $domain = $institution.".zportal.nl";
    // Put the URL together to which we will make the http request.
    $URL = "https://".$domain."/api/v2/appointments?user=~me&valid=true&cancelled=false".
            "&start=".$startTime.
            "&end=".$endTime.
            "&access_token=".$accessToken;

    // Define new cURL instance.
    $ch = curl_init();
    // Set the URL.
    curl_setopt($ch, CURLOPT_URL, $URL);
    // Make sure we receive a response by setting `CURLOPT_RETURNTRANSFER` to true.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Make the http request and retrieve the result.
    $result = curl_exec($ch);
    // Close the cURL instance.
    curl_close($ch);

    // If something went wrong, the result will be false.
    if ($result) {
        // Convert the received JSON string to an array.
        $data = json_decode($result, true);
        $appointments = $data["response"]["data"];
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
<?php foreach ($appointments as $appointment): ?>
BEGIN:VEVENT
UID:<?php echo $appointment["appointmentInstance"]."@".$domain; ?>

DTSTART:<?php echo timestampToText($appointment["start"]); ?>

DTEND:<?php echo timestampToText($appointment["end"]); ?>

DTSTAMP:<?php echo timestampToText($appointment["lastModified"]); ?>

SUMMARY:<?php echo escapeCharacters(implode(", ", $appointment["subjects"])); ?>

LOCATION:<?php echo escapeCharacters(strtoupper(implode(", ", $appointment["locations"])." ".implode(", ", $appointment["teachers"]))); ?>

DESCRIPTION:<?php echo escapeCharacters(ucfirst($appointment["type"]));
    if (!empty($appointment["changeDescription"])) echo escapeCharacters("\\n".$appointment["changeDescription"]);
?>

END:VEVENT
<?php endforeach; ?>
END:VCALENDAR
<?php
    }
?>
