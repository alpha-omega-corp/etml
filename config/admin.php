<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrator password
    |--------------------------------------------------------------------------
    |
    | Never committed: it lives in the environment. A bcrypt hash is accepted
    | and preferred; a plain string works too and is compared in constant time.
    | Leave it empty and the administrator mode cannot be entered at all.
    |
    */

    'password' => env('ADMIN_PASSWORD'),

];
