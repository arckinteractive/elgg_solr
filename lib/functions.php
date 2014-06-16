<?php

function elgg_solr_reindex() {
	set_time_limit(0);
	
	// lock the function
	elgg_set_plugin_setting('reindex_running', 1, 'elgg_solr');

	$debug = get_input('debug', false);
	if ($debug) {
		elgg_set_config('elgg_solr_debug', 1);
	}

	if (elgg_get_config('elgg_solr_reindex_options')) {
		$registered_types = elgg_get_config('elgg_solr_reindex_options');
	}
	else {
		$registered_types = get_registered_entity_types();
	}

	$ia = elgg_set_ignore_access(true);

	elgg_set_config('elgg_solr_nocommit', true); // tell our indexer not to commit right away

	$count = 0;
	foreach ($registered_types as $type => $subtypes) {
		$options = array(
			'type' => $type,
			'limit' => false
		);
		
		$time = elgg_get_config('elgg_solr_time_options');
		if ($time && is_array($time)) {
			$options['wheres'] = array(
				"e.time_created >= {$time['starttime']}",
				"e.time_created <= {$time['endtime']}",
			);
		}

		if ($subtypes) {
			if (!is_array($subtypes)) {
				$subtypes = array($subtypes);
			}
			$options['subtypes'] = $subtypes;
		}

		$batch_size = elgg_get_plugin_setting('reindex_batch_size', 'elgg_solr');
		$entities = new ElggBatch('elgg_get_entities', $options, null, $batch_size);

		foreach ($entities as $e) {

			$count++;
			if ($count % 100) {
				elgg_set_config('elgg_solr_nocommit', false); // push a commit on this one
			}
			elgg_solr_add_update_entity(null, null, $e);

			elgg_set_config('elgg_solr_nocommit', true);
		}
	}
	
	elgg_set_plugin_setting('reindex_running', 0, 'elgg_solr');
	
	// commit the last of the entities
	$client = elgg_solr_get_client();
	$query = $client->createUpdate();
	$query->addCommit();
	$client->update($query);
	elgg_set_ignore_access($ia);
}


function elgg_solr_comment_reindex() {
	set_time_limit(0);
	
	$debug = get_input('debug', false);
	if ($debug) {
		elgg_set_config('elgg_solr_debug', 1);
	}
	
	// lock the function
	elgg_set_plugin_setting('reindex_running', 1, 'elgg_solr');
	
	$ia = elgg_set_ignore_access(true);

	elgg_set_config('elgg_solr_nocommit', true); // tell our indexer not to commit right away

	$count = 0;
	
	// index comments
	$options = array(
		'annotation_name' => 'generic_comment',
		'limit' => false
	);
	
	$time = elgg_get_config('elgg_solr_time_options');
	if ($time && is_array($time)) {
		$options['annotation_created_time_lower'] = $time['starttime'];
		$options['annotation_created_time_upper'] = $time['endtime'];
	}
	
	$batch_size = elgg_get_plugin_setting('reindex_batch_size', 'elgg_solr');
	$comments = new ElggBatch('elgg_get_annotations', $options, null, $batch_size);
	
	foreach ($comments as $comment) {
		$count++;
		if ($count % 10000) {
			elgg_set_config('elgg_solr_nocommit', false); // push a commit on this one
		}
		elgg_solr_add_update_annotation(null, null, $comment);

		elgg_set_config('elgg_solr_nocommit', true);
	}

	if ($debug) {
		elgg_solr_debug_log($count . ' entities sent to Solr');
	}
	
	elgg_set_ignore_access($ia);
	elgg_set_plugin_setting('reindex_running', 0, 'elgg_solr');
}


function elgg_solr_get_indexable_count() {
	$registered_types = get_registered_entity_types();

	$ia = elgg_set_ignore_access(true);

	$count = 0;
	foreach ($registered_types as $type => $subtypes) {
		$options = array(
			'type' => $type,
			'count' => true
		);

		if ($subtypes) {
			$options['subtypes'] = $subtypes;
		}

		$count += elgg_get_entities($options);
	}
	
	// count comments
	$options = array(
		'annotation_name' => 'generic_comment',
		'count' => true
	);
	$count += elgg_get_annotations($options);
	
	elgg_set_ignore_access($ia);
	
	return $count;
}


