DRUPAL_FRESH_TARGETS := up build solr-create-core sync post-install
DRUPAL_POST_INSTALL_TARGETS := drush-updb drush-cim solr-reindex drush-cr drush-uli

PHONY += solr-create-core
solr-create-core: ## Create Solr core
	docker-compose exec solr make create core=default -f /usr/local/bin/actions.mk || true

PHONY += solr-reindex
solr-reindex: ## Solr reindex
	$(call step,Reindex Solr...)
	$(call drush_on_${RUN_ON},sapi-c)
	$(call drush_on_${RUN_ON},sapi-i)
