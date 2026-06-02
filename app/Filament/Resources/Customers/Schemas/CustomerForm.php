<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Support\CustomerPhoneNumberFormState;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->nullable()
                    ->email()
                    ->rule('required_without:phone_number')
                    ->maxLength(255),
                Select::make('phone_country_code')
                    ->label('Country Code')
                    ->options([
                        CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE => CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE,
                    ])
                    ->default(CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE)
                    ->dehydrated(false),
                TextInput::make('phone_number')
                    ->nullable()
                    ->rule('required_without:email')
                    ->maxLength(20)
                    ->helperText('Enter number without country code, e.g. 123456789')
                    ->tel(),
            ]);
    }
}

