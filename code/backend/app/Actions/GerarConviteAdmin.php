<?php

namespace App\Actions;

use App\Mail\ConviteAdminMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GerarConviteAdmin
{
    public function handle(User $admin): string
    {
        $admin->loadMissing('empresa');
        $token = Str::random(48);
        $admin->update([
            'convite_token' => hash('sha256', $token),
            'convite_expira_em' => now()->addDays(7),
        ]);

        $url = $this->url($token);
        Mail::to($admin->email)->queue(new ConviteAdminMail(
            $admin,
            $url,
            $admin->empresa?->nome ?? 'sua agência',
        ));

        return $url;
    }

    public function url(string $token): string
    {
        return config('services.frontend.url').'/convite?token='.$token;
    }

    public function localizar(string $token): ?User
    {
        if ($token === '') {
            return null;
        }

        return User::query()
            ->with('empresa')
            ->where('convite_token', hash('sha256', $token))
            ->first();
    }
}
