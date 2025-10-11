<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\Forum;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ForumImporter extends Importer
{
    protected static ?string $model = Forum::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('slug')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description'),
            ImportColumn::make('category')
                ->relationship(),
            ImportColumn::make('rules'),
            ImportColumn::make('icon')
                ->rules(['max:255']),
            ImportColumn::make('color')
                ->requiredMapping()
                ->rules(['required', 'max:7']),
            ImportColumn::make('order')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('is_active')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your forum import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    public function resolveRecord(): Forum
    {
        return Forum::firstOrNew([
            'name' => $this->data['name'],
        ]);
    }
}
