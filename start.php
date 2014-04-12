<?php

elgg_register_event_handler('init', 'system', 'elgg_solr_init');

/**
 *  Init elgg_solr plugin
 */
function elgg_solr_init() {

    // Register solr page handler
    elgg_register_page_handler('solr', 'elgg_solr_page_handler');
	
	// unregister default search hooks
	elgg_unregister_plugin_hook_handler('search', 'object', 'search_objects_hook');
	elgg_unregister_plugin_hook_handler('search', 'user', 'search_users_hook');
	elgg_unregister_plugin_hook_handler('search', 'group', 'search_groups_hook');

    elgg_register_plugin_hook_handler('search', 'object:file', 'elgg_solr_file_search');
	elgg_register_plugin_hook_handler('search', 'object', 'elgg_solr_object_search');
	elgg_register_plugin_hook_handler('search', 'user', 'elgg_solr_user_search');
	elgg_register_plugin_hook_handler('search', 'group', 'elgg_solr_group_search');
	
	elgg_register_plugin_hook_handler('cron', 'hourly', 'elgg_solr_cron_index');

    elgg_register_event_handler('create', 'all', 'elgg_solr_add_update_entity', 1000);
    elgg_register_event_handler('update', 'all', 'elgg_solr_add_update_entity', 1000);
    elgg_register_event_handler('delete', 'object', 'elgg_solr_delete_entity', 1000);
	elgg_register_event_handler('create', 'metadata', 'elgg_solr_metadata_update');
	elgg_register_event_handler('update', 'metadata', 'elgg_solr_metadata_update');
	
	// when to update the user index
	elgg_register_plugin_hook_handler('usersettings:save', 'user', 'elgg_solr_user_settings_save', 1000);
	elgg_register_event_handler('profileupdate','user', 'elgg_solr_add_update_entity', 1000);
	
	
	// register functions for indexing
	elgg_solr_register_solr_entity_type('object', 'file', 'elgg_solr_add_update_file');
	elgg_solr_register_solr_entity_type('user', 'default', 'elgg_solr_add_update_user');
	elgg_solr_register_solr_entity_type('object', 'default', 'elgg_solr_add_update_object_default');
	elgg_solr_register_solr_entity_type('group', 'default', 'elgg_solr_add_update_group_default');
}

/**
 * Solr page handler
 *
 * @param array $page  The URI elements
 *
 * @return bool
 */
function elgg_solr_page_handler($page) {

    $base = elgg_get_plugins_path() . 'elgg_solr/pages/elgg_solr';

    switch ($page[0]) {

        case 'reindex':
			admin_gatekeeper();
            elgg_solr_reindex();
            break;

        default:
            require $base . "/solr.php";
            break;
    }

    return true;
}

/**
 * Return default results for searches on objects.
 *
 * @param unknown_type $hook
 * @param unknown_type $type
 * @param unknown_type $value
 * @param unknown_type $params
 * @return unknown_type
 */
function elgg_solr_file_search($hook, $type, $value, $params) {

    $entities = array();

    require_once(__DIR__ . '/lib/Solarium/Autoloader.php');

    Solarium_Autoloader::register();

    $config = array(
        'adapteroptions' => array(
            'host' => 'solr.executivenetworks.com',
            'port' => 8983,
            'path' => '/solr/',
        )
    );

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','container_guid','title','description'),
    );

    // create a client instance
    $client = new Solarium_Client($config);

    // get an update query instance
    $query = $client->createSelect($select);
	
	$access_query = elgg_solr_get_access_query();
	if ($access_query) {
		$query->createFilterQuery('access')->setQuery($access_query);
	}
	
	$query->createFilterQuery('type')->setQuery('type:object');
	$query->createFilterQuery('subtype')->setQuery('subtype:file');

    if (!empty($params['fq'])) {
        foreach ($params['fq'] as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }

    // get highlighting component and apply settings
    $hl = $query->getHighlighting();
    $hl->setFields(array('attr_content', 'description'));
    $hl->setSimplePrefix('<strong class="search-highlight search-highlight-color1">');
    $hl->setSimplePostfix('</strong>');

    // this executes the query and returns the result
    try {
        $resultset = $client->select($query);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Get the highlighted snippet
    try {
        $highlighting = $resultset->getHighlighting();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Count the total number of documents found by solr
    $count = $resultset->getNumFound();

    foreach ($resultset as $document) {

        $snippet = '';

        if ($entity = get_entity($document->id)) {

            // highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
            $highlightedDoc = $highlighting->getResult($document->id);

            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet .= implode(' (...) ', $highlight) . '<br/>';
                }
            }

            $title = search_get_highlighted_relevant_substrings($entity->title, $params['query']);
            $entity->setVolatileData('search_matched_title', $title);

            $entity->setVolatileData('search_matched_description', $snippet);    

            $entities[] = $entity;
        }
    }

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}

