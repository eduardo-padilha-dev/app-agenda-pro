# Autenticação interna com Blade — roteiro de implementação

> Revisado em 14/09/2026. Substitui o plano anterior, que dependia de Fortify e páginas React/Inertia do starter kit. Nenhuma etapa deste roteiro está implementada na base recriada.

**Objetivo:** permitir o acesso da equipe ao Agenda Pro por autenticação de sessão, com páginas Blade e sem cadastro público.

**Base atual:** Laravel 13, model `User`, factory e migration padrão de usuários. A rota `/` renderiza `welcome`; ainda não existem páginas de login, dashboard ou configurações de conta. Fortify não está instalado.

**Referência:** [Design do MVP](../specs/2026-08-28-agenda-pro-multissetorial-design.md).

## Diretrizes

- Usar os recursos de autenticação, sessão, hashing e recuperação de senha do Laravel.
- Renderizar páginas Blade e enviar formulários pelas rotas web, com proteção CSRF, validação e mensagens de sessão.
- Manter login, logout, recuperação, atualização e confirmação de senha no escopo planejado.
- Não disponibilizar cadastro público, verificação obrigatória de email, autenticação de dois fatores ou passkeys no MVP.
- Proteger as páginas internas com autenticação e restringir formulários de login e recuperação a visitantes.
- Manter controllers pequenos e validação de entrada em Form Requests.
- Definir o provisionamento do primeiro usuário interno antes de disponibilizar o acesso; convites e administração da equipe ficam para outro incremento.

## 1. Login, logout e acesso interno

- [ ] Criar testes Feature para exibição do login, credenciais válidas e inválidas, limitação de tentativas e logout.
- [ ] Implementar formulário Blade de login e processamento das credenciais pelo mecanismo de autenticação do Laravel.
- [ ] Regenerar a sessão após autenticação e invalidá-la no logout, renovando o token CSRF.
- [ ] Criar uma página inicial interna em Blade protegida por `auth`.
- [ ] Verificar redirecionamento de visitantes ao login e de usuários autenticados ao destino interno.
- [ ] Testar que rotas de cadastro público não estão disponíveis.

## 2. Recuperação e gestão de senha

- [ ] Criar testes para solicitação de recuperação, token válido, token inválido ou expirado e alteração de senha.
- [ ] Implementar as páginas Blade de solicitação e redefinição de senha com o password broker do Laravel.
- [ ] Implementar atualização de senha autenticada com verificação da senha atual.
- [ ] Implementar confirmação de senha para operações sensíveis que a exigirem.
- [ ] Configurar e verificar o envio de email antes do uso em produção; o `.env.example` atual usa o mailer `log`.
- [ ] Garantir que erros de autenticação e recuperação não exponham detalhes internos ou revelem desnecessariamente a existência de uma conta.

## 3. Validação do incremento

- [ ] Executar os testes de autenticação criados e depois a suíte com `composer test`.
- [ ] Formatar os arquivos PHP alterados com `vendor/bin/pint`.
- [ ] Compilar os assets com `npm run build`.
- [ ] Inspecionar `php artisan route:list` e confirmar a proteção das rotas internas e a ausência de registro público.
- [ ] Revisar os fluxos de formulários, mensagens de erro e navegação em desktop e celular.
- [ ] Revisar o incremento com o autor antes de iniciar o módulo de clientes.

Os nomes de arquivos e rotas serão definidos na implementação seguindo as convenções da base atual. Não executar os exemplos do plano anterior: os arquivos, middlewares e testes específicos do starter kit deixaram de existir.
