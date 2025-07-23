<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'announcements' => Announcement::current()->get(),
        ]);
    }
}
