# Lições — E4 / F6

**Slug:** gestor-job  
**Etapa / fase:** e4 / F6  
**Data:** 2026-08-16  
**Rodada:** 1

## O que funcionou

- Health com `checks.database` (200/503) como smoke pós-deploy único.
- CI hosted + Deploy **self-hosted** (padrão nfse): Deploy Key em `~/.ssh/gestorjob_github`; SSH 22 só IP admin.
- Catalog + `DEPLOY-GITHUB.md` alinhados ao nfse-empresa.

## O que falhou / atrito

- (nenhum nesta rodada — implementação inicial)

## Melhoria de processo (acionável)

- F6 sempre: health com DB + artefatos workflow assertados no PHPUnit + doc `docs/DEPLOY.md` com curl.

## Padrão candidato (standards/catalog?)

- Já coberto por `DEPLOY-GITHUB.md`; tip: health database check.

## Tags

`#e4` `#f6` `#deploy` `#ci` `#health`

## Sync KB

- [x] Ficha: `educraft-devkit/lessons/2026-08-16-gestor-job-e4-f6.md`
- [x] Tip: `tips/2026-08-16-gestor-job-health-database.md`
- [x] Tip: `tips/2026-08-16-deploy-self-hosted-deploy-key.md`
