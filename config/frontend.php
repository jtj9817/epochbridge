<?php

return [
    /*
    |--------------------------------------------------------------------------
    | React Frontend Migration
    |--------------------------------------------------------------------------
    |
    | This flag controls whether the React/Inertia frontend is enabled.
    | When false, routes serve the existing Volt/Livewire implementation.
    | When true, routes serve the new React/Inertia pages.
    |
    | This allows page-by-page or route-group cutover and makes rollback
    | operationally cheap during the migration period.
    |
    */

    'react_enabled' => env('FRONTEND_REACT_ENABLED', true),
];
