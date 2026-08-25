<?php

// Per-mode redirect targets, baked in by the chosen frontend preset at setup
// time. There is intentionally NO runtime branching on the frontend mode:
// the apply step copies exactly one of these files into place.
return [
    'guest_redirect' => '/login',
    'user_redirect' => '/dashboard',
];
