<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;

class SuspendContractAction
{
    public static function make(): Action
    {
        return Action::make('suspendContract')
            ->label('Trancar Contrato')
            ->icon('heroicon-m-pause-circle')
            ->color('warning')
            ->visible(fn (Contract $record): bool => $record->status === 'active')
            ->modalHeading('Trancamento Provisório de Matrícula')
            ->modalDescription('Defina a data inicial, o prazo de trancamento e o motivo. O status do contrato será alterado para "Suspenso" e o termo será emitido.')
            ->modalSubmitActionLabel('Confirmar Trancamento e Gerar Termo')
            ->form([
                DatePicker::make('start_date')
                    ->label('Data de Início do Trancamento')
                    ->default(today())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        $start = $get('start_date') ? Carbon::parse($get('start_date')) : today();
                        $days = (int) ($get('days') ?: 30);
                        $set('expected_return_date', $start->copy()->addDays($days)->format('Y-m-d'));
                    }),

                Select::make('days')
                    ->label('Prazo de Trancamento')
                    ->options([
                        15 => '15 dias',
                        30 => '30 dias (1 mês)',
                        45 => '45 dias',
                        60 => '60 dias (2 meses)',
                        90 => '90 dias (3 meses)',
                    ])
                    ->default(30)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        $start = $get('start_date') ? Carbon::parse($get('start_date')) : today();
                        $days = (int) ($get('days') ?: 30);
                        $set('expected_return_date', $start->copy()->addDays($days)->format('Y-m-d'));
                    }),

                DatePicker::make('expected_return_date')
                    ->label('Previsão de Retorno')
                    ->default(today()->addDays(30))
                    ->required(),

                TextInput::make('reason')
                    ->label('Motivo / Justificativa')
                    ->placeholder('Ex: Motivos de saúde, trabalho temporário...')
                    ->default('motivos de ordem particular e temporária')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Contract $record, array $data, $livewire): void {
                $startDate = Carbon::parse($data['start_date']);
                $expectedReturn = Carbon::parse($data['expected_return_date']);
                $reason = (string) $data['reason'];
                $days = (int) ($data['days'] ?: $startDate->diffInDays($expectedReturn));

                $record->update([
                    'status' => 'suspended',
                    'suspended_at' => $startDate,
                    'suspension_expected_return_date' => $expectedReturn,
                    'suspension_reason' => $reason,
                ]);

                // Abre o Termo de Trancamento em nova aba
                $url = route('contracts.termo-trancamento', [
                    'contract' => $record,
                    'days' => $days,
                    'start_date' => $startDate->format('Y-m-d'),
                    'return_date' => $expectedReturn->format('Y-m-d'),
                    'reason' => $reason,
                ]);

                $livewire->js('window.open(' . json_encode($url) . ", '_blank');");

                Notification::make()
                    ->title('Contrato Trancado com Sucesso')
                    ->body("O contrato #{$record->contract_number} foi alterado para Suspenso com previsão de retorno em {$expectedReturn->format('d/m/Y')}.")
                    ->warning()
                    ->send();
            });
    }
}

