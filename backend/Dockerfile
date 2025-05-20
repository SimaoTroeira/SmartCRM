FROM php:8.3-cli-bullseye

# Instalar extensões PHP
# RUN apt-get update && apt-get install -y \
#     libpng-dev \
#     libjpeg-dev \
#     libfreetype6-dev \
#     libonig-dev \
#     libxml2-dev \
#     zip \
#     unzip \
#     git \
#     curl \
#     && docker-php-ext-install pdo_mysql gd intl mbstring zip xml
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    python3 \
    python3-pip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo_mysql gd intl mbstring zip xml \
    && ln -s /usr/bin/python3 /usr/bin/python \
    && pip install pandas numpy scikit-learn matplotlib mlxtend joblib xgboost


# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

#CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
CMD ["php", "-a"]