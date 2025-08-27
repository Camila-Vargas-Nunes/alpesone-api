# Configuração da Infraestrutura na AWS

Este guia detalha como configurar e implantar a aplicação Alpes One API na AWS.

## Pré-requisitos

- Conta AWS ativa
- Acesso ao console AWS
- Conhecimento básico de EC2, VPC e segurança
- Chave SSH para acesso à instância

## Arquitetura Recomendada

```
Internet Gateway
       ↓
   Route 53 (DNS)
       ↓
   Application Load Balancer (opcional)
       ↓
   EC2 Instance (t3.micro ou t3.small)
       ↓
   RDS MySQL (opcional, para produção)
```

## Passo a Passo

### 1. Criar Instância EC2

#### 1.1 Lançar Instância
1. Acesse o console AWS EC2
2. Clique em "Launch Instance"
3. Configure:
   - **Name**: `alpesone-api-production`
   - **AMI**: Ubuntu Server 20.04 LTS (HVM)
   - **Instance Type**: t3.micro (free tier) ou t3.small
   - **Key Pair**: Selecione ou crie uma nova chave SSH

#### 1.2 Configuração de Rede
- **VPC**: Default VPC
- **Subnet**: Subnet pública
- **Auto-assign Public IP**: Enable
- **Security Group**: Criar novo (ver configuração abaixo)

#### 1.3 Storage
- **Root Volume**: 20 GB (gp3)
- **Delete on Termination**: Desmarcar para produção

### 2. Configurar Security Group

#### 2.1 Inbound Rules
```
Type        Protocol    Port Range    Source
SSH         TCP         22            Your IP / 0.0.0.0/0
HTTP        TCP         80            0.0.0.0/0
HTTPS       TCP         443           0.0.0.0/0 (se usar SSL)
Custom      TCP         8000          0.0.0.0/0 (para desenvolvimento)
```

#### 2.2 Outbound Rules
```
Type        Protocol    Port Range    Destination
All         All         All           0.0.0.0/0
```

### 3. Conectar à Instância

```bash
# Conectar via SSH
ssh -i "sua-chave.pem" ubuntu@IP_PUBLICO_DA_INSTANCIA

# Atualizar sistema
sudo apt update && sudo apt upgrade -y
```

### 4. Instalar Dependências

```bash
# Instalar pacotes essenciais
sudo apt install -y \
    nginx \
    php7.4-fpm \
    php7.4-mysql \
    php7.4-xml \
    php7.4-curl \
    php7.4-zip \
    php7.4-gd \
    php7.4-bcmath \
    php7.4-sqlite3 \
    php7.4-mbstring \
    composer \
    git \
    unzip \
    curl \
    supervisor

# Verificar versões
php -v
nginx -v
composer --version
```

### 5. Configurar Nginx

```bash
# Remover configuração padrão
sudo rm /etc/nginx/sites-enabled/default

# Copiar configuração personalizada
sudo cp nginx-config.conf /etc/nginx/sites-available/alpesone-api

# Ativar site
sudo ln -s /etc/nginx/sites-available/alpesone-api /etc/nginx/sites-enabled/

# Testar configuração
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

### 6. Configurar PHP-FPM

```bash
# Editar configuração PHP-FPM
sudo nano /etc/php/7.4/fpm/pool.d/www.conf

# Alterar:
# user = www-data
# group = www-data
# listen = /run/php/php7.4-fpm.sock
# listen.owner = www-data
# listen.group = www-data

# Reiniciar PHP-FPM
sudo systemctl restart php7.4-fpm
sudo systemctl enable php7.4-fpm
```

### 7. Configurar Supervisor

```bash
# Criar diretório de logs
sudo mkdir -p /var/log/alpesone-api

# Copiar configuração
sudo cp supervisor-config.conf /etc/supervisor/conf.d/alpesone-scheduler.conf

# Recarregar configurações
sudo supervisorctl reread
sudo supervisorctl update

# Verificar status
sudo supervisorctl status
```

### 8. Deploy da Aplicação

```bash
# Clonar repositório
cd /var/www
sudo git clone https://github.com/seu-usuario/alpesone-api.git
sudo chown -R ubuntu:ubuntu alpesone-api
cd alpesone-api

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Configurar banco SQLite
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
sed -i '/DB_HOST/d' .env
sed -i '/DB_PORT/d' .env
sed -i '/DB_DATABASE/d' .env
sed -i '/DB_USERNAME/d' .env
sed -i '/DB_PASSWORD/d' .env

# Criar banco SQLite
touch database/database.sqlite

