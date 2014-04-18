<?php

if (elgg_get_plugin_setting('reindex_running', 'elgg_solr')) {
	register_error(elgg_echo('elgg_solr:error:index_running'));
	forward(REFERER);
}

//vroomed
elgg_register_event_handler('shutdown', 'system', 'elgg_solr_reindex');

system_message(elgg_echo('elgg_solr:success:reindex'));
