.PHONY: web
web:
	php -S localhost:8000 -t web

zip: questions.db
	gzip questions.db
