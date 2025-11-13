#!/bin/bash
#
# SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd $DIR/

git submodule update --init

# Codespace config
cp .devcontainer/codespace.config.php config/codespace.config.php

# VSCode debugger profile
mkdir -p .vscode && cp .devcontainer/launch.json .vscode/launch.json

# Onetime installation setup
if [[ ! $(sudo -u ${APACHE_RUN_USER} php occ status) =~ installed:[[:space:]]*true ]]; then
    echo "Running NC installation"
    sudo -u ${APACHE_RUN_USER} php occ maintenance:install \
        --verbose \
        --database=pgsql \
        --database-name=postgres \
        --database-host=127.0.0.1 \
        --database-port=5432 \
        --database-user=postgres \
        --database-pass=postgres \
        --admin-user admin \
        --admin-pass admin
fi

sudo service apache2 restart

# Activer automatiquement les apps utiles
echo "🔧 Activation des apps communes..."
sudo -u ${APACHE_RUN_USER} php occ app:enable files || true
sudo -u ${APACHE_RUN_USER} php occ app:enable dashboard || true
sudo -u ${APACHE_RUN_USER} php occ app:enable helloworld || true

# Copier une configuration partagée (si vous en avez une dans le repo)
if [ -f .devcontainer/shared.config.php ]; then
  echo "📦 Application de la configuration commune..."
  cp .devcontainer/shared.config.php config/config.php
fi

# Créer des utilisateurs de test si besoin
if ! sudo -u ${APACHE_RUN_USER} php occ user:list | grep -q 'nathan'; then
  sudo -u ${APACHE_RUN_USER} php occ user:add --password-from-env nathan
fi

echo "✅ Setup terminé ! Nextcloud prêt sur http://localhost:8080"
