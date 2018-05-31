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
    ;;
    install)
        cd tests/platform
        composer install --no-interaction --no-suggest --prefer-dist

        if [ -f app/console ]; then
            CONFIG_DIR=app/config
        elif [ -f bin/console ]; then
            CONFIG_DIR=config
        else
            echo "Not found symfony project"
            exit 1
        fi

        cp "$CONFIG_DIR/parameters_test.yml.dist" "$CONFIG_DIR/parameters_test.yml"

        sed -i "s/message_queue_transport"\:".*/message_queue_transport"\:" dbal/g" "$CONFIG_DIR/parameters_test.yml"
        sed -i "s/message_queue_transport_config"\:".*/message_queue_transport_config"\:" ~/g" "$CONFIG_DIR/parameters_test.yml"
        case ${DATABASE} in
            mysql)
                mysql -u root -e "create database IF NOT EXISTS okvpn";
                find "$CONFIG_DIR" -type f -name 'parameters_test.yml' -exec sed -i "s/database_driver"\:".*/database_driver"\:" pdo_mysql/g; s/database_name"\:".*/database_name"\:" okvpn/g; s/database_user"\:".*/database_user"\:" root/g; s/database_password"\:".*/database_password"\:" ~/g; s/mailer_transport"\:".*/mailer_transport"\:" null/g;" {} \;
            ;;
            postgresql)
                psql -U postgres -c "CREATE DATABASE okvpn;";
                psql -U postgres -c 'CREATE EXTENSION IF NOT EXISTS "uuid-ossp";' -d okvpn;
                find "$CONFIG_DIR" -type f -name 'parameters_test.yml' -exec sed -i "s/database_driver"\:".*/database_driver"\:" pdo_pgsql/g; s/database_name"\:".*/database_name"\:" okvpn/g; s/database_user"\:".*/database_user"\:" postgres/g; s/database_password"\:".*/database_password"\:" ~/g; s/mailer_transport"\:".*/mailer_transport"\:" null/g;" {} \;
            ;;
        esac
    ;;
    before_script)
        cd tests/platform

        if [ -f app/console ]; then
            APP_BIN=app/console
        elif [ -f bin/console ]; then
            APP_BIN=bin/console
        else
            echo "Not found symfony project"
            exit 1
        fi

        START=`date +%s`
        php ${APP_BIN} oro:install --env test  --user-name=admin \
            --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password=admin \
            --sample-data=n --organization-name=OroCRM --no-interaction --application-url="http://localhost/" \
            --skip-assets --timeout 600;

        END=`date +%s`
        SECONDS=$(($END-$START))

        echo "Installation time - $(($SECONDS/60)) minutes $(($SECONDS%60)) seconds"
    ;;
esac
