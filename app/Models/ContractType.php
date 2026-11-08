<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractType extends Model
{
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_CANCELLATION = 'cancellation';
    public const TYPE_SUSPENSION = 'suspension';
    public const TYPE_IMAGE_RIGHTS = 'image_rights';
    public const TYPE_ATTENDANCE = 'attendance';

    public const DOCUMENT_TYPES = [
        self::TYPE_CONTRACT => 'Contrato de Matrícula',
        self::TYPE_CANCELLATION => 'Termo de Rescisão / Distrato',
        self::TYPE_SUSPENSION => 'Termo de Trancamento Provisório',
        self::TYPE_IMAGE_RIGHTS => 'Autorização de Uso de Imagem e Voz (LGPD)',
        self::TYPE_ATTENDANCE => 'Declaração de Comparecimento',
    ];

    protected $fillable = [
        'name',
        'document_type',
        'version',
        'description',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        // Garante que só existe um modelo ativo por tipo de documento por vez
        static::saving(function ($contractType) {
            if ($contractType->is_active) {
                $docType = $contractType->document_type ?: self::TYPE_CONTRACT;
                static::where('id', '!=', $contractType->id ?? 0)
                    ->where('document_type', $docType)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });

        static::deleting(function ($contractType) {
            if ($contractType->contracts()->exists()) {
                throw new \Exception('Este tipo de contrato não pode ser excluído pois possui contratos de alunos vinculados a ele. Inative-o em vez de excluí-lo.');
            }
        });
    }

    public static function getActiveForType(string $documentType): ?self
    {
        return static::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
