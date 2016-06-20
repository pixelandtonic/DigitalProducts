<?php
return [
    // If a user should be assigned licenses on registration belonging to his email address.
    'autoAssignLicensesOnUserRegistration' => true,

    // Should a user be assigned the license if a purchase is being made by a
    // non-logged in user with the user's e-mail address.
    'autoAssignUserOnPurchase' => true,

    // The alphabet to use for license keys
    'licenseKeyCharacters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',

    // Length of the generated license keys
    'licenseKeyLength' => 24,

    // If a user *must* be logged in to complete a purchase with Digital Products in it.
    'requireLoggedInUser' => false,
];