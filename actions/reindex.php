<?php

if (elgg_get_plugin_setting('reindex_running', 'elgg_solr')) {
	register_error(elgg_echo('elgg_solr:error:index_running'));
	forward(REFERER);
}

$starttime = get_input('starttime');
$endtime = get_input('endtime');
$type = get_input('type');

switch ($type) {
	case 'annotation':
		
		$indexable = _elgg_services()->hooks->trigger('elgg_solr:can_index', 'annotation', [], []);
		if ($indexable) {
			elgg_register_event_handler('shutdown', 'system', 'elgg_solr_annotation_reindex');
			
			elgg_set_config('elgg_solr_reindex_annotation_options', $indexable);
		}
		break;
	case '':
	case 'full':
		//vroomed
		elgg_register_event_handler('shutdown', 'system', 'elgg_solr_reindex');
		elgg_register_event_handler('shutdown', 'system', 'elgg_solr_annotation_reindex');
		break;
	default:
		// set up options to use instead of all registered types
		$subtypes = get_input('subtype', array());
		if (empty($subtypes)) {
			$subtypes = $subtypes;
		} else if (!is_array($subtypes)) {
			$subtypes = array($subtypes);
		}
		$types = array($type => $subtypes);
		elgg_set_config('elgg_solr_reindex_options', $types);
		elgg_register_event_handler('shutdown', 'system', 'elgg_solr_reindex');		
		break;
}

if ($starttime && $endtime) {
	$time = array(
		'starttime' => $starttime,
		'endtime' => $endtime
	);
	
	elgg_set_config('elgg_solr_time_options', $time);
}

system_message(elgg_echo('elgg_solr:success:reindex'));
forward(REFERER);
