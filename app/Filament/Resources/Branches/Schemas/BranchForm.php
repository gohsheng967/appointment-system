<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Support\CustomerPhoneNumberFormState;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('timezone')
                    ->label('Timezone (IANA)')
                    ->required()
                    ->rule('timezone')
                    ->maxLength(100),
                TimePicker::make('opening_time')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('closing_time')
                    ->seconds(false)
                    ->required(),
                TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Select::make('phone_country_code')
                    ->label('Country Code')
                    ->required()
                    ->options([
                        CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE => CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE,
                    ])
                    ->default(CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE)
                    ->dehydrated(false),
                TextInput::make('phone_number')
                    ->required()
                    ->maxLength(20)
                    ->helperText('Enter number without country code, e.g. 112223333')
                    ->tel(),
            ]);
    }
}

