DRUPAL_FRESH_TARGETS := up build solr-create-core sync post-install

PHONY += solr-create-core
solr-create-core: ## Create Solr core
	docker-compose exec solr make create core=default -f /usr/local/bin/actions.mk || true
