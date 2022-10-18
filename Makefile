SHELL := /bin/bash

COMPOSER_BIN := $(shell command -v composer 2> /dev/null)

# bin file definitions
PHPUNIT=php -d zend.enable_gc=0  "$(PWD)/../../lib/composer/bin/phpunit"
PHPUNITDBG=phpdbg -qrr -d memory_limit=4096M -d zend.enable_gc=0 "$(PWD)/../../lib/composer/bin/phpunit"
PHP_CS_FIXER=php -d zend.enable_gc=0 vendor-bin/owncloud-codestyle/vendor/bin/php-cs-fixer
PHP_CODESNIFFER=vendor-bin/php_codesniffer/vendor/bin/phpcs
PHPSTAN=php -d zend.enable_gc=0 vendor-bin/phpstan/vendor/bin/phpstan
PHAN=php -d zend.enable_gc=0 vendor-bin/phan/vendor/bin/phan
BEHAT_BIN=vendor-bin/behat/vendor/bin/behat

# composer
composer_deps=vendor
composer_dev_deps=lib/composer/phpunit
acceptance_test_deps=vendor-bin/behat/vendor

build_dir=$(CURDIR)/build
dist_dir=$(build_dir)/dist
app_name=data_exporter
src_files=README.md CHANGELOG.md LICENSE
src_dirs=appinfo docs img lib vendor
all_src=$(src_dirs) $(src_files)

# signing
occ=$(CURDIR)/../../occ
private_key=$(HOME)/.owncloud/certificates/$(app_name).key
certificate=$(HOME)/.owncloud/certificates/$(app_name).crt
sign=$(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"
sign_skip_msg="Skipping signing, either no key and certificate found in $(private_key) and $(certificate) or occ can not be found at $(occ)"
ifneq (,$(wildcard $(private_key)))
ifneq (,$(wildcard $(certificate)))
ifneq (,$(wildcard $(occ)))
	CAN_SIGN=true
endif
endif
endif

#
# Catch-all rules
#
.PHONY: all
all: $(composer_dev_deps)

.PHONY: clean
clean: clean-composer-dev-deps

#
# base composer steps
#

$(composer_dev_deps): composer.json composer.lock
	$(COMPOSER_BIN) install --dev

.PHONY: clean-composer-dev-deps
clean-composer-dev-deps:
	rm -Rf $(composer_dev_deps)
	rm -Rf vendor
	rm -Rf vendor-bin/**/vendor vendor-bin/**/composer.lock

composer.lock: composer.json
	@echo composer.lock is not up to date.

vendor: composer.lock
	$(COMPOSER_BIN) install --no-dev

vendor/bamarni/composer-bin-plugin: composer.lock
	composer install

vendor-bin/owncloud-codestyle/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/owncloud-codestyle/composer.lock
	composer bin owncloud-codestyle install --no-progress

vendor-bin/owncloud-codestyle/composer.lock: vendor-bin/owncloud-codestyle/composer.json
	@echo owncloud-codestyle composer.lock is not up to date.

vendor-bin/php_codesniffer/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/php_codesniffer/composer.lock
	composer bin php_codesniffer install --no-progress

vendor-bin/php_codesniffer/composer.lock: vendor-bin/php_codesniffer/composer.json
	@echo php_codesniffer composer.lock is not up to date.

vendor-bin/phpstan/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/phpstan/composer.lock
	composer bin phpstan install --no-progress

vendor-bin/phpstan/composer.lock: vendor-bin/phpstan/composer.json
	@echo phpstan composer.lock is not up to date.

vendor-bin/phan/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/phan/composer.lock
	composer bin phan install --no-progress

vendor-bin/phan/composer.lock: vendor-bin/phan/composer.json
	@echo phan composer.lock is not up to date.

vendor-bin/behat/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/behat/composer.lock
	composer bin behat install --no-progress

vendor-bin/behat/composer.lock: vendor-bin/behat/composer.json
	@echo behat composer.lock is not up to date.

##
## Build targets
##----------------------

.PHONY: dist
dist:                       ## Build distribution
dist: vendor distdir sign package

.PHONY: distdir
distdir:
	rm -rf $(dist_dir)
	mkdir -p $(dist_dir)/$(app_name)
	cp -R $(all_src) $(dist_dir)/$(app_name)

.PHONY: sign
sign:
ifdef CAN_SIGN
	$(sign) --path="$(dist_dir)/$(app_name)"
else
	@echo $(sign_skip_msg)
endif

.PHONY: package
package:
	tar -czf $(dist_dir)/$(app_name).tar.gz -C $(dist_dir) $(app_name)


#
# Tests
#

.PHONY: test-php-style
test-php-style: vendor-bin/owncloud-codestyle/vendor vendor-bin/php_codesniffer/vendor
	$(PHP_CS_FIXER) fix -v --diff --dry-run --allow-risky yes
	$(PHP_CODESNIFFER) --runtime-set ignore_warnings_on_exit --standard=phpcs.xml tests/acceptance

.PHONY: test-php-style-fix
test-php-style-fix: vendor-bin/owncloud-codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --allow-risky yes

.PHONY: test-php-phpstan
test-php-phpstan: vendor-bin/phpstan/vendor
	$(PHPSTAN) analyse --memory-limit=4G --configuration=./phpstan.neon --no-progress --level=5 appinfo lib

.PHONY: test-php-phan
test-php-phan: vendor-bin/phan/vendor
	$(PHAN) --config-file .phan/config.php --require-config-exists

.PHONY: test-php-unit
test-php-unit: $(composer_deps) $(composer_dev_deps)
	$(PHPUNIT) --configuration ./phpunit.xml --testsuite unit

.PHONY: test-php-unit-dbg
test-php-unit-dbg: $(composer_deps) $(composer_dev_deps)
	$(PHPUNITDBG) --configuration ./phpunit.xml --testsuite unit --coverage-clover ./tests/output/clover.xml

.PHONY: test-php-integration
test-php-integration:
	$(PHPUNIT) --configuration ./phpunit.xml --testsuite integration

.PHONY: test-php-integration-dbg
test-php-integration-dbg:
	$(PHPUNITDBG) --configuration ./phpunit.xml --testsuite integration --coverage-clover ./tests/output/clover.xml

.PHONY: test-acceptance-cli
test-acceptance-cli:
test-acceptance-cli: $(acceptance_test_deps)
	BEHAT_BIN=$(BEHAT_BIN) ../../tests/acceptance/run.sh --config tests/acceptance/config/behat.yml --type cli
