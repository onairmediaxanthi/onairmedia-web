<?php

namespace App\Filament\Resources\Businesses\Tables;

use App\Models\Business;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'createdBy'])->withCount('quotes')
                ->withMax('activities as last_activity_at', 'created_at'))
            ->columns([
                TextColumn::make('legal_name')->label('Επιχείρηση')->weight('bold')->searchable(['legal_name', 'trade_name'])
                    ->description(fn (Business $r) => $r->trade_name),
                TextColumn::make('vat')->label('ΑΦΜ')->searchable()->copyable(),
                TextColumn::make('business_type')->label('Είδος')->formatStateUsing(fn ($state, Business $r) => $r->typeLabel())->toggleable(),
                TextColumn::make('city')->label('Πόλη')->toggleable(),
                TextColumn::make('contact_person')->label('Επικοινωνία')
                    ->description(fn (Business $r) => $r->mobile)->searchable(['contact_person', 'mobile']),
                TextColumn::make('user.email')->label('Email')->searchable()->copyable(),
                TextColumn::make('source')->label('Προέλευση')->badge()
                    ->formatStateUsing(fn ($state, Business $r) => $r->isStaffCreated() ? 'Διαχείριση' : 'Site')
                    ->description(fn (Business $r) => $r->isStaffCreated() ? $r->createdBy?->name : null)
                    ->color(fn ($state) => $state === 'staff' ? 'warning' : 'info'),
                IconColumn::make('user.email_verified_at')->label('Ενεργός')->boolean()
                    ->state(fn (Business $r) => $r->user?->email_verified_at !== null),
                IconColumn::make('marketing_consent_at')->label('Προσφορές')->boolean()
                    ->state(fn (Business $r) => $r->marketing_consent_at !== null),
                TextColumn::make('quotes_count')->label('Προσφορές')->sortable()->alignCenter(),
                TextColumn::make('last_activity_at')->label('Τελευταία κίνηση')->sortable()->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state ? \Illuminate\Support\Carbon::parse($state)->locale('el')->diffForHumans() : null),
                TextColumn::make('created_at')->label('Δημιουργία')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->label('Προέλευση')->options(Business::SOURCES),
                SelectFilter::make('business_type')->label('Είδος')->options(Business::TYPES),
                TernaryFilter::make('marketing')->label('Δέχεται προσφορές')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('marketing_consent_at'),
                        false: fn (Builder $query) => $query->whereNull('marketing_consent_at'),
                    ),
                TernaryFilter::make('verified')->label('Επιβεβαιωμένο email')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('user', fn ($u) => $u->whereNotNull('email_verified_at')),
                        false: fn (Builder $query) => $query->whereHas('user', fn ($u) => $u->whereNull('email_verified_at')),
                    ),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('quote')->label('Προσφορά')->icon('heroicon-o-document-plus')
                    ->url(fn (Business $r) => \App\Filament\Resources\Quotes\QuoteResource::getUrl('create', ['pelatis' => $r->id])),
                EditAction::make(),
            ]);
    }
}
