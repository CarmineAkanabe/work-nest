<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Necessary for handling for controllers to use policies "$this->authorize()"
    use AuthorizesRequests;
}
