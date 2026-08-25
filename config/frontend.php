<?php

// Redirect targets for the backend-only (no frontend) branch. There is no Blade
// or static frontend here, so unauthenticated users are sent to the Swagger UI.
// Each frontend branch ships its own version of this file.
return [
    'guest_redirect' => '/api/documentation',
    'user_redirect' => '/api/documentation',
];
