<?php

$batch_size = elgg_get_plugin_setting('reindex_batch_size', 'elgg_solr');

if (empty($batch_size)) {
	elgg_set_plugin_setting('reindex_batch_size', 50, 'elgg_solr');
}