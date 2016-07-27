<?php
    /**
     * Copyright (C) 2016 Marnix Looijmans.
     * All Rights Reserved.
     *
     * This code is subject to the Apache License 2.0.
     * You may use, distribute and modify this code
     * under the terms of the Apache License 2.0.
     */

    /**
     * Checks if a date in a string is valid.
     *
     * @param String $dateString The string with tht date to validate.
     * @return bool True if the date is valid, otherwise false.
     */
    function isDateInputValid($dateString) {
        $dateComponents = explode("-", $dateString);
        if (count($dateComponents) == 3) {
            if (strlen($dateComponents[0]) == 4) {
                $dateComponents = array_reverse($dateComponents);
            }
            $day = intval($dateComponents[0]);
            $month = intval($dateComponents[1]);
            $year = intval($dateComponents[2]);
            if (checkdate($month, $day, $year) && $year < 2038 && $year >= 1970) {
                return true;
            }
        }

        return false;
    }

    // Check if a post request is being made.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Define an empty array of errors which we might fill later.
        $errors = array();
        // Validate institution.
        if (!isset($_POST["institution"]) || empty($_POST["institution"])) {
            // The institution is not provided.
            $errors["institution"] = "An institution is required.";
        } else if (preg_match('/\s/', $_POST["institution"])) {
            // The provided institution is invalid.
            $errors["institution"] = "This institution is invalid.";
        }

        // Validate authorization code.
        if (!isset($_POST["authorization_code"]) || empty($_POST["authorization_code"])) {
            // The authorization code is not provided.
            $errors["authorization_code"] = "An authorization code is required.";
        } elseif (!is_numeric($_POST["authorization_code"])) {
            // The provided authorization code is invalid.
            $errors["authorization_code"] = "This authorization code is invalid.";
        }

        // Validate start date
        if (!isset($_POST["start_date"]) || empty($_POST["start_date"])) {
            // The start date is not provided.
            $errors["start_date"] = "A start date is required.";
        } elseif (!isDateInputValid($_POST["start_date"])) {
            // The provided start date is invalid.
            $errors["start_date"] = "This start date is invalid.";
        }

        // Check if there are no errors.
        if (empty($errors)) {
            // Store the post parameters.
            $institution = $_POST["institution"];
            $authorizationCode = $_POST["authorization_code"];
            $startDate = strtotime($_POST["start_date"]." GMT");
            $endDate = strtotime("+1 year", $startDate); // Define the end date as the start date + 1 year.

            // Put together the URL for the http request.
            $requestURL = "https://".$institution.".zportal.nl/api/v2/oauth/token";

            // Define a new cURL instance.
            $ch = curl_init();
            // Set the request URL.
            curl_setopt($ch, CURLOPT_URL, $requestURL);
            // Make sure we receive a response.
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Set the request method to `POST` instead of `GET`.
            curl_setopt($ch, CURLOPT_POST, true);
            // Provide the post fields.
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                "grant_type" => "authorization_code",
                "code" => $authorizationCode
            ));
            // Make the http request and retrieve the result.
            $result = curl_exec($ch);
            // Close the cURL instance.
            curl_close($ch);

            // The result will be false if the http request failed.
            if ($result !== false) {
                // Try to decode a JSON response. This will fail if the result was not valid JSON.
                $data = json_decode($result, true);
                // Check if the response successfully decoded.
                if ($data) {
                    // Store the received access token.
                    $accessToken = $data["access_token"];
                    // Put together the resulted URL.
                    $resultURL = "http://zermelo2calendar.marnixlooijmans.dsmynas.org/".$institution."/".$accessToken."/".$startDate."/".$endDate."/calendar.ics";
                }
            }
        }
     }
?>
<!DOCTYPE html>

