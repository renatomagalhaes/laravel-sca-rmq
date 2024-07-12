# Use PHP 8.2 FPM Alpine as the base image
FROM php:8.2-fpm-alpine

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
  tzdata \
  bash \
  curl \
  vim \
  rsync \
  unzip \
  bc \
  nodejs \
  npm \
  linux-headers \
  autoconf \
  g++ \
  make \
  nginx \
  supervisor \
  sqlite \
  libpng-dev \
  libjpeg-turbo-dev \
  freetype-dev \
  libzip-dev \
  bzip2-dev \
  oniguruma-dev \
  libsodium-dev \
  && docker-php-ext-install \
  pdo_mysql \
  mbstring \
  exif \
  pcntl \
  bcmath \
  gd \
  sockets \
  bz2 \
  sodium \
  zip

# Set the timezone
RUN ln -sf /usr/share/zoneinfo/America/Fortaleza /etc/localtime && echo "America/Fortaleza" > /etc/timezone
ENV TZ=America/Fortaleza

# Install Xdebug
RUN pecl install xdebug redis \
    && docker-php-ext-enable xdebug redis

# Create a non-root user
RUN mkdir -p /home/www-data/cache \
    && mkdir -p /var/www

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure nginx
COPY ./docker/local/nginx/nginx.conf /etc/nginx/nginx.conf

# Configure PHP-FPM
COPY ./docker/local/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/local/php/php.ini $PHP_INI_DIR/conf.d/

# Configure supervisord
RUN mkdir -p /var/log/supervisor/
COPY ./docker/local/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY ./docker/local/supervisor/conf.d/*.ini /etc/supervisor/conf.d/

# Cache strategy for node_modules and composer dependencies
WORKDIR /home/www-data/cache
COPY package*.json ./
RUN npm install && npm cache clean --force
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction

# Set the working directory
WORKDIR /var/www

# Copy the Laravel application files
COPY . .

# Expose port 80 for nginx
EXPOSE 80

# Copy the start script
COPY ./docker/local/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Define the command to run when the container starts
CMD ["/usr/local/bin/start.sh"]
