# Fluxos por perfil — `gestor-job`

HTML: [prototipo-lofi.html](prototipo-lofi.html)

## Perfil: Super Admin (plataforma)

### Fluxo principal

1. Cadastra empresa (nome + plano + limites)
2. Admin da agência recebe convite e define senha
3. Acompanha tenants (ativo / trial)

### Fluxos alternativos / erros

- Convite expirado → reenviar
- Limite de seats → bloquear novo usuário no tenant

## Perfil: Admin da agência

### Fluxo principal — onboarding

1. Aceita convite
2. Wizard: Serviços → Equipe → Clientes → Feriados → Permissões
3. Cai no Kanban vazio

### Fluxo principal — operação

1. Garante cadastros (cliente com fee, colaborador com custo/hora, serviço com checklist)
2. Cria tarefa ou deixa recorrência gerar cards
3. Vê custo acumulado no drawer e, na Fase 2, margem no dashboard

### Fluxos alternativos / erros

- Cliente sem fee → margem não calcula (alertar no cadastro)
- Colaborador sem custo/hora → timer registra hora, custo fica 0 até corrigir

## Perfil: Gerente

### Fluxo principal

1. Opera o Kanban da equipe (criar, mover, prazo)
2. Abre drawer: checklist, responsáveis, custo
3. Lê relatórios de atraso / carga / margem (Fase 2)

### Fluxos alternativos / erros

- Configurações do tenant: acesso parcial (não tudo do Admin)

## Perfil: Colaborador

### Fluxo principal — dia a dia

1. Vê só cards alocados
2. Abre tarefa → timer inicia em Análise
3. Checklist / produção → fase Produção
4. Move para Em revisão → fase Revisão
5. Cliente pede alteração → fase Correção
6. Sai da tela → timer pausa

### Fluxos alternativos / erros

- Sem permissão de criar/excluir (salvo config do tenant)
- Não vê bloco financeiro

## Perfil: Visualizador

### Fluxo principal

1. Lê tarefas alocadas (Kanban/lista)
2. Sem criar, sem timer de execução, sem financeiro
