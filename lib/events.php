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
	
	elgg_solr_push_doc('<delete><query>id:' . $entity->guid . '</query></delete>');

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



function elgg_solr_add_update_annotation($event, $type, $annotation) {
	if (!($annotation instanceof ElggAnnotation)) {
		return true;
	}
	
	if ($annotation->name != 'generic_comment') {
		return true;
	}

    $description = elgg_solr_xml_format($annotation->value);
	$subtype = elgg_solr_xml_format($annotation->name);

    // Build the user document to be posted
    $doc = <<<EOF
        <add>
            <doc>
				<field name="id">annotation:{$annotation->id}</field>
				<field name="description">{$description}</field>
                <field name="type">annotation</field>
				<field name="subtype">{$subtype}</field>
				<field name="access_id">{$annotation->access_id}</field>
				<field name="container_guid">{$annotation->entity_guid}</field>
				<field name="owner_guid">{$annotation->owner_guid}</field>
				<field name="time_created">{$annotation->time_created}</field>
            </doc>
        </add>
EOF;

	elgg_solr_push_doc($doc);
}


function elgg_solr_delete_annotation($event, $type, $annotation) {

	if ($annotation->name != 'generic_comment') {
		return true;
	}

	elgg_solr_push_doc('<delete><query>id:annotation\:' . $annotation->id . '</query></delete>');

    return true;
}
