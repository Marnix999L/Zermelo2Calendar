/**
 * Copyright (C) 2016 Marnix Looijmans.
 * All Rights Reserved.
 *
 * This code is subject to the Apache License 2.0.
 * You may use, distribute and modify this code
 * under the terms of the Apache License 2.0.
 */

// Listen for clicks on the reset button.
$(".btn-reset").click(function() {
    // Get the default URL from the hidden input field.
    var defaultURL = $("#field-default-url").val();
    // Set the visible input field to the default URL.
    $("#field-result-url").val(defaultURL);
});

// Listen for clicks on the return button.
$(".btn-return").click(function() {
    // Redirect to the home page.
    window.location.href = "/";
});
