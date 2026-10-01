<?php

namespace App\Filament\Support;

use App\Support\IndonesiaRegion;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

class SchoolLocationFields
{
    public static function schema(): array
    {
        return [
            Forms\Components\Select::make('province_code')
                ->label('Provinsi')
                ->options(fn () => IndonesiaRegion::provinces())
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Set $set): void {
                    $set('province', IndonesiaRegion::provinces()[(string) $state] ?? null);
                    $set('city_code', null);
                    $set('city', null);
                    $set('district_code', null);
                    $set('district', null);
                    $set('village_code', null);
                    $set('village', null);
                }),
            Forms\Components\Hidden::make('province'),

            Forms\Components\Select::make('city_code')
                ->label('Kota / Kabupaten')
                ->options(fn (Get $get) => IndonesiaRegion::regencies($get('province_code')))
                ->searchable()
                ->preload()
                ->live()
                ->disabled(fn (Get $get) => blank($get('province_code')))
                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                    $set('city', IndonesiaRegion::regencies($get('province_code'))[(string) $state] ?? null);
                    $set('district_code', null);
                    $set('district', null);
                    $set('village_code', null);
                    $set('village', null);
                }),
            Forms\Components\Hidden::make('city'),

            Forms\Components\Select::make('district_code')
                ->label('Kecamatan')
                ->options(fn (Get $get) => IndonesiaRegion::districts($get('city_code')))
                ->searchable()
                ->preload()
                ->live()
                ->disabled(fn (Get $get) => blank($get('city_code')))
                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                    $set('district', IndonesiaRegion::districts($get('city_code'))[(string) $state] ?? null);
                    $set('village_code', null);
                    $set('village', null);
                }),
            Forms\Components\Hidden::make('district'),

            Forms\Components\Select::make('village_code')
                ->label('Kelurahan / Desa')
                ->options(fn (Get $get) => IndonesiaRegion::villages($get('district_code')))
                ->searchable()
                ->preload()
                ->disabled(fn (Get $get) => blank($get('district_code')))
                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                    $set('village', IndonesiaRegion::villages($get('district_code'))[(string) $state] ?? null);
                }),
            Forms\Components\Hidden::make('village'),
        ];
    }
}
