<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Reports';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderation';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('reason')
                ->disabled(),
            Textarea::make('description')
                ->disabled()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('reporter.name')
                    ->label('Reporter')
                    ->searchable(),
                TextColumn::make('reportedUser.name')
                    ->label('Reported User')
                    ->searchable(),
                TextColumn::make('reason')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                        default    => 'secondary',
                    }),
                IconColumn::make('auto_moderated')
                    ->boolean()
                    ->label('Auto'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'resolved'  => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Report $record): void {
                        $record->update(['status' => 'resolved']);
                        Notification::make()
                            ->title('Report resolved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending'),
                Action::make('dismiss')
                    ->label('Dismiss')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Report $record): void {
                        $record->update(['status' => 'dismissed']);
                        Notification::make()
                            ->title('Report dismissed')
                            ->send();
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending'),
                Action::make('ban_user')
                    ->label('Ban User')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Report $record): void {
                        if ($record->reported_user_id) {
                            User::where('id', $record->reported_user_id)
                                ->update(['is_banned' => true]);
                            $record->update(['status' => 'resolved']);
                            Notification::make()
                                ->title('User banned')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Report $record): bool => $record->reported_user_id !== null),
                Action::make('reduce_reputation')
                    ->label('Reduce Rep')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Report $record): void {
                        if ($record->reported_user_id) {
                            User::where('id', $record->reported_user_id)
                                ->decrement('reputation_score', 10);
                            Notification::make()
                                ->title('Reputation reduced')
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (Report $record): bool => $record->reported_user_id !== null),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['reporter', 'reportedUser', 'reportedSession']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'view'  => Pages\ViewReport::route('/{record}'),
        ];
    }
}
