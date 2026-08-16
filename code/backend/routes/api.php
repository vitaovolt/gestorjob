<?php

use App\Http\Controllers\Api\AnexoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ColaboradorController;
use App\Http\Controllers\Api\ConfiguracaoController;
use App\Http\Controllers\Api\ConviteController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MargemController;
use App\Http\Controllers\Api\PermissaoController;
use App\Http\Controllers\Api\ServicoController;
use App\Http\Controllers\Api\TarefaController;
use App\Http\Controllers\Api\NotificacaoController;
use App\Http\Controllers\Api\WizardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('/health', HealthController::class);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/auth/recuperar-senha', [AuthController::class, 'solicitarRecuperacao'])->middleware('throttle:recuperar');
    Route::post('/auth/redefinir-senha', [AuthController::class, 'redefinirSenha'])->middleware('throttle:recuperar');
    Route::get('/convites/{token}', [ConviteController::class, 'show'])->middleware('throttle:30,1');
    Route::post('/convites/{token}', [ConviteController::class, 'aceitar'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::post('/auth/senha', [AuthController::class, 'alterarSenha']);

        Route::get('/empresas', [EmpresaController::class, 'index']);
        Route::post('/empresas', [EmpresaController::class, 'store']);
        Route::get('/empresas/{empresa}', [EmpresaController::class, 'showPlataforma']);
        Route::put('/empresas/{empresa}', [EmpresaController::class, 'update']);
        Route::post('/empresas/{empresa}/convite', [EmpresaController::class, 'reenviarConvite']);
        Route::get('/empresa', [EmpresaController::class, 'show']);

        Route::apiResource('clientes', ClienteController::class);
        Route::apiResource('servicos', ServicoController::class)->parameters(['servicos' => 'servico']);

        Route::get('/colaboradores', [ColaboradorController::class, 'index']);
        Route::post('/colaboradores', [ColaboradorController::class, 'store']);
        Route::get('/colaboradores/{colaborador}', [ColaboradorController::class, 'show']);
        Route::put('/colaboradores/{colaborador}', [ColaboradorController::class, 'update']);
        Route::delete('/colaboradores/{colaborador}', [ColaboradorController::class, 'destroy']);

        Route::get('/configuracao', [ConfiguracaoController::class, 'show']);
        Route::put('/configuracao', [ConfiguracaoController::class, 'update']);
        Route::get('/permissoes', [PermissaoController::class, 'show']);
        Route::get('/wizard', [WizardController::class, 'show']);
        Route::post('/wizard/concluir', [WizardController::class, 'concluir']);
        Route::get('/notificacoes', [NotificacaoController::class, 'index']);
        Route::get('/notificacoes/nao-lidas', [NotificacaoController::class, 'naoLidas']);
        Route::post('/notificacoes/ler-todas', [NotificacaoController::class, 'marcarTodas']);
        Route::post('/notificacoes/{notificacao}/lida', [NotificacaoController::class, 'marcarLida']);

        Route::get('/tarefas', [TarefaController::class, 'index']);
        Route::post('/tarefas', [TarefaController::class, 'store']);
        Route::get('/tarefas/{tarefa}', [TarefaController::class, 'show']);
        Route::put('/tarefas/{tarefa}', [TarefaController::class, 'update']);
        Route::delete('/tarefas/{tarefa}', [TarefaController::class, 'destroy']);
        Route::post('/tarefas/{tarefa}/anexos', [AnexoController::class, 'store']);
        Route::get('/tarefas/{tarefa}/anexos/{anexo}/download', [AnexoController::class, 'download']);
        Route::delete('/tarefas/{tarefa}/anexos/{anexo}', [AnexoController::class, 'destroy']);
        Route::post('/tarefas/{tarefa}/timer', [TarefaController::class, 'iniciarTimer']);
        Route::post('/tarefas/{tarefa}/timer/pausar', [TarefaController::class, 'pausarTimer']);
        Route::put('/tarefas/{tarefa}/checklist/{item}', [TarefaController::class, 'checklist']);

        Route::get('/relatorios/margem', [MargemController::class, 'index']);
    });
});
