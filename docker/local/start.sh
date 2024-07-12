#!/bin/bash

start=`date +%s`

# Verificar se há erros nas dependências do npm
npm ls | grep ERR

# Sincronizar cache do npm
rsync -arv /home/www-data/cache/node_modules/ /var/www/node_modules/

# Sincronizar cache do Composer
rsync -arv /home/www-data/cache/vendor/ /var/www/vendor/

end=`date +%s`
runtime=$(echo "$end - $start" | bc -l)
echo "#### Runtime: $runtime seconds ####"

# Permissões
chmod -R 777 /var/www/storage
chmod -R 777 /var/www/bootstrap/cache

# Verificar se o arquivo .env existe e criar se não existir
if [ ! -f "/var/www/.env" ]; then
    cp /var/www/.env.example /var/www/.env
    php artisan key:generate
fi

# Configuração do Laravel
php /var/www/artisan migrate --force
php /var/www/artisan db:seed --force
php /var/www/artisan config:clear
php /var/www/artisan route:clear
php /var/www/artisan view:clear
php /var/www/artisan cache:clear

# Iniciar serviços
/usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
