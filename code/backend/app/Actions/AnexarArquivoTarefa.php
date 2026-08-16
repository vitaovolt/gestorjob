<?php

namespace App\Actions;

use App\Models\Tarefa;
use App\Models\TarefaAnexo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AnexarArquivoTarefa
{
    public function handle(Tarefa $tarefa, User $user, UploadedFile $arquivo): TarefaAnexo
    {
        if ($tarefa->anexos()->count() >= TarefaAnexo::MAX_POR_TAREFA) {
            throw ValidationException::withMessages([
                'arquivo' => ['Limite de 20 anexos nesta tarefa.'],
            ]);
        }

        $extensao = $arquivo->guessExtension() ?: $arquivo->getClientOriginalExtension();
        $diretorio = $tarefa->empresa_id.'/'.$tarefa->id;
        $nomeDisco = Str::uuid()->toString().($extensao ? '.'.$extensao : '');
        $path = $arquivo->storeAs($diretorio, $nomeDisco, TarefaAnexo::DISCO);

        try {
            return TarefaAnexo::query()->create([
                'empresa_id' => $tarefa->empresa_id,
                'tarefa_id' => $tarefa->id,
                'user_id' => $user->id,
                'nome_original' => $arquivo->getClientOriginalName(),
                'path' => $path,
                'mime' => $arquivo->getMimeType(),
                'tamanho_bytes' => $arquivo->getSize(),
            ]);
        } catch (\Throwable $e) {
            Storage::disk(TarefaAnexo::DISCO)->delete($path);
            throw $e;
        }
    }
}
