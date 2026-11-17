# Agenda Pro

O Agenda Pro é uma aplicação web para estabelecimentos que trabalham com serviços executados por profissionais e atendimentos com hora marcada.

O projeto nasceu para atender um estúdio de maquiagem, mas seu domínio foi ampliado para também servir a salões, barbearias, clínicas de estética, consultórios e outros prestadores com uma dinâmica de agenda semelhante.

## Objetivo do MVP

Cada instalação do Agenda Pro atenderá um único estabelecimento. O MVP será um sistema interno, usado pela equipe do negócio, sem cadastro público de usuários ou autoagendamento por clientes.

O escopo planejado contempla:

- autenticação de usuários internos;
- cadastro e desativação de clientes, profissionais e serviços;
- associação entre profissionais e os serviços que executam;
- disponibilidade semanal individual dos profissionais;
- bloqueios excepcionais de agenda;
- agendamentos com múltiplos serviços e profissionais;
- confirmação, cancelamento, conclusão e registro de não comparecimento;
- prevenção de conflitos de horário;
- agenda semanal responsiva;
- instalação como Progressive Web App (PWA).

## Conceitos do domínio

O sistema diferencia três tipos de pessoa:

- **User:** pessoa autorizada a entrar no sistema;
- **Professional:** pessoa que executa serviços, com ou sem uma conta de usuário;
- **Client:** pessoa que recebe o atendimento e não possui login no MVP.

Um agendamento pertence a um cliente e contém um ou mais itens. Cada item registra seu próprio serviço, profissional, horário de início, horário de término, duração e preço.

```text
Appointment
├── Client
├── status
├── observações
└── AppointmentItems
    ├── Service
    ├── Professional
    ├── início e término
    └── snapshots de nome, duração e preço
```

Os snapshots preservam o histórico: alterações futuras no nome, preço ou duração de um serviço não modificam agendamentos já registrados.

## Regras centrais da agenda

- Cada serviço é executado por um profissional.
- Cada item possui um intervalo independente, mas os itens do mesmo cliente não podem se sobrepor.
- Um profissional não pode executar dois atendimentos simultaneamente.
- Um cliente não pode receber dois atendimentos simultaneamente.
- Profissionais e clientes diferentes podem ocupar o mesmo horário.
- O agendamento deve respeitar a disponibilidade semanal do profissional.
- Bloqueios excepcionais tornam o profissional indisponível no período correspondente.
- Intervalos adjacentes são permitidos: `14:00–15:00` e `15:00–16:00` não conflitam.
- Agendamentos cancelados ou marcados como `no_show` deixam de bloquear o horário.
- Preço e duração são definidos no serviço e copiados para o item no momento do agendamento.

Os status previstos são:

```text
scheduled
confirmed
completed
cancelled
no_show
```

`confirmed` representa somente a confirmação de presença. Pagamentos ou sinais não fazem parte desse status nem do MVP.

## Agenda e experiência de uso

No desktop, a agenda é apresentada como uma grade semanal inspirada no Microsoft Teams:

- dias da semana organizados em colunas;
- horários dispostos verticalmente;
- cartões com cliente, profissional, serviço e intervalo;
- cores para identificar profissionais;
- filtro por profissional e navegação entre semanas.

No celular, a interface mostra um dia por vez para preservar a legibilidade.

A aplicação será uma PWA instalável. O primeiro escopo da PWA inclui manifesto, ícones, modo `standalone`, cache de arquivos estáticos, aviso de atualização e uma experiência básica sem conexão. Operações que alteram a agenda dependerão da rede e não serão enfileiradas offline, evitando reservas baseadas em dados desatualizados.

## Arquitetura

