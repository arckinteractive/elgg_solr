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
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		return true;
	}

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $update = $client->createUpdate();

    // add the delete id and a commit command to the update query
    $update->addDeleteById($entity->guid);
    $update->addCommit();

    try {
        $result = $client->update($update);
    } catch( Exception $e) {
        error_log("elgg_solr_delete_object() - GUID:{$entity->guid} - " . $e->getMessage());
    }

    return true;
}



function elgg_solr_metadata_update($event, $type, $metadata) {
	
	// short circuit if it's our own metadata
	if ($metadata->name == 'elgg_solr_reindex') {
		return true;
	}
	
	// any time we're updating metadata we may need to reindex the entity
	$entity = get_entity($metadata->entity_guid);
	if ($entity && !$entity->elgg_solr_reindex && is_registered_entity_type($entity->type, $entity->getSubtype())) {
		$entity->elgg_solr_reindex = 1;
	}
	
	return true;
}