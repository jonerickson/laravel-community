<?php

declare(strict_types=1);

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('news/index');
    }
}
