<?php

function elgg_solr_add_update_entity($event, $type, $entity) {
	
	if (elgg_get_config('solr_is_indexing')) {
		// this flag indicates that we're already updating this entity
		// an event handler may have triggered an event leading to 
		// an infinite loop, and we're breaking it
		return true;
	}
			
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

		// make sure info indexed isn't dependent on the access
		// of the logged in user
		$ia = elgg_set_ignore_access(true);
		elgg_set_config('solr_is_indexing', true);
		$function($entity);
		elgg_set_config('solr_is_indexing', false);
		elgg_set_ignore_access($ia);
	} else {
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

	// if shutdown just do it, otherwise defer
	if ($GLOBALS['shutdown_flag']) {
		$client = elgg_solr_get_client();
		$query = $client->createUpdate();
		$query->addDeleteById($entity->guid);
		$query->addCommit();

		try {
			$client->update($query);
		} catch (Exception $ex) {
			//something went wrong, lets cache the id and try again on cron
			elgg_get_site_entity()->annotate('elgg_solr_delete_cache', $entity->guid, ACCESS_PUBLIC);
		}
	} else {
		elgg_solr_defer_index_delete($entity->guid);
	}

	return true;
}

function elgg_solr_metadata_update($event, $type, $metadata) {
	if ($GLOBALS['shutdown_flag']) {
		$entity = get_entity($metadata->entity_guid);
		if ($entity) {
			elgg_solr_add_update_entity(null, null, $entity);
		}
	}
	else {
		elgg_solr_defer_index_update($metadata->entity_guid);
	}
}


function elgg_solr_annotation_update($event, $type, $annotation) {
		
	$entity = $annotation->getEntity();
	if ($GLOBALS['shutdown_flag']) {
		elgg_solr_add_update_entity(null, null, $entity);
		elgg_solr_index_annotation($annotation);
	}
	else {
		elgg_solr_defer_index_update($entity->guid);
		elgg_solr_defer_annotation_update($annotation->id);
	}
}

// reindexes entities by guid
// happens after shutdown thanks to vroom
// entity guids stored in config
function elgg_solr_entities_sync() {

	$access = access_get_show_hidden_status();
	access_show_hidden_entities(true);
	$guids = elgg_get_config('elgg_solr_sync');

	if ($guids) {
		$options = array(
			'guids' => array_keys($guids),
			'limit' => false
		);

		$ia = elgg_set_ignore_access(true);
		$batch_size = elgg_get_plugin_setting('reindex_batch_size', 'elgg_solr');
		$entities = new ElggBatch('elgg_get_entities', $options, null, $batch_size);

		foreach ($entities as $e) {
			elgg_solr_add_update_entity(null, null, $e);
		}
		elgg_set_ignore_access($ia);
	}

	// reset the config
	elgg_set_config('elgg_solr_sync', array());

	$delete_guids = elgg_get_config('elgg_solr_delete');

	if (is_array($delete_guids)) {
		foreach ($delete_guids as $g => $foo) {
			$client = elgg_solr_get_client();
			$query = $client->createUpdate();
			$query->addDeleteById($g);
			$query->addCommit();

			try {
				$client->update($query);
			} catch (Exception $ex) {
				//something went wrong, lets cache the id and try again on cron
				elgg_get_site_entity()->annotate('elgg_solr_delete_cache', $g, ACCESS_PUBLIC);
			}
		}
	}

	// reset the config
	elgg_set_config('elgg_solr_delete', array());

	access_show_hidden_entities($access);
}

function elgg_solr_profile_update($event, $type, $entity) {
	if ($GLOBALS['shutdown_flag']) {
		elgg_solr_add_update_entity(null, null, $entity);
	} else {
		$guids = elgg_get_config('elgg_solr_sync');
		if (!is_array($guids)) {
			$guids = array();
		}
		$guids[$entity->guid] = 1; // use key to keep it unique

		elgg_set_config('elgg_solr_sync', $guids);
	}
}

