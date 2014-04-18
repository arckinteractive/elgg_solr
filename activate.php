<?php

$batch_size = elgg_get_plugin_setting('reindex_batch_size', 'elgg_solr');

if (empty($batch_size)) {
	elgg_set_plugin_setting('reindex_batch_size', 50, 'elgg_solr');
}

$extract = elgg_get_plugin_setting('extract_handler', 'elgg_solr');

if (empty($extract)) {
	elgg_set_plugin_setting('extract_handler', 'yes', 'elgg_solr');
}