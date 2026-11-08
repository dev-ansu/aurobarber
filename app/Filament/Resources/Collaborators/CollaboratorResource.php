<?php

namespace App\Filament\Resources\Collaborators;

use App\Filament\Resources\Collaborators\Pages\CreateCollaborator;
use App\Filament\Resources\Collaborators\Pages\EditCollaborator;
use App\Filament\Resources\Collaborators\Pages\ListCollaborators;
use App\Filament\Resources\Collaborators\Pages\ViewCollaborator;
use App\Filament\Resources\Collaborators\Schemas\CollaboratorForm;
use App\Filament\Resources\Collaborators\Schemas\CollaboratorInfolist;
use App\Filament\Resources\Collaborators\Tables\CollaboratorsTable;
use App\Models\Collaborator;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CollaboratorResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = Collaborator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $recordTitleAttribute = 'person.name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record?->display_name ?? $record?->person?->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['person.name', 'role'];
    }

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Colaboradores';

    protected static ?string $pluralModelLabel = 'Colaboradores';

    protected static ?string $modelLabel = 'colaborador';

    protected static ?string $slug = 'collaborators';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->id;

        return parent::getEloquentQuery()
            ->whereHas('person')
            ->when($tenantId, function (Builder $query) use ($tenantId): void {
                $query->where(function (Builder $sub) use ($tenantId): void {
                    $sub->whereHas('person', function (Builder $pq) use ($tenantId): void {
                        $pq->whereHas('schools', fn ($sq) => $sq->where('schools.id', $tenantId));
                    })
                        ->orWhereHas('user.schools', fn ($usq) => $usq->where('schools.id', $tenantId))
                        ->orWhereExists(function ($cq) use ($tenantId): void {
                            $cq->selectRaw('1')
                                ->from('contracts')
                                ->whereColumn('contracts.collaborator_id', 'collaborators.id')
                                ->where('contracts.school_id', $tenantId);
                        });
                });
            });
    }

    public static function form(Schema $schema): Schema
    {
        return CollaboratorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CollaboratorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollaboratorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollaborators::route('/'),
            'create' => CreateCollaborator::route('/create'),
            'view' => ViewCollaborator::route('/{record}'),
            'edit' => EditCollaborator::route('/{record}/edit'),
        ];
    }
}
