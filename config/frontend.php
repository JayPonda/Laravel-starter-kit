<?php

// Redirect targets for the static-HTML (public) frontend branch. There is
// intentionally NO runtime branching on the frontend mode; this file is simply
// the version that ships with the html branch.
return [
    'guest_redirect' => '/login.html',
    'user_redirect' => '/',
];
