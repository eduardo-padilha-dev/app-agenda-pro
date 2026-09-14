# Agenda Pro multissetorial — Design do MVP

> Revisado em 14/09/2026 após a recriação do projeto sem starter kit, com Blade. Este documento descreve o MVP planejado; a base atual contém apenas a estrutura inicial do Laravel.

## 1. Objetivo

O Agenda Pro será uma aplicação web interna para estabelecimentos que oferecem serviços executados por profissionais com hora marcada. O produto não será específico para maquiagem ou cabelo: deverá atender, por exemplo, salões, barbearias, clínicas de estética, consultórios e outros prestadores com dinâmica equivalente.

Cada instalação atenderá exatamente um estabelecimento. Multiempresa, múltiplas unidades e modelo SaaS não fazem parte do MVP. Portanto, o banco não terá uma entidade `Establishment` nem repetirá `establishment_id` nas tabelas.

## 2. Escopo do MVP

O MVP permitirá:

- autenticação de usuários internos, sem registro público;
- cadastro e desativação de clientes, profissionais e serviços;
- associação entre profissionais e serviços;
- definição da disponibilidade semanal de cada profissional;
- criação de bloqueios excepcionais na agenda;
- criação de agendamentos com múltiplos itens;
- atribuição independente de serviço, profissional e intervalo para cada item;
- confirmação, cancelamento, conclusão e ausência;
- visualização semanal da agenda;
- prevenção de conflitos de clientes e profissionais;
- instalação da aplicação como PWA.

Continuam fora do MVP:

- financeiro, pagamentos, PIX e controle de sinal;
- comissões e estoque;
- WhatsApp, SMS e notificações push;
- autoagendamento público e aplicativo nativo;
- funcionamento offline para operações de agenda;
- recursos físicos como salas, cadeiras, quadras e equipamentos;
- múltiplas unidades, multiempresa, marketplace e assinatura SaaS;
- relatórios avançados, fidelidade, pacotes e avaliações.

O status `confirmed` representa somente a confirmação de presença. Ele não representa pagamento ou recebimento de sinal.

## 3. Stack e arquitetura

A aplicação será um monólito modular com Laravel 13, views Blade, Tailwind CSS 4 e JavaScript compilado pelo Vite:

```text
Blade + HTML/CSS/JavaScript
        ↓
    Rotas web
        ↓
 Laravel Controllers
        ↓
 Actions do domínio
        ↓
      Eloquent
        ↓
    PostgreSQL
```

O ambiente inicial usa SQLite. PostgreSQL é o banco planejado para a agenda e para validar o protocolo de concorrência descrito na seção 10; sua configuração ainda será adicionada.

As páginas serão renderizadas no servidor, com formulários web e redirecionamentos que apresentam mensagens e erros de validação. A autenticação interna ainda será implementada com os recursos de sessão do Laravel, sem presumir funcionalidades de um starter kit ou Fortify instalado.

O frontend interno não terá uma API REST criada apenas para consumo próprio. Futuras integrações poderão reutilizar as mesmas Actions por meio de controllers de API.

Os limites conceituais serão:

```text
Identity
└── autenticação e usuários internos

Clients
└── clientes atendidos

Catalog
├── profissionais
├── serviços
└── associação profissional-serviço

Scheduling
├── disponibilidades
├── bloqueios
├── agendamentos
├── itens de agendamento
└── detecção de conflitos
```

As convenções do Laravel serão preservadas. Actions serão agrupadas por caso de uso, sem criar uma infraestrutura de DDD completo:

```text
app/
├── Actions/
│   ├── Clients/
│   ├── Professionals/
│   ├── Services/
│   └── Appointments/
├── Enums/
├── Models/
└── Http/
    ├── Controllers/
    └── Requests/
```

## 4. Modelo de domínio

### 4.1 Identidades distintas

- `User`: pessoa autorizada a entrar no sistema.
- `Professional`: pessoa que executa serviços. Não precisa possuir um `User` no MVP.
- `Client`: pessoa que recebe o atendimento. Não possui login no MVP.

Essas entidades não serão unificadas.

### 4.2 Entidades e atributos

```text
Client
- name
- phone
- email opcional
- notes opcional
- active

Professional
- name
- phone opcional
- email opcional
- notes opcional
- active

Service
- name
- description opcional
- duration_minutes
- price_cents
- active

ProfessionalService
- professional_id
- service_id

ProfessionalAvailability
- professional_id
- weekday
- starts_at
- ends_at

ScheduleBlock
- professional_id
- starts_at
- ends_at
- reason opcional

Appointment
- client_id
- status
- notes opcional
- cancelled_at opcional
- cancellation_reason opcional
- timestamps

AppointmentItem
- appointment_id
- service_id
- professional_id
- starts_at
- ends_at
- service_name
- duration_minutes
- price_cents
```