//@TODO - this can take a long time - vroom it!
function elgg_solr_reindex() {
	set_time_limit(0);
	
	$debug = get_input('debug', false);
	if ($debug) {
		elgg_set_config('elgg_solr_debug', 1);
	}
	
	$registered_types = get_registered_entity_types();
	
	$ia = elgg_set_ignore_access(true);
	
    // Include the solarium class loader
    require_once(__DIR__ . '/lib/Solarium/Autoloader.php');

    Solarium_Autoloader::register();
	
	$config = array(
			'adapteroptions' => array(
				'host' => 'solr.executivenetworks.com',
				'port' => 8983,
				'path' => '/solr/',
			)
		);
	
	// create a client instance
	$client = new Solarium_Client($config);

	// get an update query instance
	$update = $client->createUpdate();
	
	// Add the delete query
	$update->addDeleteQuery('*:*');

	// Add the commit command to the update query
	$update->addCommit();
    
	// this executes the query and returns the result
	$result = $client->update($update);

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
	exit;
}

function elgg_solr_add_update_entity($event, $type, $entity) {
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

    // Delete the current index
    require_once(__DIR__ . '/lib/Solarium/Autoloader.php');
    Solarium_Autoloader::register();

    $config = array(
        'adapteroptions' => array(
            'host' => 'solr.executivenetworks.com',
            'port' => 8983,
            'path' => '/solr/',
        )
    );

    // create a client instance
    $client = new Solarium_Client($config);

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


function elgg_solr_add_update_file($entity) {
	
	if (elgg_get_config('elgg_solr_debug')) {
		$debug = true;
	}
	
	// combine some additional sources to search
	$desc = $entity->description;
	
	$owner = $entity->getOwnerEntity();

    $title       = urlencode(elgg_solr_xml_format($entity->title));
    $description = urlencode(elgg_solr_xml_format($desc));
   

    // File you want to upload/post
	
	if (file_exists($entity->getFilenameOnFilestore())) {
		// URL on which we have to post data
		$url = "http://solr.executivenetworks.com:8983/solr/update/extract?"
         . "literal.id={$entity->guid}"
         . "&literal.container_guid={$entity->container_guid}"
		 . "&literal.owner_guid={$entity->owner_guid}"
		 . "&literal.title={$title}"
		 . "&literal.type=object"
		 . "&literal.subtype=file"
		 . "&literal.access_id={$entity->access_id}"
		 . "&literal.time_created={$entity->time_created}";

		if ($description) {
			$url .= "&literal.description={$description}";
		}

		$url .= "&uprefix=attr_&fmap.content=attr_content";
		
		if (!elgg_get_config('elgg_solr_nocommit')) {
			$url .= '&commit=true';
		}
		
		$curl = 'curl "' . $url . '" -F "myfile=@' . $entity->getFilenameOnFilestore() . '"';
		
		if ($debug) {
			elgg_solr_debug_log('Curl via exec - ' . $curl);
		}
		
		exec($curl);
		return true;
	}
	
	$title       = elgg_solr_xml_format($entity->title);
    $description = elgg_solr_xml_format($desc);

	// we have no file to send, so push xml doc
	$doc = <<<EOF
        <add>
            <doc>
                <field name="id">{$entity->guid}</field>
				<field name="title">{$title}</field>
				<field name="description">{$description}</field>
                <field name="type">object</field>
				<field name="subtype">file</field>
				<field name="container_guid">{$entity->container_guid}</field>
				<field name="owner_guid">{$entity->owner_guid}</field>
				<field name="access_id">{$entity->access_id}</field>
				<field name="time_created">{$entity->time_created}</field>
            </doc>
        </add>
EOF;
				
	elgg_solr_push_doc($doc);
}


function elgg_solr_add_update_object_default($entity) {
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		return false;
	}
	
	$title       = elgg_solr_xml_format($entity->title);
    $description = elgg_solr_xml_format($entity->description);

    // Build the user document to be posted
    $doc = <<<EOF
        <add>
            <doc>
				<field name="id">{$entity->guid}</field>
				<field name="title">{$title}</field>
				<field name="description">{$description}</field>
                <field name="type">object</field>
				<field name="subtype">{$entity->getSubtype()}</field>
				<field name="access_id">{$entity->access_id}</field>
				<field name="container_guid">{$entity->container_guid}</field>
				<field name="owner_guid">{$entity->owner_guid}</field>
				<field name="time_created">{$entity->time_created}</field>
            </doc>
        </add>
EOF;

	elgg_solr_push_doc($doc);
}


