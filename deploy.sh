#!/bin/bash

# Alpes One API - Script de Deploy
# Este script automatiza o processo de deploy da aplicação na instância EC2

set -e  # Para o script se houver erro

# Configurações
APP_NAME="alpesone-api"
APP_DIR="/var/www/$APP_NAME"
BACKUP_DIR="/var/backups/$APP_NAME"
REPO_URL="https://github.com/Camila-Vargas-Nunes/alpesone-api.git"
BRANCH="main"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# Verificar se está rodando como root
if [[ $EUID -eq 0 ]]; then
   error "Este script não deve ser executado como root"
fi

# Verificar se o diretório de backup existe
if [ ! -d "$BACKUP_DIR" ]; then
    log "Criando diretório de backup..."
    sudo mkdir -p "$BACKUP_DIR"
    sudo chown $USER:$USER "$BACKUP_DIR"
fi

log "🚀 Iniciando deploy da $APP_NAME..."

# 1. Criar backup
log "📦 Criando backup da aplicação atual..."
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
    tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" \
        --exclude='.git' \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        .
    log "✅ Backup criado: backup_$TIMESTAMP.tar.gz"
else
    warn "Diretório da aplicação não encontrado, pulando backup..."
fi

# 2. Atualizar código
log "📥 Atualizando código da aplicação..."
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
    log "Puxando alterações do repositório..."
    git fetch origin
    git reset --hard origin/$BRANCH
    log "✅ Código atualizado para a branch $BRANCH"
else
    log "Clonando repositório..."
    sudo mkdir -p "$APP_DIR"
    sudo chown $USER:$USER "$APP_DIR"
    cd "$APP_DIR"
    git clone -b $BRANCH $REPO_URL .
    log "✅ Repositório clonado"
fi

# 3. Instalar dependências
log "📚 Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader --no-interaction
log "✅ Dependências instaladas"

# 4. Configurar permissões
log "🔐 Configurando permissões..."
sudo chown -R www-data:www-data "$APP_DIR"
sudo chmod -R 755 "$APP_DIR"
sudo chmod -R 775 "$APP_DIR/storage"
sudo chmod -R 775 "$APP_DIR/bootstrap/cache"
log "✅ Permissões configuradas"

# 5. Configurar arquivo .env se não existir
if [ ! -f "$APP_DIR/.env" ]; then
    log "⚙️ Configurando arquivo .env..."
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    
    # Gerar chave da aplicação
    cd "$APP_DIR"
    php artisan key:generate --force
    
    # Configurar banco SQLite
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
    sed -i '/DB_HOST/d' .env
    sed -i '/DB_PORT/d' .env
    sed -i '/DB_DATABASE/d' .env
    sed -i '/DB_USERNAME/d' .env
    sed -i '/DB_PASSWORD/d' .env
    
    # Criar arquivo SQLite
    touch database/database.sqlite
    
    log "✅ Arquivo .env configurado"
fi

# 6. Executar migrações
log "🗄️ Executando migrações do banco de dados..."
cd "$APP_DIR"
php artisan migrate --force
log "✅ Migrações executadas"

# 7. Limpar e otimizar caches
log "🧹 Limpando e otimizando caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
log "✅ Caches otimizados"

# 8. Configurar cron para verificação automática
log "⏰ Configurando cron para verificação automática..."
CRON_JOB="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"

# Verificar se o cron job já existe
if ! crontab -l 2>/dev/null | grep -q "$APP_DIR.*schedule:run"; then
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    log "✅ Cron job configurado"
else
    log "ℹ️ Cron job já existe, pulando..."
fi

# 9. Reiniciar serviços
log "🔄 Reiniciando serviços..."
sudo systemctl restart php7.4-fpm
sudo systemctl restart nginx
log "✅ Serviços reiniciados"

# 10. Verificar status dos serviços
log "🔍 Verificando status dos serviços..."
if systemctl is-active --quiet php7.4-fpm; then
    log "✅ PHP-FPM está rodando"
else
    error "❌ PHP-FPM não está rodando"
fi

if systemctl is-active --quiet nginx; then
    log "✅ Nginx está rodando"
else
    error "❌ Nginx não está rodando"
fi

# 11. Testar aplicação
log "🧪 Testando aplicação..."
sleep 5  # Aguardar serviços iniciarem

if curl -s -o /dev/null -w "%{http_code}" http://localhost/api/integrator | grep -q "401"; then
    log "✅ API está respondendo (401 - autenticação requerida, como esperado)"
else
    warn "⚠️ API pode não estar funcionando corretamente"
fi

# 12. Limpar backups antigos (manter apenas os últimos 5)
log "🧹 Limpando backups antigos..."
cd "$BACKUP_DIR"
ls -t *.tar.gz | tail -n +6 | xargs -r rm
log "✅ Backups antigos removidos"

# 13. Resumo final
log "🎉 Deploy concluído com sucesso!"
log "📊 Resumo:"
log "   - Aplicação: $APP_NAME"
log "   - Diretório: $APP_DIR"
log "   - Backup: backup_$TIMESTAMP.tar.gz"
log "   - Branch: $BRANCH"
log "   - Timestamp: $TIMESTAMP"

# 14. Informações úteis
echo ""
log "📋 Próximos passos:"
log "   1. Configure seu domínio no Nginx se necessário"
log "   2. Configure SSL/HTTPS se necessário"
log "   3. Monitore os logs: tail -f $APP_DIR/storage/logs/laravel.log"
log "   4. Teste a API: curl -H 'X-API-Key: alpesone-test-2024' http://localhost/api/integrator"

echo ""
log "🌐 A aplicação está disponível em: http://localhost"
log "🔑 API Key padrão: alpesone-test-2024"

# 15. Verificar espaço em disco
DISK_USAGE=$(df -h "$APP_DIR" | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    warn "⚠️ Uso de disco alto: ${DISK_USAGE}%"
else
    log "💾 Uso de disco: ${DISK_USAGE}%"
fi

log "✅ Deploy finalizado com sucesso!" 