Relacionamentos:

```text
Professional N ── N Service
Client       1 ── N Appointment
Appointment  1 ── N AppointmentItem
Professional 1 ── N AppointmentItem
Service      1 ── N AppointmentItem
Professional 1 ── N ProfessionalAvailability
Professional 1 ── N ScheduleBlock
```

Valores monetários serão armazenados em centavos inteiros. O `AppointmentItem` manterá snapshots do nome, da duração e do preço do serviço, preservando o histórico mesmo após alterações no catálogo.

Clientes, profissionais e serviços referenciados pelo histórico serão desativados em vez de excluídos fisicamente. O horário geral de um agendamento será derivado do menor `starts_at` e do maior `ends_at` entre seus itens.

## 5. Disponibilidade

Cada profissional terá sua própria disponibilidade semanal recorrente. Poderão existir vários intervalos no mesmo dia, permitindo pausas como horário de almoço.

Não haverá expediente geral do estabelecimento. A disponibilidade efetiva será:

```text
disponibilidade semanal do profissional
− bloqueios excepcionais
− agendamentos que ocupam o horário
= horários disponíveis
```

Os agendamentos serão persistidos como instantes em UTC e apresentados no fuso configurado para a instalação. Disponibilidades semanais representam horários locais recorrentes do estabelecimento.

## 6. Agendamentos e conflitos

Um `Appointment` agrupa um ou mais `AppointmentItem`. Cada item possui intervalo próprio, serviço e profissional. Os itens não precisam ser contíguos, mas não podem se sobrepor para o mesmo cliente.

Um novo item será válido somente se:

1. o cliente estiver ativo;
2. o serviço e o profissional estiverem ativos;
3. o profissional estiver associado ao serviço;
4. o fim for posterior ao início;
5. a duração do intervalo corresponder à duração padrão do serviço;
6. o intervalo estiver dentro de uma faixa de disponibilidade do profissional;
7. o intervalo não cruzar um bloqueio do profissional;
8. o profissional não possuir outro item bloqueante sobreposto;
9. o cliente não possuir outro item bloqueante sobreposto;
10. o item não se sobrepuser a outro item da mesma solicitação para o cliente.

Intervalos serão tratados como semiabertos, `[início, fim)`. Portanto, `14:00–15:00` e `15:00–16:00` são adjacentes e não conflitantes.

Profissionais e clientes diferentes podem ter atendimentos simultâneos. A reserva de salas ou outros recursos físicos não será considerada.

## 7. Status e transições

`AppointmentStatus` terá os valores:

```text
scheduled
confirmed
completed
cancelled
no_show
```

Transições válidas:

```text
scheduled → confirmed
scheduled → completed
scheduled → cancelled
scheduled → no_show
confirmed → completed
confirmed → cancelled
confirmed → no_show
```

`completed`, `cancelled` e `no_show` serão estados finais no MVP. Agendamentos cancelados e marcados como ausência não bloqueiam novos horários. Agendamentos concluídos permanecem bloqueantes para representar corretamente o histórico realizado.

Ao reagendar um agendamento `confirmed`, seu status voltará para `scheduled`, pois o cliente deverá confirmar os novos horários.

## 8. Casos de uso

O núcleo de agenda terá as seguintes Actions:

```text
CreateAppointment
RescheduleAppointment
ConfirmAppointment
CancelAppointment
CompleteAppointment
MarkAppointmentAsNoShow
```

`CreateAppointment` validará e persistirá o agendamento completo em uma única transação.

`RescheduleAppointment` receberá a nova composição completa dos itens, ignorará os itens antigos do próprio agendamento durante a consulta de conflitos e executará novamente todas as validações. A operação será atômica: todos os itens serão atualizados ou nenhuma alteração será persistida.

## 9. Responsabilidades das camadas

- Views Blade apresentam a agenda, formulários e erros; JavaScript apoia interações quando necessário. A interface não decide regras de negócio.
- Form Requests validam forma, tipos, campos obrigatórios e estrutura da entrada.
- Controllers coordenam a requisição e delegam para uma Action.
- Actions aplicam regras de negócio, controlam transações e persistência do caso de uso.
- Models mantêm relações, casts e consultas simples, sem acumular fluxos completos.
- Exceções de domínio representam indisponibilidade, conflito e transições inválidas.