A aplicação usa Laravel com páginas renderizadas no servidor por Blade. Formulários enviam requisições às rotas web, e os controllers retornam views ou redirecionamentos com mensagens e erros de validação. JavaScript será usado conforme necessário para interações da interface, sem uma API REST criada apenas para consumo próprio.

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
PostgreSQL 18
```

O código seguirá uma abordagem de monólito modular pragmático, com quatro limites conceituais:

```text
Identity   → autenticação e usuários internos
Clients    → clientes atendidos
Catalog    → profissionais, serviços e suas associações
Scheduling → disponibilidades, bloqueios, agendamentos e conflitos
```

Controllers coordenam requisições, Form Requests validam o formato da entrada e Actions concentram os casos de uso e regras do domínio. Regras importantes não devem ficar em views Blade ou scripts do frontend, controllers ou models excessivamente grandes.

## Stack

- Laravel 13 e Blade, sem starter kit;
- PHP: requisito da aplicação `^8.3`; ambiente atual com PHP 8.5;
- Tailwind CSS 4;
- JavaScript e Vite 8;
- Eloquent ORM e PostgreSQL 18 via Docker Compose;
- Pest 5 e Laravel Pint;
- Laravel Boost para suporte às ferramentas de desenvolvimento.

As dependências de desenvolvimento devem respeitar os requisitos do `composer.lock`; use PHP 8.5 para reproduzir o ambiente atual. O PostgreSQL é executado pelo serviço `postgres` do `compose.yaml`. A suíte atual usa SQLite em memória, configurado no `phpunit.xml`; testes específicos de concorrência deverão usar PostgreSQL. Fortify, React, Inertia e TypeScript não fazem parte da base atual.

## Fora do MVP

Para manter o primeiro ciclo enxuto, não fazem parte do MVP:

- financeiro completo, PIX, pagamentos e controle de sinal;
- comissões e estoque;
- WhatsApp, SMS e notificações push;
- autoagendamento público;
- funcionamento offline para operações de agenda;
- salas, cadeiras, equipamentos e outros recursos físicos;
- múltiplas unidades, multiempresa, marketplace ou assinatura SaaS;
- relatórios avançados, fidelidade, pacotes e avaliações.

## Ambiente de desenvolvimento

### Pré-requisitos

- PHP 8.5 com as extensões exigidas pelas dependências, incluindo `pdo_pgsql` e `pdo_sqlite` (testes);
- Docker com Docker Compose;
- Composer 2;
- Node.js compatível com Vite 8 (22.12+ na linha 22) e npm.

### Configuração inicial

1. Instale as dependências PHP e crie o ambiente, caso `.env` ainda não exista:

    ```bash
    composer install
    cp .env.example .env
    php artisan key:generate
    ```

2. Configure o banco no `.env` (o `.env.example` já contém estes valores para desenvolvimento):

    ```dotenv
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=agendapro
    DB_USERNAME=agendapro
    DB_PASSWORD=agendapro_dev
    BUSINESS_TIMEZONE=America/Sao_Paulo
    ```

    `BUSINESS_TIMEZONE` define o fuso dos bloqueios exibidos e informados nos formulários. Os instantes são armazenados em UTC; a disponibilidade semanal continua representando horários locais recorrentes.

    O `.env` local pode usar uma senha diferente. Laravel e Docker Compose leem a mesma configuração. Inicie o banco e execute as migrations:

    ```bash
    docker compose up -d --wait postgres
    php artisan config:clear
    php artisan migrate
    ```

3. Instale e compile os assets:

    ```bash
    npm install --ignore-scripts
    npm run build
    ```

4. Inicie o ambiente local:

    ```bash
    composer dev
    ```

O script de desenvolvimento usa `php artisan dev`. Para iniciar os processos separadamente, use `php artisan serve` e `npm run dev` em terminais distintos.

Inicie o PostgreSQL antes de executar `composer setup`, que reúne instalação, geração de chave, migrations e build. Use-o na preparação inicial; ele gera uma nova chave mesmo quando já existe um `.env`.

### Comandos do banco

```bash
docker compose up -d --wait postgres  # iniciar e aguardar o banco
docker compose ps                    # consultar o estado
docker compose logs postgres         # consultar logs
docker compose stop postgres         # parar sem remover os dados
php artisan migrate:status           # consultar migrations
```

O Laravel continua rodando na máquina e acessa `127.0.0.1`. Se a porta 5432 estiver ocupada, altere `DB_PORT` no `.env`, recrie o serviço com `docker compose up -d --wait postgres` e execute `php artisan config:clear`.

Os dados persistem no volume `postgres_data`. O comando `docker compose down` preserva esse volume; `docker compose down -v` apaga os dados. Credenciais de inicialização só são aplicadas ao criar o volume: alterar a senha no `.env` depois disso exige também alterá-la no PostgreSQL.

O volume é montado em `/var/lib/postgresql`, conforme a [imagem oficial do PostgreSQL 18](https://hub.docker.com/_/postgres).

### Estrutura atual

- `routes/web.php`: login, logout, cadastros, disponibilidade semanal, bloqueios, criação, reagendamento, lista e transições de agendamentos, agenda real e pré-visualização do protótipo;
- `app/Http/Controllers/CalendarController.php`: consulta e prepara agendamentos, bloqueios, disponibilidades e profissionais do período exibido;
- `resources/views/`: layouts, componentes e telas Blade reais e do protótipo;
- `resources/prototype/data.json`: dados fictícios usados somente na pré-visualização;
- `resources/css/app.css`: entrada de estilos;
- `resources/js/app.js`: entrada JavaScript;
- `app/Models/`: models de usuário, cliente, profissional, serviço, disponibilidade, bloqueio, agendamento e item;
- `app/Enums/AppointmentStatus.php`: status previstos para os agendamentos;
- `app/Domain/Scheduling/AppointmentConflictChecker.php`: regras de disponibilidade, associação e conflitos usadas pelas futuras operações de agendamento;
- `app/Actions/Scheduling/CreateAppointment.php`: cria agendamentos e itens em uma transação, com bloqueios de concorrência e snapshots dos serviços;
- `app/Actions/Scheduling/ChangeAppointmentStatus.php`: aplica as transições permitidas e registra o cancelamento;
- `database/migrations/`: migrations de usuários, cadastros, disponibilidades, bloqueios, agendamentos, cache e filas;
- `tests/Feature/` e `tests/Unit/`: testes em Pest para autenticação, cadastros, disponibilidade, bloqueios e regras dos agendamentos.

## Pré-visualização das telas

Com o ambiente iniciado por `composer dev`, acesse `/prototipo` na aplicação local. A navegação inclui login e recuperação de senha, agenda semanal (diária no celular), agendamentos, clientes, profissionais e serviços.

As views ficam em `resources/views/`, organizadas por tela, com layouts e componentes compartilhados. Os formulários incluem criação e edição, detalhes e reagendamento, disponibilidade semanal e bloqueios. Busca, filtros, navegação e abertura de painéis usam JavaScript simples, sem React ou Inertia.

Essa pré-visualização usa dados fictícios do protótipo original em `prototype_design/`. Seus formulários não autenticam usuários, enviam emails ou salvam alterações. As telas reais de login, clientes, profissionais, serviços e agendamentos usam autenticação, validação e persistência. A disponibilidade semanal e os bloqueios excepcionais são configurados na tela real de profissionais. A agenda em `/calendar` consulta os agendamentos e bloqueios persistidos; os dados fictícios permanecem restritos a `/prototipo`.

## Qualidade e testes

Execute os testes e compile os assets com os comandos disponíveis:

```bash
composer test
npm run build
```

Para formatar código PHP:

```bash
vendor/bin/pint
```

Ainda não há script `composer ci:check`, análise PHPStan ou verificações de ESLint, Prettier e TypeScript configuradas.

O desenvolvimento dos módulos deve seguir ciclos curtos:

```text
entender a regra
→ escrever um teste que falha
→ implementar o mínimo necessário
→ verificar
→ revisar
```

## Ordem planejada de implementação

1. autenticação interna;
2. clientes;
3. profissionais;
4. serviços e associação com profissionais;
5. disponibilidades recorrentes;
6. bloqueios de agenda;
7. agendamentos, itens e status;
8. detecção de conflitos;
9. criação e transições de agendamentos;
10. agenda semanal responsiva;
11. recursos de PWA;
12. identidade visual e refinamentos do dashboard.

## Documentação do projeto

- [Design completo do MVP](docs/superpowers/specs/2026-08-28-agenda-pro-multissetorial-design.md)
- [Roteiro da autenticação interna com Blade](docs/superpowers/plans/2026-08-28-internal-authentication.md)

Esses documentos registram as decisões atuais. Eles devem ser atualizados quando uma regra de negócio ou o escopo do MVP mudar.
