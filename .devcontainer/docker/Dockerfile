# Define the DevContainer template to be used as the base
ARG PHP=8.3
ARG DISTRO=bullseye

FROM mcr.microsoft.com/vscode/devcontainers/php:$PHP-$DISTRO

# Update the registry once
RUN apt-get update

# Install ext-bz2
RUN apt-get install -y libbz2-dev \
    && docker-php-ext-configure bz2 \
    && docker-php-ext-install bz2

# Install act
RUN curl --proto '=https' --tlsv1.2 -sSf https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash -s -- -b /usr/local/bin