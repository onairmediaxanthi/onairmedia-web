<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Models\CampaignRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Παραλήπτες';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->modifyQueryUsing(fn ($q) => $q->with('user.business'))
            ->columns([
                TextColumn::make('user.business.legal_name')->label('Πελάτης')->searchable()->placeholder('—'),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('status')->label('Κατάσταση')->badge()
                    ->formatStateUsing(fn ($state) => ['pending' => 'Σε αναμονή', 'sent' => 'Στάλθηκε', 'failed' => 'Απέτυχε', 'skipped' => 'Παραλείφθηκε'][$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'sent' => 'success', 'failed' => 'danger', 'skipped' => 'gray', default => 'warning' })
                    ->description(fn (CampaignRecipient $r) => $r->error),
                TextColumn::make('sent_at')->label('Στάλθηκε')->dateTime('d/m H:i')->placeholder('—'),
                TextColumn::make('unsubscribed_at')->label('Διαγράφηκε')->dateTime('d/m H:i')->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->label('Κατάσταση')->options(['pending' => 'Σε αναμονή', 'sent' => 'Στάλθηκε', 'failed' => 'Απέτυχε', 'skipped' => 'Παραλείφθηκε'])]);
    }
}
