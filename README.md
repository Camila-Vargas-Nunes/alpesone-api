# Alpes One API - Teste Técnico

Esta é uma API Laravel que fornece dados de um recurso integrador e demonstra habilidades em desenvolvimento backend, infraestrutura AWS e DevOps.

## 🚀 Funcionalidades

- **Comando Artisan**: Importa dados da API do Alpes One
- **Verificação Automática**: Checa atualizações a cada hora
- **API REST**: Endpoints CRUD completos com autenticação
- **Validação de Dados**: Verifica mudanças usando hash MD5
- **Testes Automatizados**: Unitários e de integração
- **Paginação**: Suporte a paginação nos endpoints de listagem

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- Composer
- SQLite (configurado por padrão)
- Extensões PHP: xml, curl, zip, gd, bcmath, sqlite3

## 🛠️ Instalação

1. **Clone o repositório**
   ```bash
   git clone <repository-url>
   cd alpesone-api
   ```

2. **Instale as dependências**
   ```bash
   composer install
   ```

3. **Configure o ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure o banco de dados**
   O projeto está configurado para usar SQLite por padrão. O arquivo `database/database.sqlite` será criado automaticamente.

5. **Execute as migrações**
   ```bash
   php artisan migrate
   ```

## 🔧 Configuração

### Variáveis de Ambiente (.env)

```env
APP_NAME="Alpes One API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

# API Key para autenticação (padrão: alpesone-test-2024)
APP_API_KEY=alpesone-test-2024
```

### API Key

A API usa autenticação por API Key. Por padrão, a chave é `alpesone-test-2024`. Você pode alterá-la no arquivo `.env` ou no middleware `ApiAuthentication`.

## 📡 Uso da API

### Autenticação

Todas as requisições devem incluir o header `X-API-Key` ou o parâmetro `api_key`:

```bash
# Usando header
curl -H "X-API-Key: alpesone-test-2024" http://localhost:8000/api/integrator

# Usando parâmetro
curl "http://localhost:8000/api/integrator?api_key=alpesone-test-2024"
```

### Endpoints Disponíveis

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/integrator` | Lista todos os dados com paginação |
| GET | `/api/integrator/{id}` | Obtém dados específicos |
| GET | `/api/integrator/latest` | Obtém os dados mais recentes |
| POST | `/api/integrator` | Cria novos dados |
| PUT | `/api/integrator/{id}` | Atualiza dados existentes |
| DELETE | `/api/integrator/{id}` | Remove dados |

### Parâmetros de Paginação

- `per_page`: Número de itens por página (padrão: 15, máximo: 100)

### Exemplos de Uso

#### Listar dados com paginação
```bash
curl -H "X-API-Key: alpesone-test-2024" \
     "http://localhost:8000/api/integrator?per_page=10"
```

#### Criar novos dados
```bash
curl -X POST \
     -H "X-API-Key: alpesone-test-2024" \
     -H "Content-Type: application/json" \
     -d '{"data":{"key":"value"},"source_url":"https://example.com"}' \
     http://localhost:8000/api/integrator
```

## ⚡ Comandos Artisan

### Importar dados do integrador

```bash
# Importação normal (só importa se houver mudanças)
php artisan integrator:import

# Importação forçada (ignora verificação de mudanças)
php artisan integrator:import --force
```

### Verificar status do agendador

```bash
# Listar tarefas agendadas
php artisan schedule:list

# Executar tarefas agendadas manualmente
php artisan schedule:run
```

## 🧪 Testes

### Executar todos os testes
```bash
php artisan test
```

### Executar testes específicos
```bash
# Testes unitários
php artisan test --testsuite=Unit

# Testes de integração
php artisan test --testsuite=Feature

# Teste específico
php artisan test tests/Feature/IntegratorDataApiTest.php
```

### Cobertura de testes
```bash
# Com Xdebug instalado
php artisan test --coverage
```

## 🚀 Deploy na AWS

### 1. Configuração da Instância EC2

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependências
sudo apt install nginx php7.4-fpm php7.4-mysql php7.4-xml php7.4-curl php7.4-zip php7.4-gd php7.4-bcmath php7.4-sqlite3 composer git -y

# Configurar Nginx
sudo nano /etc/nginx/sites-available/alpesone-api
```

### 2. Configuração do Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/alpesone-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 3. Script de Deploy

Crie um script `deploy.sh` na raiz do projeto:

```bash
#!/bin/bash

# Configurações
APP_DIR="/var/www/alpesone-api"
BACKUP_DIR="/var/backups/alpesone-api"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "🚀 Iniciando deploy da Alpes One API..."

# Criar backup
echo "📦 Criando backup..."
mkdir -p $BACKUP_DIR
if [ -d "$APP_DIR" ]; then
    tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" -C $APP_DIR .
fi

# Atualizar código
echo "📥 Atualizando código..."
if [ -d "$APP_DIR" ]; then
    cd $APP_DIR
    git pull origin main
else
    git clone <repository-url> $APP_DIR
    cd $APP_DIR
fi

# Instalar dependências
echo "📚 Instalando dependências..."
composer install --no-dev --optimize-autoloader

# Configurar permissões
echo "🔐 Configurando permissões..."
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

# Executar migrações
echo "🗄️ Executando migrações..."
php artisan migrate --force

# Limpar caches
echo "🧹 Limpando caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reiniciar serviços
echo "🔄 Reiniciando serviços..."
sudo systemctl restart php7.4-fpm
sudo systemctl restart nginx

# Configurar cron para verificação automática
echo "⏰ Configurando cron..."
(crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo "✅ Deploy concluído com sucesso!"
echo "🌐 Acesse: http://your-domain.com"
```

### 4. Executar Deploy

```bash
# Dar permissão de execução
chmod +x deploy.sh

# Executar deploy
./deploy.sh
```

## 🔒 Segurança

- **API Key**: Autenticação obrigatória para todos os endpoints
- **Rate Limiting**: Limite de requisições por minuto
- **Validação**: Validação de entrada em todos os endpoints
- **SQL Injection**: Proteção através do Eloquent ORM

## 📊 Monitoramento

### Logs

Os logs são armazenados em:
- `storage/logs/laravel.log` - Logs gerais da aplicação
- `storage/logs/integrator.log` - Logs específicos do integrador

### Verificação de Status

```bash
# Verificar status dos serviços
sudo systemctl status nginx
sudo systemctl status php7.4-fpm

# Verificar logs em tempo real
tail -f storage/logs/laravel.log
```

## 🚨 Troubleshooting

### Problemas Comuns

1. **Erro de permissões**
   ```bash
   sudo chown -R www-data:www-data /var/www/alpesone-api
   sudo chmod -R 755 /var/www/alpesone-api
   ```

2. **Erro de banco de dados**
   ```bash
   php artisan migrate:fresh
   ```

3. **Erro de cache**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

## 📝 Estrutura do Projeto

```
alpesone-api/
├── app/
│   ├── Console/Commands/ImportIntegratorData.php
│   ├── Http/Controllers/Api/IntegratorDataController.php
│   ├── Http/Middleware/ApiAuthentication.php
│   ├── Jobs/CheckIntegratorDataUpdates.php
│   └── Models/IntegratorData.php
├── database/
│   ├── factories/IntegratorDataFactory.php
│   └── migrations/
├── routes/api.php
├── tests/
│   ├── Unit/ImportIntegratorDataTest.php
│   └── Feature/IntegratorDataApiTest.php
├── deploy.sh
└── README.md
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto foi criado a pedido da Alpes One.



**Desenvolvido para Alpes One**
