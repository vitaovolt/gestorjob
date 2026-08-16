# Ajustes do operador — `gestor-job`

Use entre etapas/fases (ou no meio delas).  
Cada item vira trabalho + candidatura à base central (`educraft-devkit/lessons/`).

## Como registrar

No chat:

```
Use educraft-dev-orchestrator.
ajuste gestor-job
[cole aqui a dica ou o que quer mudar]
```

Ou escreva abaixo e diga `processar ajustes gestor-job`.

## Fila

| ID | Data | Etapa/fase | Pedido | Status | Foi para KB? |
|----|------|------------|--------|--------|--------------|
| A1 | 2026-08-13 | e4/f0 | Adequar pasta ao skeleton Educraft + remoto GitHub | feito | sim: lessons 2026-08-13-gestor-job-e1 |
| A2 | 2026-08-14 | e4/f3 | Kanban com arraste e solte entre colunas | feito | não (produto) |
| A3 | 2026-08-14 | e4/f3 | Hover/cursor nos cards (e no resto da UI) | feito | sim: tips/2026-08-14-gestor-job-affordance-hover-timer.md |
| A4 | 2026-08-14 | e4/f3 | Clicar Produção não mostra timer (relógio invisível) | feito | sim: mesma tip A3 |
| A5 | 2026-08-14 | e4/f3 | Toast cobre + Tarefa/Sair; precisa ser mais suave | feito | já existia: tips/2026-08-06-nfse-empresa-toast-visivel.md (acrescentei: não overlay no chrome) |
| A6 | 2026-08-14 | e4/f3 | Com o drawer do card aberto o toast fica atrás e pouco visível | feito | já existia: tips/2026-08-06-nfse-empresa-toast-visivel.md (portal + z-index acima do overlay) |
| A7 | 2026-08-14 | e4/f3 | Play/pause no relógio; play retoma a fase; outra fase começa do 0 | feito | não (produto) |
| A8 | 2026-08-14 | e4/f4 | Campos BR já nascem com máscara (BRL, data, tel, CPF, CNPJ novo, e-mail) | feito | sim: tips/2026-08-14-gestor-job-mascaras-padrao.md (+ UX-PROTOTIPO + skill e4; CNPJ alfanumérico já existia) |
| A9 | 2026-08-14 | e4/f4 | Data: máscara e calendário para selecionar | feito | já existia: tips/2026-08-14-gestor-job-mascaras-padrao.md (acrescentei: DD/MM + calendário) |
| A10 | 2026-08-15 | e4/f4 | CRUD: botão Editar visível na lista (não só clicar no nome) | feito | sim: tips/2026-08-15-gestor-job-crud-editar-visivel.md (+ UX-PROTOTIPO + skill e4) |
| A11 | 2026-08-15 | e4/f4 | Excluir visível em todos os CRUDs (lista, admin/gerente) | feito | já existia: tips/2026-08-05-financeiro-pessoal-crud-delete.md (acrescentei: Excluir na lista, não só no form) |
| A12 | 2026-08-15 | e4/f4 | Admin redefine senha do colaborador; usuário troca a própria | feito | sim: tips/2026-08-15-gestor-job-senha-admin-e-propria.md |
| A13 | 2026-08-15 | e4/f4 | Upload .bin grande fica em Processando… sem mensagem | feito | sim: tips/2026-08-15-gestor-job-upload-validar-antes.md |
| A14 | 2026-08-15 | e4/f4 | Bloquear qualquer arquivo fora da allowlist (não só .bin) | feito | já existia: tips/2026-08-15-gestor-job-upload-validar-antes.md (acrescentei: allowlist fechada, sem image/*) |
| A15 | 2026-08-15 | e4/f4 | Recuperar senha: e-mail inexistente deve avisar (não “Link enviado”) | feito | sim: tips/2026-08-15-gestor-job-recuperar-senha.md (atualizada) |
| A16 | 2026-08-16 | e4/f6 | Docs runner EC2 = padrão nfse (self-hosted + Deploy Key; sem SSH 22 aberto) | feito | sim: tips/2026-08-16-deploy-self-hosted-deploy-key.md + DEPLOY-GITHUB.md |

Status: `aberto` | `em_andamento` | `feito` | `descartado`

## Notas

- Ajuste de **produto/projeto** → altera artefatos desta pasta
- Ajuste de **processo/framework** → também entra em `lessons/process/MELHORIAS-PENDENTES.md` (se ainda não existir)
