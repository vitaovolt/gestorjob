<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait PertenceAEmpresa
{
    public static function bootPertenceAEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $query) {
            $user = auth()->user();
            if (! $user || $user->ehSuperAdmin()) {
                return;
            }

            if (! $user->empresa_id) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where($query->getModel()->getTable().'.empresa_id', $user->empresa_id);
        });

        static::creating(function (Model $model) {
            $user = auth()->user();
            if ($user?->empresa_id && blank($model->getAttribute('empresa_id'))) {
                $model->setAttribute('empresa_id', $user->empresa_id);
            }
        });
    }

    public function scopeDoTenant(Builder $query, int $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId);
    }
}
