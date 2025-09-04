FROM php:8.0-fpm

# Copy composer.lock and composer.json
# COPY composer.lock composer.json /var/www/
COPY composer.json /var/www/
# Set root password for container access
RUN echo 'root:root' | chpasswd && \
    echo "Root password set to 'root' for container access"

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy your local packages first so Composer can find them.
COPY local_packages /var/www/local_packages

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www

# Create directories if they don't exist and set permissions
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache

RUN git config --global --add safe.directory /var/www

# 1. Clear cache to avoid issues.
# 2. Use "install" which is faster and more reliable for builds if a lock file exists.
RUN composer clear-cache && \
    composer config -g disable-tls true && \
    composer config -g process-timeout 2000 && \
    composer install --no-interaction --no-progress --no-scripts

# Set proper permissions after composer operations
RUN chown -R www:www /var/www/storage
RUN chown -R www:www /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/bootstrap/cache

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