# Executar migrações
php artisan migrate

# Configurar permissões
sudo chown -R www-data:www-data /var/www/alpesone-api
sudo chmod -R 755 /var/www/alpesone-api
sudo chmod -R 775 /var/www/alpesone-api/storage
sudo chmod -R 775 /var/www/alpesone-api/bootstrap/cache

# Otimizar caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. Configurar SSL/HTTPS (Opcional)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obter certificado SSL
sudo certbot --nginx -d seu-dominio.com

# Configurar renovação automática
sudo crontab -e
# Adicionar: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 10. Configurar DNS (Route 53)

1. Acesse o console Route 53
2. Criar/editar zona hospedada
3. Adicionar registros A:
   ```
   Type: A
   Name: api (ou @ para domínio raiz)
   Value: IP_PUBLICO_DA_EC2
   TTL: 300
   ```

### 11. Configurar Monitoramento

```bash
# Instalar CloudWatch Agent
sudo apt install amazon-cloudwatch-agent -y

# Configurar métricas básicas
sudo /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-config-wizard

# Iniciar agente
sudo systemctl start amazon-cloudwatch-agent
sudo systemctl enable amazon-cloudwatch-agent
```

## Segurança

### Firewall (UFW)
```bash
# Habilitar UFW
sudo ufw enable

# Permitir SSH
sudo ufw allow ssh

# Permitir HTTP/HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Verificar status
sudo ufw status
```

### Atualizações Automáticas
```bash
# Instalar unattended-upgrades
sudo apt install unattended-upgrades -y

# Configurar
sudo dpkg-reconfigure -plow unattended-upgrades

# Verificar status
sudo systemctl status unattended-upgrades
```

## Monitoramento e Logs

### Logs da Aplicação
```bash
# Logs Laravel
tail -f /var/www/alpesone-api/storage/logs/laravel.log

# Logs Nginx
tail -f /var/log/nginx/alpesone-api-access.log
tail -f /var/log/nginx/alpesone-api-error.log

# Logs Supervisor
tail -f /var/log/alpesone-api/scheduler.log
```

### Métricas do Sistema
```bash
# Uso de CPU e memória
htop

# Uso de disco
df -h

# Status dos serviços
sudo systemctl status nginx php7.4-fpm supervisor
```

## 🚨 Troubleshooting

### Problemas Comuns

1. **Erro 502 Bad Gateway**
   ```bash
   # Verificar PHP-FPM
   sudo systemctl status php7.4-fpm
   sudo systemctl restart php7.4-fpm
   ```

2. **Erro de permissões**
   ```bash
   sudo chown -R www-data:www-data /var/www/alpesone-api
   sudo chmod -R 755 /var/www/alpesone-api
   ```

3. **Erro de banco de dados**
   ```bash
   cd /var/www/alpesone-api
   php artisan migrate:fresh
   ```

4. **Serviço não inicia**
   ```bash
   sudo journalctl -u nginx -f
   sudo journalctl -u php7.4-fpm -f
   ```

## Estimativa de Custos

### Free Tier (12 meses)
- **EC2 t3.micro**: $0.00/mês
- **EBS 20GB**: $0.00/mês
- **Data Transfer**: 15GB/mês grátis

### Após Free Tier
- **EC2 t3.micro**: ~$8.50/mês
- **EBS 20GB**: ~$2.00/mês
- **Data Transfer**: $0.09/GB

**Total estimado**: ~$10-15/mês

## Checklist de Deploy

- [ ] Instância EC2 criada e configurada
- [ ] Security Group configurado
- [ ] Dependências instaladas
- [ ] Nginx configurado e funcionando
- [ ] PHP-FPM configurado e funcionando
- [ ] Aplicação deployada e funcionando
- [ ] Supervisor configurado para jobs
- [ ] SSL configurado (opcional)
- [ ] DNS configurado
- [ ] Monitoramento configurado
- [ ] Backup configurado
- [ ] Testes realizados

## Deploy Automatizado

Para configurar deploy automático via GitHub Actions:

1. Adicionar secrets no repositório:
   - `EC2_HOST`: IP público da instância
   - `EC2_USERNAME`: ubuntu
   - `EC2_SSH_KEY`: Chave SSH privada

2. O workflow será executado automaticamente a cada push para main

## Suporte

Para problemas relacionados à AWS:
- [AWS Documentation](https://docs.aws.amazon.com/)
- [AWS Support](https://aws.amazon.com/support/)
- [AWS Community](https://aws.amazon.com/community/)

---

**Configuração criada para api da Alpes One** 