<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\SP\Auth\SortkarSPUser;
use App\Models\SortkarJobRole;
use Filament\Tables\Columns\BadgeColumn;
use App\Filament\Admin\Resources\ServiceProviderResource\Pages;

class ServiceProviderResource extends Resource
{
    protected static ?string $model = SortkarSPUser::class;
    protected static ?string $role_model = SortkarJobRole::class;

    // 🧭 Sidebar navigation settings
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Service Providers';
    protected static ?string $pluralModelLabel = 'Service Providers';
    protected static ?string $modelLabel = 'Service Provider';
    protected static ?string $navigationGroup = 'Admin Management';
    protected static ?int $navigationSort = 1;

    // 🏷️ Optional: Change breadcrumb name
    protected static ?string $breadcrumb = 'Providers List';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // 👉 Add form fields here
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                // 👇 Combine first_name + last_name
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Provider')
                    ->getStateUsing(fn($record) => trim($record->first_name . ' ' . $record->last_name))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->getStateUsing(fn($record) => self::getRoleNames($record->intrested_role)),


                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registration')
                    ->dateTime('d M Y')
                    ->sortable(),


                Tables\Columns\TextColumn::make('is_verified')
                    ->label('Status')
                    ->badge()
                    ->icon(fn($state) => match ($state) {
                        1 => 'heroicon-o-check-circle',
                        2 => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn($state) => match ($state) {
                        1 => 'success',   // Filament’s green
                        2 => 'danger',    // Filament’s red
                        default => 'warning', // Filament’s yellow
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        1 => 'Verified',
                        2 => 'Rejected',
                        default => 'Pending',
                    }),

                Tables\Columns\TextColumn::make('contact')
                    ->label('Contact')
                    ->getStateUsing(function ($record) {
                        $mobile = $record->mobile1_number;
                        if (!$mobile) return null;

                        $whatsappUrl = 'https://wa.me/' . preg_replace('/\D/', '', $mobile);
                        $callUrl = 'tel:' . preg_replace('/\D/', '', $mobile);

                        return view('filament.columns.contact-icons', [
                            'whatsappUrl' => $whatsappUrl,
                            'callUrl' => $callUrl,
                        ]);
                    })
                    ->sortable(false),




            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Delete Selected'),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            // 👉 Add relation managers here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceProviders::route('/'),
            'create' => Pages\CreateServiceProvider::route('/create'),
            'edit' => Pages\EditServiceProvider::route('/{record}/edit'),
        ];
    }

    // 🧭 Custom page title (for sidebar & breadcrumb)
    public static function getNavigationLabel(): string
    {
        return 'Manage Providers';
    }

    public static function getNavigationGroup(): string
    {
        return 'Admin Section';
    }

    public static function getPluralModelLabel(): string
    {
        return 'All Providers';
    }

    public static function getModelLabel(): string
    {
        return 'Provider';
    }

    /**
     * Get role names for interested_role IDs
     */
    public static function getRoleNames($interestedRoleIds): string
    {
        // Normalize to array
        if (is_string($interestedRoleIds)) {
            $decoded = json_decode($interestedRoleIds, true);
            $ids = is_array($decoded) ? $decoded : (is_int($decoded) ? [$decoded] : []);
        } elseif (is_array($interestedRoleIds)) {
            $ids = $interestedRoleIds;
        } elseif (is_int($interestedRoleIds)) {
            $ids = [$interestedRoleIds];
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return '-';
        }

        $roles = self::$role_model::whereIn('zoho_job_role_id', $ids)
            ->pluck('role_name')
            ->toArray();

        return implode(', ', $roles);
    }
}