function elgg_solr_user_settings_save($hook, $type, $return, $params) {
	$user_guid = (int) get_input('guid');
	$user = get_user($user_guid);
	
	if ($user) {
		elgg_solr_add_update_user($user);
	}
}


function elgg_solr_add_update_user($entity) {
	
	if (elgg_get_config('elgg_solr_debug')) {
		$debug = true;
	}
	
	if (!elgg_instanceof($entity, 'user')) {
		if ($debug) {
			elgg_solr_debug_log('Error: Not a valid user - ' . print_r($entity,1));
		}
		return false;
	}
	
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		if ($debug) {
			elgg_solr_debug_log('Error: Not a valid entity type - ' . print_r($entity,1));
		}
		return false;
	}
	
    $guid     = $entity->guid;
    $name     = elgg_solr_xml_format($entity->name);
	$username = elgg_solr_xml_format($entity->username);
	
	
	// @TODO - lump public profile fields in with description
	$desc = $entity->description;
	
	$description = elgg_solr_xml_format($desc);

    // Build the user document to be posted
    $doc = <<<EOF
        <add>
            <doc>
                <field name="id">$guid</field>
				<field name="owner_guid">{$entity->owner_guid}</field>
				<field name="container_guid">{$entity->container_guid}</field>
                <field name="name">$name</field>
                <field name="username">$username</field>
				<field name="description">$description</field>
                <field name="type">user</field>
				<field name="access_id">{$entity->access_id}</field>
				<field name="time_created">{$entity->time_created}</field>
            </doc>
        </add>
EOF;

	elgg_solr_push_doc($doc);
}



function elgg_solr_user_search($hook, $type, $return, $params) {
	$entities = array();

    require_once(__DIR__ . '/lib/Solarium/Autoloader.php');

    Solarium_Autoloader::register();

    $config = array(
        'adapteroptions' => array(
            'host' => 'solr.executivenetworks.com',
            'port' => 8983,
            'path' => '/solr/',
        )
    );

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','username', 'description'),
    );

    // create a client instance
    $client = new Solarium_Client($config);

    // get an update query instance
    $query = $client->createSelect($select);
	
	$access_query = elgg_solr_get_access_query();
	if ($access_query) {
		$query->createFilterQuery('access')->setQuery($access_query);
	}
	
	// make sure we're only getting users
	$query->createFilterQuery('type')->setQuery('type:user');

    if (!empty($params['fq'])) {
        foreach ($params['fq'] as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }

    // get highlighting component and apply settings
    $hl = $query->getHighlighting();
    $hl->setFields(array('name', 'description'));
    $hl->setSimplePrefix('<strong class="search-highlight search-highlight-color1">');
    $hl->setSimplePostfix('</strong>');

    // this executes the query and returns the result
    try {
        $resultset = $client->select($query);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Get the highlighted snippet
    try {
        $highlighting = $resultset->getHighlighting();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Count the total number of documents found by solr
    $count = $resultset->getNumFound();

    foreach ($resultset as $document) {

        $snippet = '';

        if ($entity = get_entity($document->id)) {

            // highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
            $highlightedDoc = $highlighting->getResult($document->id);

            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet .= implode(' (...) ', $highlight) . '<br/>';
                }
            }

            $name = search_get_highlighted_relevant_substrings($entity->name, $params['query']);
            $entity->setVolatileData('search_matched_name', $name);

            $entity->setVolatileData('search_matched_description', $snippet);    

            $entities[] = $entity;
        }
    }

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}


