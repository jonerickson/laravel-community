<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function __invoke()
    {
        if (! App::isDownForMaintenance()) {
            return inertia()->location(route('home'));
        }

        return Inertia::render('maintenance');
    }
}
