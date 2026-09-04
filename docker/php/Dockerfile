FROM php:8.3-fpm

# Install dependencies sistem & ekstensi PHP yang dibutuhkan Laravel & Filament
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    gnupg

# Install Node.js (Versi LTS terbaru via NodeSource) & npm
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs

# Clear cache apt untuk memperkecil ukuran image
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP (termasuk intl dan zip)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html