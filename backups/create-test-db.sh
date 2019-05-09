#!/usr/bin/env bash

# This file is executed when the maria db container is started for the first time. It is creating an additional database for tests.
# @see https://hub.docker.com/_/mariadb in 'Initializing a fresh instance' section.
mysql -h localhost -u root -plive_usom -P 3360 -e 'CREATE DATABASE IF NOT EXISTS `rf-woocommerce-test`; GRANT ALL PRIVILEGES ON `rf-woocommerce-test`.* TO "live_usom"@"%"; FLUSH PRIVILEGES;'
