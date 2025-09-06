FROM php:8.0-fpm

# Set working directory
WORKDIR /var/www

# --- Part 1: Install System Environment & Tools ---
# These layers are stable and will rarely be rebuilt.

# Set root password for container access (your custom setting)
RUN echo 'root:root' | chpasswd && \
    echo "Root password set to 'root' for container access"

# Install system dependencies (your custom list and mirror)
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
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (your custom list)
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create the application user
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# --- Part 2: Install Composer Dependencies ---
# This layer is only rebuilt when your dependencies change.

# Copy only the files needed for 'composer install'
COPY composer.json composer.lock* ./
COPY local_packages ./local_packages

# Fix "dubious ownership" warning and run install
RUN git config --global --add safe.directory /var/www && \
    composer clear-cache && \
    composer config -g disable-tls true && \
    composer config -g process-timeout 2000 && \
    composer install --no-interaction --no-progress --no-scripts

# --- Part 3: Copy Application Code & Set Permissions ---
# This is the final, most frequently rebuilt layer.

# Copy the rest of your application code
COPY . .

# Set correct ownership for the entire application.
# Must run as root to have permission to chown.
USER root
RUN chown -R www:www /var/www

# Switch back to the non-root user for security
USER www

# Expose port and start the server
EXPOSE 9000
CMD ["php-fpm"]