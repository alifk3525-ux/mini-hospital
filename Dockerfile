# 1. Gunakan web server Apache dengan PHP versi 8.2 resmi
FROM php:8.2-apache

# 2. Install modul MySQL agar PHP bisa baca database
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 3. Aktifkan modul rewrite Apache
RUN a2enmod rewrite

# 4. Salin semua file proyek mini hospital ke dalam folder web Apache
COPY . /var/www/html/

# 5. Buka port 80 untuk lalu lintas web
EXPOSE 80