Erros esperados serão associados aos campos ou itens relevantes e apresentados em linguagem útil. Erros inesperados serão registrados pelo Laravel e não terão detalhes internos expostos ao usuário.

## 10. Concorrência

Consultar conflitos dentro de uma transação não é suficiente, pois duas requisições simultâneas podem observar o mesmo horário como livre.

As Actions de criação e reagendamento bloquearão para atualização o cliente e todos os profissionais envolvidos antes de consultar conflitos. Profissionais serão bloqueados em ordem crescente de identificador, sempre depois do cliente, para manter uma ordem determinística e reduzir o risco de deadlock.

Todas as operações que alteram a ocupação da agenda deverão seguir o mesmo protocolo. Depois dos bloqueios, a Action fará as consultas de sobreposição e persistirá o agregado dentro da mesma transação.

## 11. Interface da agenda

A navegação principal terá:

```text
Agenda
Agendamentos
Clientes
Profissionais
Serviços
```

No desktop, a Agenda exibirá a semana completa:

- os dias da semana serão colunas;
- os horários serão linhas no eixo vertical;
- os cartões indicarão cliente, profissional, serviço e intervalo;
- cores identificarão profissionais;
- haverá filtro por profissional;
- a grade permitirá navegar entre semanas e retornar à semana atual.

No celular, serão exibidos os horários de um dia por vez, com navegação entre datas. Não serão comprimidas sete colunas em uma tela pequena.

O MVP não terá arrastar e soltar. Criação e reagendamento passarão por formulários explícitos, preservando validações e acessibilidade.

## 12. PWA

A mesma aplicação Laravel com Blade será instalável como PWA. Esses recursos ainda não estão implementados. O escopo inclui:

- Web App Manifest com nome, ícones, cores e modo `standalone`;
- service worker para arquivos estáticos versionados;
- tela de indisponibilidade quando não houver rede;
- aviso ao usuário quando uma nova versão estiver disponível;
- HTTPS em produção;
- interface responsiva para desktop e dispositivos móveis.

Estratégia de rede:

```text
arquivos estáticos versionados → cache permitido
navegações de leitura          → rede primeiro, fallback offline
operações que alteram agenda   → somente rede
dados autenticados             → sem cache persistente indiscriminado
```

Criação, reagendamento, cancelamento e outras mutações não funcionarão offline nem serão enfileiradas. Isso impede que uma reserva feita sobre dados desatualizados seja sincronizada após outro usuário ocupar o horário.

Notificações push, sincronização em segundo plano, banco local e atualização automática silenciosa ficam fora do MVP.

## 13. Estratégia de testes

Testes de regras e Actions cobrirão:

- conflito do profissional;
- conflito do cliente;
- conflito entre itens da mesma solicitação;
- aceitação de intervalos adjacentes;
- liberação de horário por cancelamento e ausência;
- respeito a disponibilidades e bloqueios;
- associação entre profissional e serviço;
- snapshot de nome, duração e preço;
- transições válidas e inválidas;
- retorno de `confirmed` para `scheduled` após reagendamento;
- atomicidade do reagendamento.

Testes Feature cobrirão autenticação, autorização das rotas, validação HTTP, persistência do agregado, mensagens de erro e indisponibilidade do registro público.

Testes da PWA verificarão a geração do manifesto e do service worker, a estratégia de cache configurada e a presença da experiência de indisponibilidade. A interface semanal terá testes focados nas transformações de dados e nos fluxos críticos, sem testar detalhes internos de bibliotecas visuais.

## 14. Ordem de implementação

1. Implementar autenticação interna com Blade, sem registro público.
2. Implementar clientes.
3. Implementar profissionais.
4. Implementar serviços e associação com profissionais.
5. Implementar disponibilidades recorrentes.
6. Implementar bloqueios de agenda.
7. Modelar agendamentos, itens e status.
8. Implementar e testar a detecção de conflitos.
9. Implementar criação de agendamento.
10. Implementar transições de status.
11. Implementar reagendamento.
12. Construir a visualização semanal e sua adaptação diária para celular.
13. Adicionar os recursos de PWA.
14. Refinar dashboard, navegação e identidade visual.

Cada etapa seguirá o ciclo teste primeiro, implementação mínima, verificação e revisão antes da próxima etapa. O desenvolvimento será conduzido como aprendizado: o autor do projeto implementará cada incremento e usará revisão e orientação para compreender as decisões, em vez de receber funcionalidades completas prontas.