function elgg_solr_get_indexed_count($query = '*:*', $fq = array()) {
	$select = array(
        'query'  => $query,
        'start'  => 0,
        'rows'   => 1,
        'fields' => array('id'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	
	if (!empty($fq)) {
        foreach ($fq as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }
	
	try {
        $resultset = $client->select($query);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
	
	return $resultset->getNumFound();
}


function elgg_solr_get_client() {
	elgg_load_library('Solarium');
	
	Solarium\Autoloader::register();
	
	$options = elgg_solr_get_adapter_options();

	$config = array('endpoint' => array(
		'localhost' => $options
		));

	// create a client instance
	return new Solarium\Client($config);
}


function elgg_solr_get_adapter_options() {
	return array(
		'host' => elgg_get_plugin_setting('host', 'elgg_solr'),
		'port' => elgg_get_plugin_setting('port', 'elgg_solr'),
		'path' => elgg_get_plugin_setting('solr_path', 'elgg_solr'),
		'core' => elgg_get_plugin_setting('solr_core', 'elgg_solr'),
	);
}


function elgg_solr_has_settings() {
	$host = elgg_get_plugin_setting('host', 'elgg_solr');
	$port = elgg_get_plugin_setting('port', 'elgg_solr');
	$path = elgg_get_plugin_setting('path', 'elgg_solr');
	
	if (empty($host) || empty($port) || empty($path)) {
		return false;
	}
	
	return true;
}



/**
 * get default filter queries based on search params
 * 
 * @param type $params
 * 
 * return array
 */
function elgg_solr_get_default_fq($params) {
	$fq = array();
	
	// type/types
	if (isset($params['type']) && $params['type'] !== ELGG_ENTITIES_ANY_VALUE) {
		if ($params['type'] === ELGG_ENTITIES_NO_VALUE) {
			//$fq['type'] = '-type:[* TO *]';
			$fq['type'] = 'type:""';
		}
		else {
			$fq['type'] = 'type:' . $params['type'];
		}
	}
	
	if ($params['types'] && $params['types'] !== ELGG_ENTITIES_ANY_VALUE) {
		if (is_array($params['types'])) {
			$fq['type'] = 'type:(' . implode(' OR ', $params['types']) . ')';
		}
		else {
			if ($params['types'] === ELGG_ENTITIES_NO_VALUE) {
				//$fq['type'] = '-type:[* TO *]';
				$fq['type'] = 'type:""';
			}
			else {
				$fq['type'] = 'type:' . $params['types'];
			}
		}
	}
	
	//subtype
	if (isset($params['subtype']) && $params['subtype'] !== ELGG_ENTITIES_ANY_VALUE) {
		if ($params['subtype'] === ELGG_ENTITIES_NO_VALUE) {
			//$fq['subtype'] = '-subtype:[* TO *]';
			$fq['subtype'] = 'subtype:""';
		}
		else {
			$fq['subtype'] = 'subtype:' . $params['subtype'];
		}
	}
	
	if (isset($params['subtypes']) && $params['subtypes'] !== ELGG_ENTITIES_ANY_VALUE) {
		if (is_array($params['subtypes'])) {
			$fq['subtype'] = 'subtype:(' . implode(' OR ', $params['subtypes']) . ')';
		}
		else {
			if ($params['subtypes'] === ELGG_ENTITIES_NO_VALUE) {
				//$fq['subtype'] = '-subtype:[* TO *]';
				$fq['subtype'] = 'subtype:""';
			}
			else {
				$fq['subtype'] = 'subtype:' . $params['subtypes'];
			}
		}
	}
	
	
	//container
	if (isset($params['container_guid']) && $params['container_guid'] !== ELGG_ENTITIES_ANY_VALUE) {
			$fq['container'] = 'container_guid:' . $params['container_guid'];
	}
	
	if (isset($params['container_guids'])&& $params['container_guids'] !== ELGG_ENTITIES_ANY_VALUE) {
		if (is_array($params['container_guids'])) {
			$fq['container'] = 'container_guid:(' . implode(' OR ', $params['container_guids']) . ')';
		}
		else {
				$fq['container'] = 'container_guid:' . $params['container_guid'];
		}
	}
	
	//owner
	if (isset($params['owner_guid']) && $params['owner_guid'] !== ELGG_ENTITIES_ANY_VALUE) {
		$fq['owner'] = 'owner_guid:' . $params['owner_guid'];
	}
	
	if (isset($params['owner_guids']) && $params['owner_guids'] !== ELGG_ENTITIES_ANY_VALUE) {
		if (is_array($params['owner_guids'])) {
			$fq['owner'] = 'owner_guid:(' . implode(' OR ', $params['owner_guids']) . ')';
		}
		else {
				$fq['owner'] = 'owner_guid:' . $params['owner_guid'];
		}
	}
	
	$access_query = elgg_solr_get_access_query();
	if ($access_query) {
		$fq['access'] = $access_query;
	}

	return $fq;
}



/**
 * Register a function to define specific configuration of an entity in solr
 * 
 * @param type $type - the entity type
 * @param type $subtype - the entity subtype
 * @param type $function - the function to call for updating an entity in solr
 */
function elgg_solr_register_solr_entity_type($type, $subtype, $function) {
	$solr_entities = elgg_get_config('solr_entities');
	
	if (!is_array($solr_entities)) {
		$solr_entities = array();
	}
	
	$solr_entities[$type][$subtype] = $function;
	
	elgg_set_config('solr_entities', $solr_entities);
}


/**
 * 
 * 
 * @param type $type
 * @param type $subtype
 * @return boolean
 */
function elgg_solr_get_solr_function($type, $subtype) {
	if (elgg_get_config('elgg_solr_debug')) {
		$debug = true;
	}
	
	$solr_entities = elgg_get_config('solr_entities');
	
	if (is_callable($solr_entities[$type][$subtype])) {
		return $solr_entities[$type][$subtype];
	}
	
	if (is_callable($solr_entities[$type]['default'])) {
		return $solr_entities[$type]['default'];
	}
	
	if (is_callable($solr_entities['entity']['default'])) {
		return $solr_entities['entity']['default'];
	}
	
	if ($debug) {
		elgg_solr_debug_log('Solr function not callable for type: ' . $type . ', subtype: ' . $subtype);
	}
	
	return false;
}

/**
 * Index a file entity
 * 
 * @param type $entity
 * @return boolean
 */
function elgg_solr_add_update_file($entity) {
	   
	$client = elgg_solr_get_client();
	$commit = elgg_get_config('elgg_solr_nocommit') ? false : true;
	
	$extract = elgg_get_plugin_setting('extract_handler', 'elgg_solr');
	$extracting = false;
	if (file_exists($entity->getFilenameOnFilestore()) && $extract == 'yes') {
		$extracting = true;
	}
	
	if ($extracting) {
		// get an extract query instance and add settings
		$query = $client->createExtract();
		$query->setFile($entity->getFilenameOnFilestore());
		$query->addFieldMapping('content', 'attr_content');
		$query->setUprefix('attr_');
		$query->setOmitHeader(true);
	}
	else {
		$query = $client->createUpdate();
	}
		
	// add document
	$doc = $query->createDocument();
	$doc->id = $entity->guid;
	$doc->type = $entity->type;
	$doc->subtype = $entity->getSubtype();
	$doc->owner_guid = $entity->owner_guid;
	$doc->container_guid = $entity->container_guid;
	$doc->access_id = $entity->access_id;
	$doc->title = elgg_strip_tags($entity->title);
	$doc->description = elgg_strip_tags($entity->description);
	$doc->time_created = $entity->time_created;
	$doc->tags = elgg_solr_get_tags_array($entity);
	
	//@TODO - investigate these
	// set a document boost value
	//$document->setBoost(2.5);

	// set a field boost
	//$document->setFieldBoost('population', 4.5);
	//$document->setField('title', $entity->title, 4.5);
				
	if ($extracting) {
		$query->setDocument($doc);
		if ($commit) {
			$query->setCommit(true);
		}
		try {
			$client->extract($query);	
		} catch (Exception $exc) {
			error_log($exc->getMessage());
		}
	}
	else {
		$query->addDocument($doc);
		if ($commit) {
			$query->addCommit();
		}
		
		try {
			$client->update($query);	
		} catch (Exception $exc) {
			error_log($exc->getMessage());
		}
	}
		
	return true;
}

/**
 * Index a generic elgg object
 * 
 * @param type $entity
 * @return boolean
 */
function elgg_solr_add_update_object_default($entity) {
	   
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		return false;
	}
	
	$client = elgg_solr_get_client();
	$commit = elgg_get_config('elgg_solr_nocommit') ? false : true;
	
	$query = $client->createUpdate();
	
	// add document
	$doc = $query->createDocument();
	$doc->id = $entity->guid;
	$doc->type = $entity->type;
	$doc->subtype = $entity->getSubtype();
	$doc->owner_guid = $entity->owner_guid;
	$doc->container_guid = $entity->container_guid;
	$doc->access_id = $entity->access_id;
	$doc->title = elgg_strip_tags($entity->title);
	$doc->name = elgg_strip_tags($entity->name);
	$doc->description = elgg_strip_tags($entity->description);
	$doc->time_created = $entity->time_created;
	$doc->tags = elgg_solr_get_tags_array($entity);
	
	//@TODO - investigate these
	// set a document boost value
	//$document->setBoost(2.5);

	// set a field boost
	//$document->setFieldBoost('population', 4.5);
	//$document->setField('title', $entity->title, 4.5);
				
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


function elgg_solr_add_update_user($entity) {
	
	if (!elgg_instanceof($entity, 'user')) {
		return false;
	}
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		return false;
	}
	
	// lump public profile fields in with description
	$profile_fields = elgg_get_config('profile_fields');
	$desc = '';
	if (is_array($profile_fields) && sizeof($profile_fields) > 0) {
		$walled = elgg_get_config('walled_garden');
		foreach ($profile_fields as $shortname => $valtype) {
			$md = elgg_get_metadata(array(
				'guid' => $entity->guid,
				'metadata_names' => array($shortname)
			));
			
			foreach ($md as $m) {
				if ($m->access_id == ACCESS_PUBLIC || ($walled && $m->access_id == ACCESS_LOGGED_IN)) {
					$desc .= $m->value . ' ';
				}
			}
		}
	}
	
	$client = elgg_solr_get_client();
	$commit = elgg_get_config('elgg_solr_nocommit') ? false : true;
	
	$query = $client->createUpdate();
	
	// add document
	$doc = $query->createDocument();
	$doc->id = $entity->guid;
	$doc->type = $entity->type;
	$doc->subtype = $entity->getSubtype();
	$doc->owner_guid = $entity->owner_guid;
	$doc->container_guid = $entity->container_guid;
	$doc->access_id = $entity->access_id;
	$doc->title = elgg_strip_tags($entity->title);
	$doc->name = elgg_strip_tags($entity->name);
	$doc->username = $entity->username;
	$doc->description = elgg_strip_tags($desc);
	$doc->time_created = $entity->time_created;
	$doc->tags = elgg_solr_get_tags_array($entity);
	
	//@TODO - investigate these
	// set a document boost value
	//$document->setBoost(2.5);

	// set a field boost
	//$document->setFieldBoost('population', 4.5);
	//$document->setField('title', $entity->title, 4.5);
				
	$query->addDocument($doc, true);
	if ($commit) {
		$query->addCommit();
	}

	// this executes the query and returns the result
	try {
		$client->update($query);	
	} catch (Exception $exc) {
		error_log($exc->getMessage());
	}
	return true;
}


function elgg_solr_debug_log($message) {
	error_log($message);
}


function elgg_solr_get_access_query() {
	
	if (elgg_is_admin_logged_in() || elgg_get_ignore_access()) {
		return false; // no access limit
	}
	
	static $return;
	
	if ($return) {
		return $return;
	}
	
	$access = get_access_array();
	
	// access filter query
	if ($access) {
		$access_list = implode(" OR ", $access);
	}
	
	if (elgg_is_logged_in()) {

		// get friends
		// @TODO - is there a better way? Not sure if there's a limit on solr if
		// someone has a whole lot of friends...
		$friends = elgg_get_entities_from_relationship(array(
			'type' => 'user',
			'relationship' => 'friend',
			'relationship_guid' => elgg_get_logged_in_user_guid(),
			'inverse_relationship' => true,
			'limit' => false,
			'callback' => false // keep the query fast
		));
		
		$friend_guids = array();
		foreach ($friends as $friend) {
			$friend_guids[] = $friend->guid;
		}
			
		$friends_list = '';
		if ($friend_guids) {
			$friends_list = elgg_solr_escape_special_chars(implode(' OR ', $friend_guids));
		}
	}

	//$query->createFilterQuery('access')->setQuery("owner_guid: {guid} OR access_id:({$access_list}) OR (access_id:" . ACCESS_FRIENDS . " AND owner_guid:({$friends}))");
	if (elgg_is_logged_in()) {
		$return = "owner_guid:" . elgg_get_logged_in_user_guid();
	}
	else {
		$return = '';
	}
	
	if ($access_list) {
		if ($return) {
			$return .= ' OR ';
		}
		$return .= "access_id:(" . elgg_solr_escape_special_chars($access_list) . ")";
	}
	
	$fr_prefix = '';
	$fr_suffix = '';
	if ($return && $friends_list) {
		$return .= ' OR ';
		$fr_prefix = '(';
		$fr_suffix = ')';
	}
	
	if ($friends_list) {
		$return .= $fr_prefix . 'access_id:' . elgg_solr_escape_special_chars(ACCESS_FRIENDS) . ' AND owner_guid:(' . $friends_list . ')' . $fr_suffix;
	}

	return $return;
}


/**
 * by default there's nothing we need to do different with this
 * so it's just a wrapper for object add
 * 
 * @param type $entity
 */
function elgg_solr_add_update_group_default($entity) {
	elgg_solr_add_update_object_default($entity);
}


function elgg_solr_escape_special_chars($string) {
	// Lucene characters that need escaping with \ are + - && || ! ( ) { } [ ] ^ " ~ * ? : \
	$luceneReservedCharacters = preg_quote('+-&|!(){}[]^"~*?:\\');
	$query = preg_replace_callback('/([' . $luceneReservedCharacters . '])/',
		function($matches) {
			return '\\' . $matches[0];
		},
    $string);
	return $query;
}


/**
 * 
 * @param type $time - timestamp of the start of the block
 * @param type $block - the block of time, hour/day/month/year/all
 * @param type $type
 * @param type $subtype
 * @return type
 */
function elgg_solr_get_stats($time, $block, $type, $subtype) {
	$options = array(
		'type' => $type
	);
	
	$fq = array();
	
	if ($subtype) {
		$options['subtype'] = $subtype;
		$fq['subtype'] = "subtype:{$subtype}";
	}
	else {
		$options['subtype'] = ELGG_ENTITIES_NO_VALUE;
	}
	
	$stats = array();
	switch ($block) {
		case 'minute':
			for ($i=0; $i<60; $i++) {
				$starttime = mktime(date('G', $time), date('i', $time), $i, date('m', $time), date('j', $time), date('Y', $time));
				$endtime = mktime(date('G', $time), date('i', $time), $i+1, date('m', $time), date('j', $time), date('Y', $time));
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_solr_get_system_count($options, $starttime, $endtime);
				
				$stats[date('s', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => false
				);
			}
			break;
		case 'hour':
			for ($i=0; $i<60; $i++) {
				$starttime = mktime(date('G', $time), $i, 0, date('m', $time), date('j', $time), date('Y', $time));
				$endtime = mktime(date('G', $time), $i+1, 0, date('m', $time), date('j', $time), date('Y', $time)) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_solr_get_system_count($options, $starttime, $endtime);
				
				$stats[date('i', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => false
				);
			}
			break;
		case 'day':
			for ($i=0; $i<24; $i++) {
				$starttime = mktime($i, 0, 0, date('m', $time), date('j', $time), date('Y', $time));
				$endtime = mktime($i+1, 0, 0, date('m', $time), date('j', $time), date('Y', $time)) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_solr_get_system_count($options, $starttime, $endtime);
				
				$stats[date('H', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => 'hour'
				);
			}
			break;
		case 'month':
			for ($i=1; $i<date('t', $time)+1; $i++) {
				$starttime = mktime(0, 0, 0, date('m', $time), $i, date('Y', $time));
				$endtime = mktime(0, 0, 0, date('m', $time), $i+1, date('Y', $time)) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_solr_get_system_count($options, $starttime, $endtime);
				
				$stats[date('d', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => 'day'
				);
			}
			break;
		case 'year':
			for ($i=1; $i<13; $i++) {
				$starttime = mktime(0, 0, 0, $i, 1, date('Y', $time));
				$endtime = mktime(0, 0, 0, $i+1, 1, date('Y', $time)) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_solr_get_system_count($options, $starttime, $endtime);
				
				$stats[date('F', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => 'month'
				);
			}
			break;
		
		case 'all':
		default:
			$startyear = date('Y', elgg_get_site_entity()->time_created);
			$currentyear = date('Y');

			for ($i=$currentyear; $i>$startyear -1; $i--) {
				$starttime = mktime(0, 0, 0, 1, 1, $i);
				$endtime = mktime(0, 0, 0, 1, 1, $i+1) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_solr_get_system_count($options, $starttime, $endtime);
				
				$stats[$i] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => 'year'
				);
			}

			break;
	}
	
	return $stats;
}

function elgg_solr_get_comment_stats($time, $block) {
	$type = 'annotation';
	$fq = array(
		'subtype' => "subtype:generic_comment"
	);
	$stats = array();
	switch ($block) {
		case 'hour':
			// I don't think we need minute resolution right now...
			break;
		case 'day':
			for ($i=0; $i<24; $i++) {
				$starttime = mktime($i, 0, 0, date('m', $time), date('j', $time), date('Y', $time));
				$endtime = mktime($i+1, 0, 0, date('m', $time), date('j', $time), date('Y', $time)) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_get_annotations(array(
					'annotation_name' => 'generic_comment',
					'annotation_created_time_lower' => $starttime,
					'annotation_created_time_upper' => $endtime,
					'count' => true
					));
				
				$stats[date('H', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => false
				);
			}
			break;
		case 'month':
			for ($i=1; $i<date('t', $time)+1; $i++) {
				$starttime = mktime(0, 0, 0, date('m', $time), $i, date('Y', $time));
				$endtime = mktime(0, 0, 0, date('m', $time), $i+1, date('Y', $time)) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_get_annotations(array(
					'annotation_name' => 'generic_comment',
					'annotation_created_time_lower' => $starttime,
					'annotation_created_time_upper' => $endtime,
					'count' => true
					));
				
				$stats[date('d', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => 'day'
				);
			}
			break;
		case 'year':
			for ($i=1; $i<13; $i++) {
				$starttime = mktime(0, 0, 0, $i, 1, date('Y', $time));
				$endtime = mktime(0, 0, 0, $i+1, 1, date('Y', $time)) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_get_annotations(array(
					'annotation_name' => 'generic_comment',
					'annotation_created_time_lower' => $starttime,
					'annotation_created_time_upper' => $endtime,
					'count' => true
					));
				
				$stats[date('F', $starttime)] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => 'month'
				);
			}
			break;
		
		case 'all':
		default:
			$startyear = date('Y', elgg_get_site_entity()->time_created);
			$currentyear = date('Y');

			for ($i=$currentyear; $i>$startyear -1; $i--) {
				$starttime = mktime(0, 0, 0, 1, 1, $i);
				$endtime = mktime(0, 0, 0, 1, 1, $i+1) - 1;
				
				$fq['time_created'] = "time_created:[{$starttime} TO {$endtime}]";
				$indexed = elgg_solr_get_indexed_count("type:{$type}", $fq);
				$system = elgg_get_annotations(array(
					'annotation_name' => 'generic_comment',
					'annotation_created_time_lower' => $starttime,
					'annotation_created_time_upper' => $endtime,
					'count' => true
					));
				
				$stats[$i] = array(
					'count' => $system,
					'indexed' => $indexed,
					'starttime' => $starttime,
					'endtime' => $endtime,
					'block' => 'year'
				);
			}

			break;
	}
	
	return $stats;
}


function elgg_solr_get_system_count($options, $starttime, $endtime) {
	$options['wheres'] = array(
		"e.time_created >= {$starttime}",
		"e.time_created <= {$endtime}"
	);
		
	$options['count'] = true;
	
	return (int) elgg_get_entities($options);
}



function elgg_solr_get_display_datetime($time, $block) {

	switch ($block) {
		case 'year':
			$format = 'Y';
			break;
		case 'month':
			$format = 'F Y';
			break;
		case 'day':
			$format = 'F j, Y';
			break;
		case 'hour':
			$format = 'F j, Y H:00';
			break;
		case 'minute':
			$format = 'F j, Y H:i';
			break;
		case 'all':
		default:
			return elgg_echo('elgg_solr:time:all');
			break;
	}
	
	return date($format, $time);
}


/**
 * Returns an array of tags for indexing
 * 
 * @param type $entity
 * @return string
 */
function elgg_solr_get_tags_array($entity) {
	$valid_tag_names = elgg_get_registered_tag_metadata_names();
	
	$t = array();
	if ($valid_tag_names && is_array($valid_tag_names)) {
		foreach ($valid_tag_names as $tagname) {
			$tags = $entity->$tagname;
			if ($tags && !is_array($tags)) {
				$tags = array($tags);
			}
	
			if ($tags && is_array($tags)) {
				foreach ($tags as $tag) {
					$t[] = $tagname . '%%' . $tag;
				}
			}
		}
	}
	
	return $t;
}


function elgg_solr_get_title_boost() {
	static $title_boost;
	
	if ($title_boost) {
		return $title_boost;
	}
	
	$title_boost = elgg_get_plugin_setting('title_boost', 'elgg_solr');
	if (!is_numeric($title_boost)) {
		$title_boost = 1.5;
	}
	
	return $title_boost;
}

function elgg_solr_get_description_boost() {
	static $description_boost;
	
	if ($description_boost) {
		return $description_boost;
	}
	
	$description_boost = elgg_get_plugin_setting('description_boost', 'elgg_solr');
	if (!is_numeric($description_boost)) {
		$description_boost = 1.5;
	}
	
	return $description_boost;
}


function elgg_solr_get_boost_query() {
	static $boostquery;

	if ($boostquery || $boostquery === false) {
		return $boostquery;
	}
	
	$use_boostquery = elgg_get_plugin_setting('use_time_boost', 'elgg_solr');
	if ($use_boostquery != 'yes') {
		$boostquery = false;
		return $boostquery;
	}
	
	$num = elgg_get_plugin_setting('time_boost_num', 'elgg_solr');
	$interval = elgg_get_plugin_setting('time_boost_interval', 'elgg_solr');
	$time_boost = elgg_get_plugin_setting('time_boost', 'elgg_solr');
	$starttime = strtotime("-{$num} {$interval}");
	$now = time();

	if (!is_numeric($num) || !is_numeric($starttime) || !is_numeric($time_boost)) {
		$boostquery = false;
		return $boostquery;
	}
	
	$boostquery = "time_created:[{$starttime} TO {$now}]^{$time_boost}";
	return $boostquery;
}


function elgg_solr_get_hl_prefix() {
	static $hl_prefix;
	
	if ($hl_prefix) {
		return $hl_prefix;
	}
	
	$hl_prefix = elgg_get_plugin_setting('hl_prefix', 'elgg_solr');
	
	return $hl_prefix;
}


function elgg_solr_get_hl_suffix() {
	static $hl_suffix;
	
	if ($hl_suffix) {
		return $hl_suffix;
	}
	
	$hl_suffix = elgg_get_plugin_setting('hl_suffix', 'elgg_solr');
	
	return $hl_suffix;
}