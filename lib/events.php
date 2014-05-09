<?php

function elgg_solr_add_update_entity($event, $type, $entity) {
	$debug = false;
	if (elgg_get_config('elgg_solr_debug')) {
		$debug = true;
	}
	
	
	if (!elgg_instanceof($entity)) {
		if ($debug) {
			elgg_solr_debug_log('Not a valid elgg entity');
		}
		return true;
	}
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		if ($debug) {
			elgg_solr_debug_log('Not a registered entity type');
		}
		return true;
	}
	
	$function = elgg_solr_get_solr_function($entity->type, $entity->getSubtype());
	
	if (is_callable($function)) {
		if ($debug) {
			elgg_solr_debug_log('processing entity with function - ' . $function);
		}
		
		$function($entity);
	}
	else {
		if ($debug) {
			elgg_solr_debug_log('Not a callable function - ' . $function);
		}
	}
}



function elgg_solr_delete_entity($event, $type, $entity) {
	
	if (!elgg_instanceof($entity)) {
		return true;
	}
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		return true;
	}

	$client = elgg_solr_get_client();
	$query = $client->createUpdate();
	$query->addDeleteById($entity->guid);
	$query->addCommit();
	$client->update($query);

    return true;
}



function elgg_solr_metadata_update($event, $type, $metadata) {
	$guids = elgg_get_config('elgg_solr_sync');
	$guids[$metadata->entity_guid] = 1; // use key to keep it unique
	
	elgg_set_config('elgg_solr_sync', $guids);
}



function elgg_solr_add_update_annotation($event, $type, $annotation) {
	if (!($annotation instanceof ElggAnnotation)) {
		return true;
	}
	
	if ($annotation->name != 'generic_comment') {
		return true;
	}
	
	$client = elgg_solr_get_client();
	$commit = elgg_get_config('elgg_solr_nocommit') ? false : true;
	
	$query = $client->createUpdate();
	
	// add document
	$doc = $query->createDocument();
	$doc->id = 'annotation:' . $annotation->id;
	$doc->type = 'annotation';
	$doc->subtype = $annotation->name;
	$doc->owner_guid = $annotation->owner_guid;
	$doc->container_guid = $annotation->entity_guid;
	$doc->access_id = $annotation->access_id;
	$doc->description = $annotation->value;
	$doc->time_created = $annotation->time_created;
	
	$query->addDocument($doc);
	if ($commit) {
		$query->addCommit($commit);
	}

	// this executes the query and returns the result
	try {
		$client->update($query);	
	} catch (Exception $exc) {
		error_log($exc->getMessage());
	}
		
	return true;
}


function elgg_solr_delete_annotation($event, $type, $annotation) {

	if ($annotation->name != 'generic_comment') {
		return true;
	}

	$client = elgg_solr_get_client();
	$query = $client->createUpdate();
	$query->addDeleteById('annotation:' . $annotation->id);
	$query->addCommit();
	$client->update($query);

    return true;
}


// reindexes entities by guid
// happens after shutdown thanks to vroom
// entity guids stored in config
function elgg_solr_entities_sync() {
	$guids = elgg_get_config('elgg_solr_sync');
	
	if (!$guids) {
		return true;
	}
	
	$options = array(
		'guids' => array_keys($guids),
		'limit' => false
	);
	
	$batch_size = elgg_get_plugin_setting('reindex_batch_size', 'elgg_solr');
	$entities = new ElggBatch('elgg_get_entities', $options, null, $batch_size);
	
	foreach ($entities as $e) {
		elgg_solr_add_update_entity(null, null, $e);
	}
}


function elgg_solr_profile_update($event, $type, $entity) {
	elgg_solr_add_update_user($entity);
}


function elgg_solr_upgrades() {
	$ia = elgg_set_ignore_access(true);
	elgg_load_library('elgg_solr:upgrades');
	
	run_function_once('elgg_solr_upgrade_20140504b');
	
	elgg_set_ignore_access($ia);
}

function elgg_solr_disable_entity($event, $type, $entity) {
	if (elgg_instanceof($entity, $type)) {
		elgg_solr_delete_entity(null, null, $entity);
	}
}

function elgg_solr_enable_entity($event, $type, $entity) {
	if (elgg_instanceof($entity, $type)) {
		elgg_solr_add_update_entity(null, null, $entity);
	}
}