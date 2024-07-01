.PHONY: web
web:
	php -S localhost:8000 -f index.php

zip: questions.db
	gzip questions.db
