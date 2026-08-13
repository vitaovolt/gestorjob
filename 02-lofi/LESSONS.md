# Lições — rodada

**Slug:** gestor-job  
**Etapa / fase:** e2  
**Data:** 2026-08-13  
**Rodada:** retrofit

## O que funcionou

- HTML único com tabs (visão, fluxos, Kanban, detalhe, timesheet, dashboard, cadastros, permissões) cobre todos os perfis.
- Atraso como badge (não coluna) e timer com fases já estavam explícitos — virou regra do kit.

## O que falhou / atrito

- Arquivo vivia na raiz (`gestor-job-fluxo-prototipo.html`); o skeleton espera `02-lofi/prototipo-lofi.html`.
- Links do kit apontavam para o path antigo.

## Melhoria de processo (acionável)

- Manter redirect na raiz para não quebrar bookmarks; atualizar `standards/UX-PROTOTIPO.md` e `catalog/gestorjob.md` para o path canônico.

## Padrão candidato (standards/catalog?)

- Já é o padrão E2. Só o path muda.

## Tags

`#e2` `#lo-fi` `#multi-tenant` `#kanban`

## Sync KB

- [x] Ficha em `educraft-devkit/lessons/2026-08-13-gestor-job-e2.md`
- [x] Linha em `lessons/INDEX.md`
- [x] Melhoria: path canônico + redirect (ver E1)
