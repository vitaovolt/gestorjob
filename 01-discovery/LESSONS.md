# Lições — rodada

**Slug:** gestor-job  
**Etapa / fase:** e1  
**Data:** 2026-08-13  
**Rodada:** retrofit (protótipos já existiam)

## O que funcionou

- Lo-fi + hi-fi já respondiam o brief: perfis, MVP, fórmula de margem, timer.
- Decisões da proposta (“já fechadas”) equivalem a P0 resolvidas.

## O que falhou / atrito

- Projeto nasceu fora do skeleton (`GestorJob/*.html` na raiz). Orchestrator `iniciar` não rodou; intake formal não existia.
- Logos estavam em `assets/brand/` na raiz, não em `03-hifi/assets/brand/`.

## Melhoria de processo (acionável)

- Se um produto já tem HTML de E2/E3, `iniciar` deve **retrofit** (mover para pastas canônicas + preencher brief a partir do HTML), não recomeçar E1 do zero.

## Padrão candidato (standards/catalog?)

- Catalog `gestorjob.md`: passar de “só referência HTML” para “projeto ativo no skeleton + ainda é referência de estrutura”.

## Tags

`#e1` `#multi-tenant` `#queues` `#files` `#reporting`

## Sync KB

- [x] Ficha em `educraft-devkit/lessons/2026-08-13-gestor-job-e1.md`
- [x] Linha em `lessons/INDEX.md`
- [x] Item em `MELHORIAS-PENDENTES.md`
