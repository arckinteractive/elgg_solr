<?php

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

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','container_guid','title','description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

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




function elgg_solr_object_search($hook, $type, $return, $params) {
	// we don't want to show results if a more specific search was run
//	if (empty($params['subtype']) && get_input('entity_subtype', false)) {
//		return false;
//	}
//	
	$entities = array();

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','title','description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

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



function elgg_solr_user_search($hook, $type, $return, $params) {
	$entities = array();

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','username', 'description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

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



function elgg_solr_group_search($hook, $type, $return, $params) {
	$entities = array();

    $select = array(
        'query'  => $params['query'],
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

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



function elgg_solr_user_settings_save($hook, $type, $return, $params) {
	$user_guid = (int) get_input('guid');
	$user = get_user($user_guid);
	
	if ($user) {
		elgg_solr_add_update_user($user);
	}
}