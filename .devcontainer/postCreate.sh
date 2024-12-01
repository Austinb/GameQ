#!/usr/bin/env bash

# Install dependencies using Composer
if [ ! -f composer.lock ]; then
    composer install
fi