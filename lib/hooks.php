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
	$params['query'] = elgg_solr_escape_special_chars($params['query']);
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
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'asc'
	));
	
	$params['fq']['type'] = 'type:object';
	$params['fq']['subtype'] = 'subtype:file';

    $default_fq = elgg_solr_get_default_fq($params);
	if ($params['fq']) {
		$filter_queries = array_merge($default_fq, $params['fq']);
	}
	else {
		$filter_queries = $default_fq;
	}

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
	$params['query'] = elgg_solr_escape_special_chars($params['query']);
	$entities = array();

    $select = array(
        'query'  => "title:{$params['query']}^2 OR description:{$params['query']}^1",
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','title','description')
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'asc'
	));
	
	// make sure we're only getting objectss
	$params['fq']['type'] = 'type:object';

	$default_fq = elgg_solr_get_default_fq($params);
	if ($params['fq']) {
		$filter_queries = array_merge($default_fq, $params['fq']);
	}
	else {
		$filter_queries = $default_fq;
	}

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
	$params['query'] = elgg_solr_escape_special_chars($params['query']);
	$entities = array();

    $select = array(
        'query'  => "name:{$params['query']}^3 OR username:{$params['query']}^2 OR description:{$params['query']}^1",
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','username', 'description')
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'desc'
	));
	
	// make sure we're only getting users
	$params['fq']['type'] = 'type:user';

	$default_fq = elgg_solr_get_default_fq($params);
	if ($params['fq']) {
		$filter_queries = array_merge($default_fq, $params['fq']);
	}
	else {
		$filter_queries = $default_fq;
	}

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
	$params['query'] = elgg_solr_escape_special_chars($params['query']);
	$entities = array();

    $select = array(
        'query'  => "name:{$params['query']}^2 OR description:{$params['query']}^1",
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','name','description')
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'asc'
	));
	
	// make sure we're only getting groups
	$params['fq']['type'] = 'type:group';

	$default_fq = elgg_solr_get_default_fq($params);
	if ($params['fq']) {
		$filter_queries = array_merge($default_fq, $params['fq']);
	}
	else {
		$filter_queries = $default_fq;
	}

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


function elgg_solr_user_settings_save($hook, $type, $return, $params) {
	$user_guid = (int) get_input('guid');
	$user = get_user($user_guid);
	
	if ($user) {
		elgg_solr_add_update_user($user);
	}
}



function elgg_solr_tag_search($hook, $type, $return, $params) {
	
	$valid_tag_names = elgg_get_registered_tag_metadata_names();
	
	if (!$valid_tag_names || !is_array($valid_tag_names)) {
		return array('entities' => array(), 'count' => 0);
	}

	// if passed a tag metadata name, only search on that tag name.
	// tag_name isn't included in the params because it's specific to
	// tag searches.
	if ($tag_names = get_input('tag_names')) {
		if (is_array($tag_names)) {
			$search_tag_names = $tag_names;
		} else {
			$search_tag_names = array($tag_names);
		}

		// check these are valid to avoid arbitrary metadata searches.
		foreach ($search_tag_names as $i => $tag_name) {
			if (!in_array($tag_name, $valid_tag_names)) {
				unset($search_tag_names[$i]);
			}
		}
	} else {
		$search_tag_names = $valid_tag_names;
	}
	
	$query = array();
	foreach ($search_tag_names as $tagname) {
		// @note - these need to be treated as literal exact matches, so encapsulate in double-quotes
		$query[] = 'tags:"' . elgg_solr_escape_special_chars($tagname . '%%' . $params['query']) . '"';
	}
	
	if (!$query) {
		return array('entities' => array(), 'count' => 0);
	}
	
	$q = implode(' OR ', $query);

	$select = array(
        'query'  => $q,
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','title','description')
    );

	$client = elgg_solr_get_client();
// get an update query instance
    $query = $client->createSelect($select);
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'asc'
	));
	
	$default_fq = elgg_solr_get_default_fq($params);
	if ($params['fq']) {
		$filter_queries = array_merge($default_fq, $params['fq']);
	}
	else {
		$filter_queries = $default_fq;
	}

    if (!empty($filter_queries)) {
        foreach ($filter_queries as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }

    // get highlighting component and apply settings
    $hl = $query->getHighlighting();
    $hl->setFields(array('tags'));
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
					// a little hackery for matched tags
					$snippet = array();
                    foreach ($highlight as $key => $h) {
						$matched = '<strong class="search-highlight search-highlight-color1">';
						$matched .= substr(strstr(elgg_strip_tags($h), '%%'), 2);
						$matched .= '</strong>';
						$snippet[] = $matched;
					}

					$display = implode(', ', $snippet);
					$entity->setVolatileData('search_matched_extra', $display);
                }
            }
			
			$title = $entity->title ? $entity->title : $entity->name;
			$description = $entity->description;
			$entity->setVolatileData('search_matched_title', $title);
			$entity->setVolatileData('search_matched_description', elgg_get_excerpt($description));
            
            $entities[] = $entity;
        }
    }

	return array(
		'entities' => $entities,
		'count' => $count,
	);
}



function elgg_solr_comment_search($hook, $type, $return, $params) {
	$params['query'] = elgg_solr_escape_special_chars($params['query']);
	
	$entities = array();

    $select = array(
        'query'  => "description:{$params['query']}",
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','container_guid','description', 'owner_guid', 'time_created'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'asc'
	));
	
	// make sure we're only getting comments
	$params['fq']['type'] = 'type:annotation';
	$params['fq']['subtype'] = 'subtype:generic_comment';
	
	$default_fq = elgg_solr_get_default_fq($params);
	if ($params['fq']) {
		$filter_queries = array_merge($default_fq, $params['fq']);
	}
	else {
		$filter_queries = $default_fq;
	}

    if (!empty($filter_queries)) {
        foreach ($filter_queries as $key => $value) {
            $query->createFilterQuery($key)->setQuery($value);
        }
    }

    // get highlighting component and apply settings
    $hl = $query->getHighlighting();
    $hl->setFields(array('description'));
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

        $entity = get_entity($document->container_guid);
		
		if (!$entity) {
			$entity = new ElggObject();
			$entity->setVolatileData('search_unavailable_entity', TRUE);
		}

        // highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
        $highlightedDoc = $highlighting->getResult($document->id);

		$comment_str = '';
        if($highlightedDoc){
            foreach($highlightedDoc as $highlight) {
                $comment_str = implode(' (...) ', $highlight);
            }
        }
		
		if (!$comment_str) {
			$comment_str = elgg_get_excerpt($document->description);
		}
		
		$comments_data = $entity->getVolatileData('search_comments_data');
		if (!$comments_data) {
			$comments_data = array();
		}
		$comments_data[] = array(
			'annotation_id' => substr(strstr(elgg_strip_tags($document->id), ':'), 1),
			'text' => $comment_str,
			'owner_guid' => $document->owner_guid,
			'time_created' => $document->time_created,
		);
		$entity->setVolatileData('search_comments_data', $comments_data);

        $entities[] = $entity;
    }

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}

// optimize our index daily
function elgg_solr_daily_cron($hook, $type, $return, $params) {
	$client = elgg_solr_get_client();
	$query = $client->createUpdate();
	$query->addOptimize(true, true, 5);
	$client->update($query);
}