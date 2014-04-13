<?php

//@TODO - this can take a long time - vroom it!

set_time_limit(0);

$debug = get_input('debug', false);
if ($debug) {
	elgg_set_config('elgg_solr_debug', 1);
}

$registered_types = get_registered_entity_types();

$ia = elgg_set_ignore_access(true);

elgg_solr_push_doc('<delete><query>*:*</query></delete>');

elgg_set_config('elgg_solr_nocommit', true); // tell our indexer not to commit right away

$count = 0;
foreach ($registered_types as $type => $subtypes) {
	$options = array(
		'type' => $type,
		'limit' => false
	);

	if ($subtypes) {
		$options['subtypes'] = $subtypes;
	}

	$entities = new ElggBatch('elgg_get_entities', $options);

	foreach ($entities as $e) {

		$count++;
		if ($count % 100) {
			elgg_set_config('elgg_solr_nocommit', false); // push a commit on this one
		}
		elgg_solr_add_update_entity(null, null, $e);

		elgg_set_config('elgg_solr_nocommit', true);
	}
}

if ($debug) {
	elgg_solr_debug_log($count . ' entities sent to Solr');
}

elgg_set_ignore_access($ia);
