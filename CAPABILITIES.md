# Capacidades do projeto — `gestor-job`

Marque cada pack: `nao` | `avaliar` | `sim`  
Catálogo: [../educraft-devkit/capabilities/INDEX.md](../educraft-devkit/capabilities/INDEX.md)

| Pack | Status | Notas (1 linha) |
|------|--------|-----------------|
| offline-sync | nao | Operação web online |
| queues | sim | E-mail de convite, prazo e eventos do Kanban |
| multi-tenant | sim | Núcleo: N agências isoladas + Super Admin |
| realtime | avaliar | Notificações in-app no MVP; websocket só se polling não bastar |
| files | sim | Anexos na tarefa; pasta Drive é URL no cadastro (Fase 3 = sync) |
| fiscal | nao | Fora do produto |
| mobile | nao | Expansão Fase 3 |
| integrations | nao | Drive / Figma / Meta = Fase 3 |
| reporting | sim | Dashboard e margem (Fase 2 do produto; preparar modelo já no F1) |
| custom | nao | |

## Dependências detectadas

- `multi-tenant=sim` ⇒ isolamento por tenant em auth, policies e queries desde F1/F2
- `queues=sim` ⇒ mailer + jobs no F0/F4 (convite, prazo)
- `files=sim` ⇒ upload de anexo no detalhe da tarefa (MVP)
- `reporting=sim` ⇒ horas × custo/hora × fee; UI completa na Fase 2 do produto

## Preenchido em

- Etapa: e1 (retrofit 2026-08-13)
- Data: 2026-08-13
