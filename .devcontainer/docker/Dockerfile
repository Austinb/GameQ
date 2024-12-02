########################## Base ###########################
# Create & configure the base for this DevContainer image #
###########################################################

# Define the DevContainer template to be used as the base
ARG PHP=8.3
ARG DISTRO=bullseye

FROM mcr.microsoft.com/vscode/devcontainers/php:$PHP-$DISTRO

# Add global Composer /bin to PATH for vscode
USER vscode

RUN echo "export PATH=\$(XDEBUG_MODE=off composer global config bin-dir --absolute --quiet):\$PATH" >> ~/.bashrc

# Add global Composer /bin to PATH for root
USER root

RUN echo "export PATH=\$(XDEBUG_MODE=off composer global config bin-dir --absolute --quiet):\$PATH" >> ~/.bashrc

##################### Dependencies #####################
# Add any dependencies required to develop the project #
########################################################

# Update the registry once
RUN apt-get update

# Install ext-bz2
RUN apt-get install -y libbz2-dev \
    && docker-php-ext-configure bz2 \
    && docker-php-ext-install bz2


###################### Tools ######################
# Install tools that are required for development #
###################################################

# Install act
RUN curl --proto '=https' --tlsv1.2 -sSf https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash -s -- -b /usr/local/bin

# Install PHP Code Sniffer Fixer
USER vscode

RUN composer global require friendsofphp/php-cs-fixer