<html lang="en">
    <head>
        <!-- Define the meta tags -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Define the title tag -->
        <title>Zermelo2Calendar</title>

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <!-- Additional CSS of our own -->
        <link rel="stylesheet" href="main.css">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="container">
            <!-- Start page content -->
            <div class="content">
                <h1>Zermelo2Calendar</h1>
                <p class="lead">
                    <!-- Intro message -->
                    <?php if (isset($resultURL)): ?>
                        Use this URL to receive an updated schedule. The URL is valid for <b>one year</b>.
                    <?php else: ?>
                        Fill out this form to request a URL.
                        You can find the institution and authorization code by logging in your school&#39;s Zermelo Portal.
                        Then go to <b>Koppelingen</b> on the left and click <b>Koppel App</b>.
                    <?php endif; ?>
                </p>

                <?php if (isset($resultURL)): ?>
                    <!-- This input field contains the result URL which the user can copy from here. -->
                    <input type="text" name="url" id="field-result-url" class="input-field" value="<?php echo $resultURL; ?>">
                    <!-- This hidden field contains the result URL for resetting purposes if the user accidently removed the result URL. -->
                    <input type="hidden" name="default-url" id="field-default-url" value="<?php echo $resultURL; ?>">
                    <!-- The button to reset the result URL. -->
                    <button type="button" class="btn btn-info btn-result btn-reset">Reset to default</button>

                    <!-- Start instruction well -->
                    <div class="well">
                        <b>What to do next?</b><br><br>
                        <ol>
                            <li>Copy this URL.</li>
                            <li>On your iPad, go to <b>Settings</b> &gt; <b>Mail, Contacts, Calendar</b> &gt; <b>Add Account</b> &gt; <b>Other</b> &gt; <b>Add Subscribed Calendar</b>.</li>
                            <li>There, paste this URL (or retype it) and press <b>Next</b>.</li>
                            <li>If being prompted to connect without SSL, press <b>Continue</b>.</li>
                            <li>Change <i>Description</i> to <b>Schedule</b> or something else (can be anything).</li>
                            <li>No user name or password is required, but make sure <i>Use SSL</i> is <b>turned off</b>.</li>
                            <li>Press <b>Next</b> in the upper right corner.</li>
                            <li>If being asked to continue, press <b>Save</b>.</li>
                            <li>Again, press <b>Save</b> in the upper right corner.</li>
                            <li>That&#39;s it, you&#39;re good to go!</li>
                        </ol>
                    </div>
                    <!-- End instruction well -->

                    <!-- The button to go back to home page -->
                    <button type="button" class="btn btn-primary btn-result btn-return">Go back</button>
                <?php else: ?>
                    <!-- Start request URL form -->
                    <form class="request-form" method="post" action="#">
                        <!-- Input field for the institution -->
                        <input type="text" name="institution" class="input-field" placeholder="Institution" value="<?php echo $_POST["institution"]; ?>">
                        <?php if (isset($errors) && isset($errors["institution"])): ?>
                            <div class="error-block input-error">
                                <!-- Error message for the institution -->
                                <?php echo $errors["institution"]; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Input field for the authorization code -->
                        <input type="text" name="authorization_code" class="input-field" placeholder="Authorization code (without spaces)" value="<?php echo $_POST["authorization_code"]; ?>">
                        <?php if (isset($errors) && isset($errors["authorization_code"])): ?>
                            <div class="error-block input-error">
                                <!-- Error message for the authorization code -->
                                <?php echo $errors["authorization_code"]; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Input field for the start date -->
                        <input type="date" name="start_date" class="input-field" placeholder="Start date (dd-mm-yyyy)" value="<?php echo $_POST["start_date"]; ?>">
                        <?php if (isset($errors) && isset($errors["start_date"])): ?>
                            <div class="error-block input-error">
                                <!-- Error message for the start date -->
                                <?php echo $errors["start_date"]; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($errors) && empty($errors)): ?>
                            <div class="error-block">
                                <!-- Error message for failed request -->
                                Something went wrong while requesting the URL. Please provide a different authorization code and try again.
                            </div>
                        <?php endif; ?>

                        <!-- The submit button -->
                        <button class="btn btn-lg btn-primary btn-block" type="submit">Request URL</button>
                    </form>
                    <!-- End request URL form -->
                <?php endif; ?>
            </div>
            <!-- End page content -->
        </div>

        <!-- Install jQuery (necessary for Bootstrap) -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <!-- Install Bootstrap -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <!-- Install additional JavaScript of our own -->
        <script src="script.js"></script>
    </body>
</html>
