#!/bin/bash

export DATABASE
export ORO_PLATFORM
export GITHUB_OAUTH

STEP=$1

case ${STEP} in
    before_install)
        pushd tests
        if [[ -d platform ]]; then
            chmod -R 777 platform
            rm -r platform
        fi
        git clone -b ${ORO_PLATFORM} https://github.com/oroinc/platform-application.git platform
        mkdir -p platform/src/Okvpn/Bundle/
        popd
        ln -s "$PWD/src" tests/platform/src/Okvpn/Bundle/MQInsightBundle

        # for 2.0 capability
        composer global require fxp/composer-asset-plugin
    ;;
    install)
        cd tests/platform
        composer install --no-interaction --no-suggest --prefer-dist
        cp app/config/parameters_test.yml.dist app/config/parameters_test.yml

        sed -i "s/message_queue_transport"\:".*/message_queue_transport"\:" dbal/g" app/config/parameters_test.yml
        sed -i "s/message_queue_transport_config"\:".*/message_queue_transport_config"\:" ~/g" app/config/parameters_test.yml
        case ${DATABASE} in
            mysql)
                mysql -u root -e "create database IF NOT EXISTS okvpn";
                find app/config -type f -name 'parameters_test.yml' -exec sed -i "s/database_driver"\:".*/database_driver"\:" pdo_mysql/g; s/database_name"\:".*/database_name"\:" okvpn/g; s/database_user"\:".*/database_user"\:" root/g; s/database_password"\:".*/database_password"\:" ~/g; s/mailer_transport"\:".*/mailer_transport"\:" null/g;" {} \;
            ;;
            postgresql)
                psql -U postgres -c "CREATE DATABASE okvpn;";
                psql -U postgres -c 'CREATE EXTENSION IF NOT EXISTS "uuid-ossp";' -d okvpn;
                find app/config -type f -name 'parameters_test.yml' -exec sed -i "s/database_driver"\:".*/database_driver"\:" pdo_pgsql/g; s/database_name"\:".*/database_name"\:" okvpn/g; s/database_user"\:".*/database_user"\:" postgres/g; s/database_password"\:".*/database_password"\:" ~/g; s/mailer_transport"\:".*/mailer_transport"\:" null/g;" {} \;
            ;;
        esac
    ;;
    before_script)
        cd tests/platform
        php app/console oro:install --env test  --user-name=admin \
            --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password=admin \
            --sample-data=n --organization-name=OroCRM --no-interaction --application-url="http://localhost/" \
            --skip-assets --timeout 600;
    ;;
esac
