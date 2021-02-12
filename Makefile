nginx_log=var/log/nginx/project_error.log

nginx_logs:
	echo ${nginx_log}
	touch ${nginx_log}
	tail -f ${nginx_log}

up:
	rm -f ${nginx_log}
	brew services restart nginx
	brew services restart php
	nginx_logs

down:
	brew services stop nginx
	brew services stop php

config_nginx:
	~/subl /usr/local/etc/nginx/nginx.conf

kafema:
	php bin/console app:parse kafema

phpstan:
	vendor/friendsofphp/php-cs-fixer/php-cs-fixer fix -vvv --allow-risky=yes src
	#vendor/bin/phpcs --standard=phpcs.xml -p -q --warning-severity=0 --report-width=9000 "src"
	#vendor/bin/phpstan analyse --no-progress --no-ansi  --memory-limit=-1 "src"
