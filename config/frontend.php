<?php

// Redirect targets for the Blade (server-rendered) frontend branch. There is
// intentionally NO runtime branching on the frontend mode; this file is simply
// the version that ships with the blade branch.
return [
    'guest_redirect' => '/login',
    'user_redirect' => '/dashboard',
];
