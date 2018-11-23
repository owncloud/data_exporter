SHELL := /bin/bash

COMPOSER_BIN := $(shell command -v composer 2> /dev/null)
ifndef COMPOSER_BIN
    $(error composer is not available on your system, please install composer)
endif

# bin file definitions
PHPUNIT=php -d zend.enable_gc=0  "$(PWD)/../../lib/composer/bin/phpunit"
PHPUNITDBG=phpdbg -qrr -d memory_limit=4096M -d zend.enable_gc=0 "$(PWD)/../../lib/composer/bin/phpunit"
PHP_CS_FIXER=php -d zend.enable_gc=0 vendor-bin/owncloud-codestyle/vendor/bin/php-cs-fixer
PHP_PARALLEL_LINT=php -d zend.enable_gc=0 vendor-bin/php-parallel-lint/vendor/bin/parallel-lint
PHPSTAN=php -d zend.enable_gc=0 vendor-bin/phpstan/vendor/bin/phpstan
PHAN=php -d zend.enable_gc=0 vendor-bin/phan/vendor/bin/phan

# composer
composer_deps=vendor
composer_dev_deps=lib/composer/phpunit

#
# Catch-all rules
#
.PHONY: all
all: $(composer_dev_deps)

.PHONY: clean
clean: clean-composer-deps

#
# base composer steps
#
$(composer_deps): composer.json composer.lock
	$(COMPOSER_BIN) install --no-dev

$(composer_dev_deps): composer.json composer.lock
	$(COMPOSER_BIN) install --dev

.PHONY: clean-composer-deps
clean-composer-deps:
	rm -Rf $(composer_deps)

vendor-bin/owncloud-codestyle/vendor: vendor-bin/owncloud-codestyle/composer.lock
	composer bin owncloud-codestyle install --no-progress

vendor-bin/owncloud-codestyle/composer.lock: vendor-bin/owncloud-codestyle/composer.json
	@echo owncloud-codestyle composer.lock is not up to date.

vendor-bin/php-parallel-lint/vendor: vendor-bin/php-parallel-lint/composer.lock
	composer bin php-parallel-lint install --no-progress

vendor-bin/php-parallel-lint/composer.lock: vendor-bin/php-parallel-lint/composer.json
	@echo php-parallel-lint composer.lock is not up to date.

vendor-bin/phpstan/vendor: vendor-bin/phpstan/composer.lock
	composer bin phpstan install --no-progress

vendor-bin/phpstan/composer.lock: vendor-bin/phpstan/composer.json
	@echo phpstan composer.lock is not up to date.

vendor-bin/phan/vendor: vendor-bin/phan/composer.lock
	composer bin phan install --no-progress

vendor-bin/phan/composer.lock: vendor-bin/phan/composer.json
	@echo phan composer.lock is not up to date.
#
# Tests
#

.PHONY: test-php-lint
test-php-lint: vendor-bin/php-parallel-lint/vendor
	$(PHP_PARALLEL_LINT) --exclude vendor --exclude build --exclude vendor-bin .

.PHONY: test-php-style
test-php-style: vendor-bin/owncloud-codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --diff-format udiff --dry-run --allow-risky yes

.PHONY: test-php-style-fix
test-php-style-fix: vendor-bin/owncloud-codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --diff-format udiff --allow-risky yes

.PHONY: test-php-phpstan
test-php-phpstan: vendor-bin/phpstan/vendor
	$(PHPSTAN) analyse --memory-limit=4G --configuration=./phpstan.neon --no-progress --level=5 appinfo lib

.PHONY: test-php-phan
test-php-phan: vendor-bin/phan/vendor
	$(PHAN) --config-file .phan/config.php --require-config-exists

.PHONY: test-php-unit
test-php-unit:
	$(PHPUNIT) --configuration ./phpunit.xml --testsuite unit

.PHONY: test-php-unit-dbg
test-php-unit-dbg:
	$(PHPUNITDBG) --configuration ./phpunit.xml --testsuite unit

.PHONY: test-php-integration
test-php-integration:
	$(PHPUNIT) --configuration ./phpunit.xml --testsuite integration

.PHONY: test-php-integration-dbg
test-php-integration-dbg:
	$(PHPUNITDBG) --configuration ./phpunit.xml --testsuite integration

.PHONY: test-acceptance-cli
test-acceptance-cli:
test-acceptance-cli: all
	../../tests/acceptance/run.sh --config tests/acceptance/config/behat.yml --type cli