function elgg_solr_upgrades() {
	$ia = elgg_set_ignore_access(true);
	
	require_once __DIR__ . '/upgrades.php';

	elgg_set_ignore_access($ia);
}

function elgg_solr_disable_entity($event, $type, $entity) {
	if ($GLOBALS['shutdown_flag']) {
		elgg_solr_add_update_entity(null, null, $entity);
	} else {
		elgg_solr_defer_index_update($entity->guid);
	}
}

function elgg_solr_enable_entity($event, $type, $entity) {
	if ($GLOBALS['shutdown_flag']) {
		elgg_solr_add_update_entity(null, null, $entity);
	} else {
		elgg_solr_defer_index_update($entity->guid);
	}
}

function elgg_solr_relationship_create($event, $type, $relationship) {
	if ($GLOBALS['shutdown_flag']) {
		$entity1 = get_entity($relationship->guid_one);
		$entity2 = get_entity($relationship->guid_two);
		elgg_solr_add_update_entity(null, null, $entity1);
		elgg_solr_add_update_entity(null, null, $entity2);
	}
	else {
		elgg_solr_defer_index_update($relationship->guid_one);
		elgg_solr_defer_index_update($relationship->guid_two);
	}
}

//@TODO - this fires before the relationship is actually deleted
// but there's no :after event for relationships
// add to core, or relationships deleted on shutdown will be incorrectly indexed
function elgg_solr_relationship_delete($event, $type, $relationship) {
	if ($GLOBALS['shutdown_flag']) {
		$entity1 = get_entity($relationship->guid_one);
		$entity2 = get_entity($relationship->guid_two);
		elgg_solr_add_update_entity(null, null, $entity1);
		elgg_solr_add_update_entity(null, null, $entity2);
	}
	else {
		elgg_solr_defer_index_update($relationship->guid_one);
		elgg_solr_defer_index_update($relationship->guid_two);
	}
}


function elgg_solr_annotation_delete($event, $type, $annotation) {

	if (!($annotation instanceof ElggAnnotation)) {
		return true;
	}

	if ($GLOBALS['shutdown_flag']) {
		$client = elgg_solr_get_client();
		$query = $client->createUpdate();
		$query->addDeleteById('annotation:' . $annotation->id);
		$query->addCommit();

		try {
			$client->update($query);
		} catch (Exception $exc) {
			elgg_get_site_entity()->annotate('elgg_solr_delete_cache', 'annotation:' . $annotation->id, ACCESS_PUBLIC);
		}
	} else {
		elgg_solr_defer_annotation_delete($annotation->id);
	}

	return true;
}

function elgg_solr_annotations_sync() {

	$access = access_get_show_hidden_status();
	access_show_hidden_entities(true);
	$ia = elgg_set_ignore_access(true);
	
	$ids = elgg_get_config('elgg_solr_annotation_update');

	if (is_array($ids)) {
		foreach ($ids as $id => $foo) {
			$annotation = elgg_get_annotation_from_id($id);
			if (!$annotation) {
				continue;
			}

			elgg_solr_index_annotation($annotation);
		}
	}

	$delete_ids = elgg_get_config('elgg_solr_annotation_delete');

	if (is_array($delete_ids)) {
		foreach ($delete_ids as $g => $foo) {
			$client = elgg_solr_get_client();
			$query = $client->createUpdate();
			$query->addDeleteById('annotation:' . $g);
			$query->addCommit();

			try {
				$client->update($query);
			} catch (Exception $exc) {
				elgg_get_site_entity()->annotate('elgg_solr_delete_cache', 'annotation:' . $g, ACCESS_PUBLIC);
			}
		}
	}

	access_show_hidden_entities($access);
	elgg_set_ignore_access($ia);
}

/*
 * defer indexing for river affected items
 */
function elgg_solr_river_creation($event, $type, $river) {
	elgg_solr_defer_index_update($river->subject_guid);
	elgg_solr_defer_index_delete($river->object_guid);
	elgg_solr_defer_index_update($river->target_guid);

	if (elgg_is_logged_in()) {
		elgg_solr_defer_index_update(elgg_get_logged_in_user_guid());
	}
}