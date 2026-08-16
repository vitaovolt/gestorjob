<?php

namespace App\Actions;

use App\Mail\PrazoHojeMail;
use App\Models\EmailPrazoEnviado;
use App\Models\Empresa;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EnviarEmailPrazoHoje
{
    public function handle(User $destinatario, Tarefa $tarefa, ?Carbon $dia = null): bool
    {
        $dia ??= now();

        if ($destinatario->ehSuperAdmin() || ! $destinatario->empresa_id || ! $destinatario->email) {
            return false;
        }

        $empresa = $destinatario->empresa ?? Empresa::query()->find($destinatario->empresa_id);
        if (! $empresa || ! $empresa->config('notif_email')) {
            return false;
        }

        $registro = EmailPrazoEnviado::query()->firstOrCreate(
            [
                'user_id' => $destinatario->id,
                'tarefa_id' => $tarefa->id,
                'dia' => $dia->toDateString(),
            ],
            [
                'empresa_id' => $destinatario->empresa_id,
            ],
        );

        if (! $registro->wasRecentlyCreated) {
            return false;
        }

        $url = rtrim((string) config('services.frontend.url'), '/').'/';

        Mail::to($destinatario->email)->queue(new PrazoHojeMail(
            $destinatario,
            $tarefa,
            $url,
            $dia->copy()->startOfDay(),
        ));

        return true;
    }
}
