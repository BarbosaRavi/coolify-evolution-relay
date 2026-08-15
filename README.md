# Coolify Evolution Relay

Recebe eventos de **deploy do Coolify** e de **push do GitHub** e entrega o resumo em um **grupo do WhatsApp**, através da [Evolution API](https://doc.evolution-api.com/).

```
Coolify  ──POST /api/deploy/hook/{secret}──┐
                                           ├──▶ valida ──▶ formata ──▶ fila ──▶ Evolution API ──▶ grupo do WhatsApp
GitHub   ──POST /api/github/push───────────┘
```

Os dois webhooks são chamados por máquinas, não por pessoas: não usam JWT nem sessão. O Coolify se autentica pelo segredo na URL, o GitHub pela assinatura HMAC do corpo da requisição.

A requisição HTTP apenas **valida e enfileira**. O envio para o WhatsApp acontece em um job da fila, com 3 tentativas e backoff de 10s/30s/60s. Isso evita que o GitHub ou o Coolify fiquem esperando a Evolution API responder — eles têm timeout curto e reenviam o evento se demorar.

---

## O que ele faz

**Deploys do Coolify**

- Aceita o payload no formato Slack (o Coolify não tem webhook próprio, então usamos o canal de notificação Slack dele).
- Detecta se o deploy deu certo ou não pelo título e, se ele for inconclusivo, pela cor do anexo — e marca com ✅ ou ❌. Quando não dá para saber, não coloca emoji nenhum, em vez de assumir sucesso.
- Descarta o link dos logs e qualquer campo que não seja projeto/ambiente, para a mensagem não vazar URL interna no grupo.

**Pushes do GitHub**

- Valida a assinatura `X-Hub-Signature-256`.
- Só notifica as branches configuradas (padrão: `main` e `master`).
- Lista até 10 commits, com hash curto e a primeira linha da mensagem.
- Aceita e ignora o evento `ping` que o GitHub dispara ao salvar o webhook.

**Roteamento por projeto (opcional)**

- Cada projeto cadastrado pode ter seu **próprio grupo do WhatsApp**, suas **próprias branches** e chaves para **silenciar** push ou deploy separadamente.
- Eventos de projetos **não cadastrados continuam sendo enviados** para o grupo padrão. O cadastro é uma tabela de exceções, não uma lista de permissão.

**API administrativa**

- Login por JWT, cargos e permissões (`spatie/laravel-permission`).
- CRUD de projetos com soft delete e restauração.
- Documentação OpenAPI gerada automaticamente pelo Scramble em `/docs/api`.

---

## Requisitos

| | |
|---|---|
| PHP | 8.3+ |
| Banco | PostgreSQL |
| Evolution API | uma instância já conectada a um número de WhatsApp |
| Coolify | opcional, só para as notificações de deploy |

---

## Configuração

Todas as variáveis abaixo vão no `.env` (localmente) ou nas variáveis de ambiente do serviço (no Coolify). Copie o ponto de partida com `cp .env.example .env`.

### Evolution API / WhatsApp

| Variável | Descrição |
|---|---|
| `EVOLUTION_URL` | URL base da sua Evolution API, sem barra no final. Ex.: `https://evo.seudominio.com.br` |
| `EVOLUTION_API_KEY` | Chave enviada no header `apikey`. |
| `EVOLUTION_INSTANCE` | Nome da instância conectada ao WhatsApp. Padrão: `deploys` |
| `WHATSAPP_GROUP_JID` | Grupo padrão que recebe tudo que não tiver projeto com grupo próprio. Termina em `@g.us`. |

**Como descobrir o JID do grupo:** liste os grupos da instância na Evolution API e copie o `id` do grupo desejado.

```bash
curl -s "$EVOLUTION_URL/group/fetchAllGroups/$EVOLUTION_INSTANCE?getParticipants=false" \
  -H "apikey: $EVOLUTION_API_KEY"
```

Antes de subir a aplicação, confirme que essa parte funciona sozinha — se este `curl` não entregar mensagem, nada do resto vai funcionar:

```bash
curl -X POST "$EVOLUTION_URL/message/sendText/$EVOLUTION_INSTANCE" \
  -H "apikey: $EVOLUTION_API_KEY" -H 'Content-Type: application/json' \
  -d '{"number":"SEU_JID@g.us","text":"teste"}'
```

### Segredos dos webhooks

| Variável | Descrição |
|---|---|
| `DEPLOY_WEBHOOK_SECRET` | Vai na **URL** do webhook do Coolify. É a única credencial daquele endpoint. |
| `GITHUB_WEBHOOK_SECRET` | Usado para conferir a assinatura HMAC do GitHub. O mesmo valor é colado na configuração do webhook no GitHub. |

Gere cada um com:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

> O segredo do Coolify aparece na URL e, por isso, acaba registrado em logs de acesso e de proxy. Trate-o como menos sigiloso que o do GitHub e troque-o periodicamente.

### Autenticação e administrador

| Variável | Descrição |
|---|---|
| `JWT_SECRET` | Gerado por `php artisan jwt:secret`. |
| `JWT_TTL` | Validade do token em minutos. Padrão: `60` |
| `JWT_REFRESH_TTL` | Janela de renovação em minutos. Padrão: `20160` (14 dias) |
| `DEFAULT_ADMIN_NAME` / `DEFAULT_ADMIN_EMAIL` / `DEFAULT_ADMIN_PASSWORD` | Administrador criado pelo seeder. **Sem a senha preenchida, nenhum administrador é criado.** |

### Banco e fila

| Variável | Descrição |
|---|---|
| `DB_CONNECTION` … `DB_PASSWORD` | Conexão PostgreSQL. |
| `QUEUE_CONNECTION` | Use `database` em produção, junto com um worker rodando. Veja o aviso em [Deploy](#deploy). |

### Branches padrão

Não é variável de ambiente: a lista global fica em [`config/github.php`](config/github.php).

```php
'branches' => ['main', 'master'],
```

Para mudar isso só em um projeto, cadastre o projeto com o campo `branches` — assim a alteração é feita em tempo de execução, sem precisar de novo deploy.

---

## Instalação local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# preencha o .env (Evolution, segredos, banco, DEFAULT_ADMIN_PASSWORD)

php artisan migrate
php artisan db:seed          # cria os cargos e o administrador padrão
php artisan permission:sync  # sincroniza as permissões de config/permission_sync.php

php artisan serve
php artisan queue:work --queue=whatsapp   # em outro terminal
```

---

## Deploy

O [`Dockerfile`](Dockerfile) tem dois estágios: o `composer:2` instala as dependências de produção e gera o autoloader otimizado; a imagem final é `dunglas/frankenphp:php8.4` (Caddy + PHP em um binário só, com HTTPS automático).

O [`docker/entrypoint.sh`](docker/entrypoint.sh) roda a cada start: cria os diretórios de storage, ajusta permissões e executa `config:cache`, `route:cache`, `migrate --force` e `permission:sync`. O healthcheck bate em `/up`.

**No Coolify:** aponte para este repositório, escolha build por Dockerfile e cadastre todas as variáveis acima em *Environment Variables*.

### ⚠️ Worker da fila

O `entrypoint.sh` sobe **apenas o servidor web**. Como o envio das mensagens é um job (`SendWhatsappMessageJob`), sem um worker as mensagens ficam paradas na tabela `jobs` e nunca chegam ao WhatsApp.

Suba um segundo serviço no Coolify a partir da mesma imagem, com:

```bash
php artisan queue:work --queue=whatsapp --tries=3
```

Alternativa mais simples para começar: deixar `QUEUE_CONNECTION=sync`, o que faz o job rodar dentro da própria requisição. Funciona, mas você perde as tentativas automáticas e o webhook passa a esperar a Evolution API responder — exatamente o que a fila existe para evitar.

### Primeiro administrador em produção

O entrypoint **não** roda o seeder. Depois do primeiro deploy, execute uma vez dentro do contêiner:

```bash
php artisan db:seed --class=Database\\Seeders\\DefaultAdminSeeder
```

O seeder não faz nada se a senha não estiver definida ou se o e-mail já existir, então é seguro repetir.

---

## Ligando os webhooks

### Coolify

*Notifications → Slack → Enabled*, e use como webhook URL:

```
https://SEU_DOMINIO/api/deploy/hook/SEU_DEPLOY_WEBHOOK_SECRET
```

Marque os eventos que quiser receber (deploy com sucesso, deploy com falha, status do contêiner). O Coolify manda no formato Slack e a aplicação traduz.

### GitHub

*Settings → Webhooks → Add webhook*, no repositório ou na organização:

| Campo | Valor |
|---|---|
| Payload URL | `https://SEU_DOMINIO/api/github/push` |
| Content type | `application/json` |
| Secret | o mesmo valor de `GITHUB_WEBHOOK_SECRET` |
| Events | *Just the push event* |

Ao salvar, o GitHub dispara um `ping`. A aplicação responde `200` e ignora — o ✓ verde em *Recent Deliveries* confirma que a assinatura está correta.

---

## Endpoints

### Webhooks (sem autenticação por token)

| Método | Rota | Autenticação |
|---|---|---|
| `POST` | `/api/deploy/hook/{secret}` | segredo na URL |
| `POST` | `/api/github/push` | HMAC SHA-256 no header `X-Hub-Signature-256` |

### Autenticação

| Método | Rota | |
|---|---|---|
| `POST` | `/api/auth/login` | devolve `token`, dados do usuário e permissões |
| `POST` | `/api/auth/refresh-token` | troca um token dentro da janela de refresh |
| `POST` | `/api/auth/me` | usuário do token (requer `Authorization: Bearer <token>`) |

### Projetos

Todas exigem token e a permissão correspondente.

| Método | Rota | Permissão |
|---|---|---|
| `GET` | `/api/project` | `project.view` |
| `GET` | `/api/project/{id}` | `project.view` |
| `POST` | `/api/project` | `project.create` |
| `PUT` | `/api/project/{id}` | `project.update` |
| `DELETE` | `/api/project/{id}` | `project.delete` |
| `PATCH` | `/api/project/restore/{id}` | `project.restore` |
| `DELETE` | `/api/project/destroy/{id}` | `project.destroy` |

A listagem aceita `page`, `per_page`, `search` (nome, repositório ou projeto do Coolify) e `trashed`.

Toda resposta segue o mesmo envelope:

```json
{ "error": false, "message": "Projetos listados com sucesso!", "data": {} }
```

**Documentação interativa:** `/docs/api`. Por padrão o Scramble libera esse acesso apenas em ambiente local; para expor em produção, defina o gate `viewApiDocs`.

---

## Cadastrando um projeto

O cadastro só é necessário quando você quer **fugir do padrão**. Sem nenhum projeto cadastrado, tudo é entregue no `WHATSAPP_GROUP_JID` usando as branches de `config/github.php`.

```bash
curl -X POST https://SEU_DOMINIO/api/project \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Loja",
    "github_repository": "minhaorg/loja",
    "coolify_project": "loja",
    "whatsapp_group_jid": "120363000000000000@g.us",
    "notify_push": true,
    "notify_deploy": true,
    "branches": ["main", "develop"]
  }'
```

| Campo | Para que serve |
|---|---|
| `github_repository` | chave de busca do webhook do GitHub, no formato `usuario/repositorio`. Único. |
| `coolify_project` | chave de busca do webhook do Coolify, comparada com o campo de projeto do payload. |
| `whatsapp_group_jid` | grupo próprio deste projeto. Vazio = grupo padrão. |
| `notify_push` / `notify_deploy` | silenciam um tipo de evento sem precisar remover o webhook na origem. |
| `branches` | lista de branches deste projeto. Vazio = lista global. |

É obrigatório informar `github_repository` e/ou `coolify_project`: sem pelo menos um deles, nenhum evento consegue encontrar o projeto e o cadastro seria invisível.

**Comportamento intencional:** um repositório sem cadastro **notifica mesmo assim**, no grupo padrão. Isso é uma escolha de projeto — para um sistema cujo trabalho é avisar quando algo quebra, receber uma mensagem no grupo errado é bem menos grave que o silêncio de um deploy falhando sem ninguém ver.

---

## Testando

Simular uma **falha de deploy** sem quebrar nada de verdade:

```bash
SECRET=$(grep '^DEPLOY_WEBHOOK_SECRET=' .env | cut -d= -f2- | tr -d "'\"")

curl -sS -X POST "http://localhost:8000/api/deploy/hook/$SECRET" \
  -H 'Content-Type: application/json' \
  -d '{
    "attachments": [{
      "title": "Deployment failed",
      "color": "#ff0000",
      "text": "Deployment failed.\n<https://coolify.local/logs/1|Deployment Logs>",
      "fields": [
        {"title": "Project",         "value": "loja"},
        {"title": "Environment",     "value": "production"},
        {"title": "Deployment Logs", "value": "https://coolify.local/logs/1"}
      ]
    }]
  }'
```

Mensagem esperada no grupo:

```
❌ Deployment failed

Deployment failed.

*Project:* loja
*Environment:* production
```

Para conferir a formatação **sem mandar mensagem**, use `QUEUE_CONNECTION=database`, não suba o worker e leia o texto direto da fila:

```bash
psql "$DB_DATABASE" -c "select payload from jobs order by id desc limit 1;"
```

Para validar o caminho real de falha, quebre um build de propósito: adicione `RUN exit 1` ao `Dockerfile`, faça o deploy, confira a mensagem e reverta. Vale a pena fazer pelo menos uma vez — as palavras que marcam falha estão em `DeployService::FAILURE_WORDS` e só a mensagem real do seu Coolify confirma se elas batem.

---

## Solução de problemas

| Sintoma | Causa provável |
|---|---|
| `403` no webhook do Coolify | segredo da URL diferente de `DEPLOY_WEBHOOK_SECRET`, ou cache de config antigo (`php artisan config:clear`) |
| `403` no webhook do GitHub | `GITHUB_WEBHOOK_SECRET` diferente do segredo cadastrado no GitHub |
| `422` no webhook | o payload real não bate com as regras de validação. O corpo da resposta diz qual campo — copie o payload de *Recent Deliveries* (GitHub) ou dos logs do Coolify e ajuste |
| `200` mas nada chega no WhatsApp | worker da fila parado; ou `notify_push`/`notify_deploy` desligado; ou o projeto aponta para outro grupo; ou a branch está fora da lista |
| `could not find driver` no boot | falta `pdo_pgsql` na imagem — a base do FrankenPHP só traz `pdo_sqlite` |
| Contêiner cai no `config:cache` | algum `config/*.php` retornando **objeto**. Valores de config precisam ser escalares ou arrays |
| Mensagem sem emoji de status | o título do Coolify não bateu com nenhuma palavra conhecida e a cor era inconclusiva — comportamento proposital, para não marcar falha como sucesso |

Logs: `storage/logs/laravel.log`, ou `php artisan pail` para acompanhar em tempo real.

---

## Onde mexer

| Quero mudar… | Arquivo |
|---|---|
| Formato da mensagem de deploy, emojis, campos exibidos | [`app/Services/Deploy/DeployService.php`](app/Services/Deploy/DeployService.php) |
| Formato da mensagem de push, limite de commits | [`app/Services/Github/GithubService.php`](app/Services/Github/GithubService.php) |
| Envio para a Evolution API, tentativas, timeout | [`app/Jobs/SendWhatsappMessageJob.php`](app/Jobs/SendWhatsappMessageJob.php) |
| Validação e autenticação dos webhooks | `app/Http/Requests/Deploy/`, `app/Http/Requests/Github/` |
| Branches padrão | [`config/github.php`](config/github.php) |
| Lista de permissões | [`config/permission_sync.php`](config/permission_sync.php) + `php artisan permission:sync` |

O fluxo segue sempre a mesma sequência: **Rota → FormRequest → Controller → Service → Job**. A autenticação dos webhooks mora no `authorize()` do FormRequest, a formatação da mensagem no Service, e a chamada HTTP externa no Job.
