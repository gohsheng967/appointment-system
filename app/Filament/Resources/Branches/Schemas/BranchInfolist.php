<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('timezone'),
                TextEntry::make('opening_time'),
                TextEntry::make('closing_time'),
                TextEntry::make('address'),
                TextEntry::make('phone'),
            ]);
    }
}