function elgg_solr_debug_log($message) {
	error_log($message);
}


function elgg_solr_push_doc($doc) {
	if (elgg_get_config('elgg_solr_debug')) {
		$debug = true;
	}
	
	// Solr URL
    $url = "http://solr.executivenetworks.com:8983/solr/update";
	
	if (!elgg_get_config('elgg_solr_nocommit')) {
		$url .= '?commit=true';
	}

    // Initialize cURL
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml")); 
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "$doc");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    // Execute the request
    try {
		if ($debug) {
			elgg_solr_debug_log('attempting solr update with url: ' . $url);
			elgg_solr_debug_log('doc = ' . print_r($doc,1));
		}
        $response = curl_exec($ch);
		
		if ($debug) {
			elgg_solr_debug_log('curl response:  ' . print_r($response,1));
		}
    } catch( Exception $e) {
		if ($debug) {
			elgg_solr_debug_log('elgg_solr_add_update_object() - ' . $e->getMessage());
		}
    }

    curl_close($ch);
}


function elgg_solr_xml_format($text) {
	return htmlspecialchars(elgg_strip_tags($text), ENT_QUOTES, 'UTF-8');
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
		$friends = elgg_get_entities(array(
			'type' => 'user',
			'relationship' => 'friend',
			'relationship_guid' => elgg_get_logged_in_user_guid(),
			'limit' => false,
			'callback' => false // keep the query fast
		));
		
		$friend_guids = array();
		foreach ($friends as $friend) {
			$friend_guids[] = $friend->guid;
		}
			
		$friends_list = '';
		if ($friend_guids) {
			$friends_list = implode(' OR ', $friend_guids);
		}
	}

	//$query->createFilterQuery('access')->setQuery("access_id:({$access_list}) OR (access_id:" . ACCESS_FRIENDS . " AND owner_guid:({$friends}))");
	$return = '';
	
	if ($access_list) {
		$return .= "access_id:({$access_list})";
	}
	
	$fr_prefix = '';
	$fr_suffix = '';
	if ($return && $friends_list) {
		$return .= ' OR ';
		$fr_prefix = '(';
		$fr_suffix = ')';
	}
	
	if ($friends_list) {
		$return .= $fr_prefix . $friends_list . $fr_suffix;
	}
	
	return $return;
}



function elgg_solr_add_update_group_default($entity) {
	if (!is_registered_entity_type($entity->type, $entity->getSubtype())) {
		return false;
	}
	
	$name       = elgg_solr_xml_format($entity->name);
    $description = elgg_solr_xml_format($entity->description);

    // Build the user document to be posted
    $doc = <<<EOF
        <add>
            <doc>
				<field name="id">{$entity->guid}</field>
				<field name="title">{$name}</field>
				<field name="name">{$name}</field>
				<field name="description">{$description}</field>
                <field name="type">group</field>
				<field name="subtype">{$entity->getSubtype()}</field>
				<field name="access_id">{$entity->access_id}</field>
				<field name="container_guid">{$entity->container_guid}</field>
				<field name="owner_guid">{$entity->owner_guid}</field>
				<field name="time_created">{$entity->time_created}</field>
            </doc>
        </add>
EOF;

	elgg_solr_push_doc($doc);
}


