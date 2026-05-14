FROM php:8.2-apache

# Install Python and system libraries needed by PyTorch / OpenCV / YOLO
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    git \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender1 \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy full project into container
COPY . /var/www/html

# Use custom Apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache rewrite
RUN a2enmod rewrite

# Upgrade pip
RUN python3 -m pip install --break-system-packages --upgrade pip

# Install CPU version PyTorch
RUN python3 -m pip install --break-system-packages --no-cache-dir \
    --index-url https://download.pytorch.org/whl/cpu \
    torch torchvision

# Install Python requirements
RUN python3 -m pip install --break-system-packages --no-cache-dir -r requirements.txt

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Make upload folder writable
RUN mkdir -p /var/www/html/frontend/uploads \
    && chown -R www-data:www-data /var/www/html/frontend/uploads \
    && chmod -R 775 /var/www/html/frontend/uploads

# Permission for whole project
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
