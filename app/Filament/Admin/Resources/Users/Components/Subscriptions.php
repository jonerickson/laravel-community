<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Components;

use Filament\Schemas\Components\Component;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

abstract class Subscriptions extends Component implements HasTable
{
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseTable();
    }
}