function elgg_solr_group_search($hook, $type, $return, $params) {
	$entities = array();

    require_once(__DIR__ . '/lib/Solarium/Autoloader.php');

    Solarium_Autoloader::register();

    $config = array(
        'adapteroptions' => array(
            'host' => 'solr.executivenetworks.com',
            'port' => 8983,
            'path' => '/solr/',
        )
    );

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','description'),
    );

    // create a client instance
    $client = new Solarium_Client($config);

    // get an update query instance
    $query = $client->createSelect($select);
	
	$access_query = elgg_solr_get_access_query();
	if ($access_query) {
		$query->createFilterQuery('access')->setQuery($access_query);
	}
	
	// make sure we're only getting groups
	$query->createFilterQuery('type')->setQuery('type:group');

    if (!empty($params['fq'])) {
        foreach ($params['fq'] as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }

    // get highlighting component and apply settings
    $hl = $query->getHighlighting();
    $hl->setFields(array('title', 'description'));
    $hl->setSimplePrefix('<strong class="search-highlight search-highlight-color1">');
    $hl->setSimplePostfix('</strong>');

    // this executes the query and returns the result
    try {
        $resultset = $client->select($query);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Get the highlighted snippet
    try {
        $highlighting = $resultset->getHighlighting();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Count the total number of documents found by solr
    $count = $resultset->getNumFound();

    foreach ($resultset as $document) {

        $snippet = '';

        if ($entity = get_entity($document->id)) {

            // highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
            $highlightedDoc = $highlighting->getResult($document->id);

            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet .= implode(' (...) ', $highlight) . '<br/>';
                }
            }

            $name = search_get_highlighted_relevant_substrings($entity->name, $params['query']);
            $entity->setVolatileData('search_matched_name', $name);

            $entity->setVolatileData('search_matched_description', $snippet);    

            $entities[] = $entity;
        }
    }

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}



function elgg_solr_object_search($hook, $type, $return, $params) {
	// we don't want to show results if a more specific search was run
//	if (empty($params['subtype']) && get_input('entity_subtype', false)) {
//		return false;
//	}
//	
	$entities = array();

    require_once(__DIR__ . '/lib/Solarium/Autoloader.php');

    Solarium_Autoloader::register();

    $config = array(
        'adapteroptions' => array(
            'host' => 'solr.executivenetworks.com',
            'port' => 8983,
            'path' => '/solr/',
        )
    );

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','title','description'),
    );

    // create a client instance
    $client = new Solarium_Client($config);

    // get an update query instance
    $query = $client->createSelect($select);
	
	$access_query = elgg_solr_get_access_query();
	if ($access_query) {
		$query->createFilterQuery('access')->setQuery($access_query);
	}
	
	// make sure we're only getting groups
	$query->createFilterQuery('type')->setQuery('type:object');

	if ($params['subtype']) {
		$query->createFilterQuery('subtype')->setQuery('subtype:' . $params['subtype']);
	}

    if (!empty($params['fq'])) {
        foreach ($params['fq'] as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }

    // get highlighting component and apply settings
    $hl = $query->getHighlighting();
    $hl->setFields(array('title'));
    $hl->setSimplePrefix('<strong class="search-highlight search-highlight-color1">');
    $hl->setSimplePostfix('</strong>');

    // this executes the query and returns the result
    try {
        $resultset = $client->select($query);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Get the highlighted snippet
    try {
        $highlighting = $resultset->getHighlighting();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }

    // Count the total number of documents found by solr
    $count = $resultset->getNumFound();

    foreach ($resultset as $document) {

        $snippet = '';

        if ($entity = get_entity($document->id)) {

            // highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
            $highlightedDoc = $highlighting->getResult($document->id);

            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet .= implode(' (...) ', $highlight) . '<br/>';
                }
            }

            $name = search_get_highlighted_relevant_substrings($entity->name, $params['query']);
            $entity->setVolatileData('search_matched_title', $name);

            $entity->setVolatileData('search_matched_description', $snippet);    

            $entities[] = $entity;
        }
    }

    return array(
        'entities' => $entities,
        'count' => $count,
    );
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



function elgg_solr_cron_index($hook, $type, $return, $params) {
	// get any objects that need to be reindexed due to new metadata
	$options = array(
		'metadata_name_value_pairs' => array(
			'name' => 'elgg_solr_reindex',
			'value' => 1
		),
		'limit' => false
	);
	
	$entities = new ElggBatch('elgg_get_entities', $options, '', 25, true);
	
	foreach ($entities as $e) {
		elgg_solr_add_update_entity('', '', $e);
		$e->elgg_solr_reindex = 0;
	}
}
