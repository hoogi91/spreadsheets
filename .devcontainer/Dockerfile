ARG VARIANT=8.1-bullseye
FROM mcr.microsoft.com/vscode/devcontainers/php:0-${VARIANT}

RUN curl -sL https://deb.nodesource.com/setup_16.x | sudo -E bash - \
    && apt-get update \
    && apt-get install -y default-mysql-client nodejs wait-for-it \
    && rm -rf /var/lib/apt/lists/*