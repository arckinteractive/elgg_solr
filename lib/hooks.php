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
        'fields' => array('id','title','description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	
	$params['fq']['type'] = 'type:object';
	$params['fq']['subtype'] = 'subtype:file';

    $default_fq = elgg_solr_get_default_fq($params);
	$filter_queries = array_merge($default_fq, $params['fq']);

    if (!empty($filter_queries)) {
        foreach ($filter_queries as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }

    // get highlighting component and apply settings
    $hl = $query->getHighlighting();
    $hl->setFields(array('title', 'attr_content', 'description'));
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

			$matched = array();
            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet = implode(' (...) ', $highlight);
					$entity->setVolatileData('search_matched_' . $field, $snippet);
					$matched[$field] = $snippet;
                }
            }

            if (empty($matched['title'])) {
				$entity->setVolatileData('search_matched_title', $entity->title);
			}
            
			if (empty($matched['description']) && empty($matched['attr_content'])) {
				$entity->setVolatileData('search_matched_description', elgg_get_excerpt($entity->description, 100));
			}
			else {
				$entity->setVolatileData('search_matched_description', $matched['description'] . $matched['attr_content']);
			}

            $entities[] = $entity;
        }
    }

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}




function elgg_solr_object_search($hook, $type, $return, $params) {

	$entities = array();

    $select = array(
        'query'  => "title:{$params['query']}^2 OR description:{$params['query']}^1",
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','title','description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	
	// make sure we're only getting objectss
	$params['fq']['type'] = 'type:object';

	$default_fq = elgg_solr_get_default_fq($params);
	$filter_queries = array_merge($default_fq, $params['fq']);

    if (!empty($filter_queries)) {
        foreach ($filter_queries as $key => $value) {
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

			$matched = array();
            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet .= implode(' (...) ', $highlight);
					$entity->setVolatileData('search_matched_' . $field, $snippet);
					$matched[$field] = $snippet;
                }
            }
			
			if (empty($matched['title'])) {
				$entity->setVolatileData('search_matched_title', $entity->title);
			}
            
			if (empty($matched['description'])) {
				$entity->setVolatileData('search_matched_description', elgg_get_excerpt($entity->description, 100));
			}

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
        'query'  => "name:{$params['query']}^3 OR username:{$params['query']}^2 OR description:{$params['query']}^1",
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','username', 'description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	
	// make sure we're only getting users
	$params['fq']['type'] = 'type:user';

	$default_fq = elgg_solr_get_default_fq($params);
	$filter_queries = array_merge($default_fq, $params['fq']);

    if (!empty($filter_queries)) {
        foreach ($filter_queries as $key => $value) {
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

			$matched = array();
            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet = implode(' (...) ', $highlight);
					$entity->setVolatileData('search_matched_' . $field, $snippet);
					$matched[$field] = $snippet;
                }
            }

			if (empty($matched['name'])) {
				$entity->setVolatileData('search_matched_name', $entity->name);
				$entity->setVolatileData('search_matched_title', $entity->name);
			}
			else {
				$entity->setVolatileData('search_matched_title', $matched['name']);
			}
            
			$desc_hl = search_get_highlighted_relevant_substrings($entity->description, $params['query']);
			$entity->setVolatileData('search_matched_description', $desc_hl);

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
        'query'  => "name:{$params['query']}^2 OR description:{$params['query']}^1",
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','description'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	
	// make sure we're only getting groups
	$params['fq']['type'] = 'type:group';

	$default_fq = elgg_solr_get_default_fq($params);
	$filter_queries = array_merge($default_fq, $params['fq']);

    if (!empty($filter_queries)) {
        foreach ($filter_queries as $key => $value) {
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

			$matched = array();
            if($highlightedDoc){
                foreach($highlightedDoc as $field => $highlight) {
                    $snippet = implode(' (...) ', $highlight);
					$entity->setVolatileData('search_matched_' . $field, $snippet);
					$matched[$field] = $snippet;
                }
            }

 			if (empty($matched['name'])) {
				$entity->setVolatileData('search_matched_name', $entity->name);
				$entity->setVolatileData('search_matched_title', $entity->name);
			}
			else {
				$entity->setVolatileData('search_matched_title', $matched['name']);
			}
            
			if (empty($matched['description'])) {
				$entity->setVolatileData('search_matched_description', elgg_get_excerpt($entity->description, 100));
			}

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
	
	$entities = new ElggBatch('elgg_get_entities', $options, '', 400, true);
	
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
