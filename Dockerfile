FROM php:8.2-apache-bookworm

# Install Python 3.11 and system libraries
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    python3-dev \
    git \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender1 \
    && rm -rf /var/lib/apt/lists/*

# Install PHP MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache rewrite
RUN a2enmod rewrite

# Create Python virtual environment
RUN python3 -m venv /opt/venv

# Use virtual environment Python and pip
ENV PATH="/opt/venv/bin:$PATH"
ENV PYTHON_BIN="/opt/venv/bin/python"

# Upgrade pip inside virtual environment only
RUN pip install --no-cache-dir --upgrade pip setuptools wheel

# Install CPU PyTorch
RUN pip install --no-cache-dir \
    --index-url https://download.pytorch.org/whl/cpu \
    torch torchvision

# Install project Python packages
RUN pip install --no-cache-dir -r requirements.txt

# Make uploads folder writable
RUN mkdir -p /var/www/html/frontend/uploads \
    && chown -R www-data:www-data /var/www/html/frontend/uploads \
    && chmod -R 775 /var/www/html/frontend/uploads

# Give Apache permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
