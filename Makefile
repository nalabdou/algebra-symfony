.PHONY: install test unit integration coverage cs cs-fix stan ci clean

install:
	composer install

test:
	vendor/bin/phpunit --no-coverage

unit:
	vendor/bin/phpunit --testsuite Unit --no-coverage

integration:
	vendor/bin/phpunit --testsuite Integration --no-coverage

coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit \
		--coverage-html coverage/html \
		--coverage-clover coverage/clover.xml
	@echo "Coverage report: coverage/html/index.html"

cs:
	vendor/bin/php-cs-fixer check --diff --ansi

cs-fix:
	vendor/bin/php-cs-fixer fix --ansi

stan:
	vendor/bin/phpstan analyse --no-progress --memory-limit=256M

ci: cs stan test
	@echo ""
	@echo "✅  All CI checks passed."

clean:
	rm -rf vendor coverage .php-cs-fixer.cache .phpunit.result.cache .phpunit.cache
