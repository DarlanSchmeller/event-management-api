FROM php:8.2-cli

# Install PDO MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app

# Set working directory
WORKDIR /app

# Copy all files from current dir to container
COPY . /app
