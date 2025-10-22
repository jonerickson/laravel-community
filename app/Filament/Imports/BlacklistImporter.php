<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\Blacklist;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Override;

class BlacklistImporter extends Importer
{
    protected static ?string $model = Blacklist::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('content')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('is_regex')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your blacklist import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if (($failedRowsCount = $import->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    #[Override]
    public function resolveRecord(): Blacklist
    {
        return Blacklist::firstOrNew([
            'content' => $this->data['content'],
        ]);
    }
}
