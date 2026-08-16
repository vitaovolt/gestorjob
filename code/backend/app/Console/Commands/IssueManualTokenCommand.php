<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueManualTokenCommand extends Command
{
    protected $signature = 'gestorjob:token
                            {email=mariana@agenciaeduc.local : E-mail do usuário}
                            {--name=manual : Nome do token Sanctum}';

    protected $description = 'Emite token Sanctum para smoke manual (F1, antes do login F2)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Usuário não encontrado: {$email}");
            $this->line('Rode: php artisan migrate:fresh --seed --force');

            return self::FAILURE;
        }

        $plain = $user->createToken((string) $this->option('name'))->plainTextToken;

        $this->info("Token de {$email}. Cole o bloco INTEIRO no PowerShell (não use tinker):");
        $this->newLine();
        $this->line('$token = "'.$plain.'"');
        $this->line('$h = @{ Authorization = "Bearer $token"; Accept = "application/json" }');
        $this->newLine();
        $this->comment('Conferência (tem que começar com a palavra Bearer):');
        $this->line('$h.Authorization');
        $this->comment('Depois:');
        $this->line('Invoke-RestMethod http://127.0.0.1:8000/api/v1/empresa -Headers $h | ConvertTo-Json -Depth 5');

        return self::SUCCESS;
    }
}
