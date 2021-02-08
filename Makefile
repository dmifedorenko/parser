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