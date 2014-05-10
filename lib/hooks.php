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

    $select = array(
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
		'time_created' => 'desc'
	));
	
	$title_boost = elgg_solr_get_title_boost();
	$description_boost = elgg_solr_get_description_boost();
	
	// get the dismax component and set a boost query
	$dismax = $query->getEDisMax();
	$dismax->setQueryFields("title^{$title_boost} description^{$description_boost} attr_content^{$description_boost}");
	
	$boostQuery = elgg_solr_get_boost_query();
	if ($boostQuery) {
		$dismax->setBoostQuery($boostQuery);
	}
	
	// this query is now a dismax query
	$query->setQuery($params['query']);
	
	
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
    $hl->setSimplePrefix('<span data-hl="elgg-solr">');
	$hl->setSimplePostfix('</span>');

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
	$hl_prefix = elgg_solr_get_hl_prefix();
	$hl_suffix = elgg_solr_get_hl_suffix();

	$search_results = array();
    foreach ($resultset as $document) {
		$search_results[$document->id] = array();
		$snippet = '';
            
		// highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
        $highlightedDoc = $highlighting->getResult($document->id);

        if($highlightedDoc){
            foreach($highlightedDoc as $field => $highlight) {
                $snippet = implode(' (...) ', $highlight);
				// get our highlight based on the wrapped tokens
				// note, this is to prevent partial html from breaking page layouts
				preg_match('/<span data-hl="elgg-solr">(.*)<\/span>/', $snippet, $match);

				$snippet = filter_tags($snippet); // need to filter tags to fix potential html snippets
				if ($match[1]) {
					$snippet = str_replace($match[1], $hl_prefix . $match[1] . $hl_suffix, $snippet);
				}
				$search_results[$document->id][$field] = $snippet;
            }
        }

		// normalize description with attr_content
		$search_results[$document->id]['description'] = trim($search_results[$document->id]['description'] . ' ' . $search_results[$document->id]['attr_content']);
    }
	
	// get the entities in a single query
	// resort them into the order returned by solr by looping through $search_results
	$entities = array();
	$entities_unsorted = array();
	if ($search_results) {
		$entities_unsorted = elgg_get_entities(array(
			'guids' => array_keys($search_results),
			'limit' => false
		));
	}
	
	foreach ($search_results as $guid => $matches) {
		foreach ($entities_unsorted as $e) {
			if ($e->guid == $guid) {
				if ($matches['title']) {
					$e->setVolatileData('search_matched_title', $matches['title']);
				}
				else {
					$e->setVolatileData('search_matched_title', $e->title);
				}
				
				if ($matches['description']) {
					$e->setVolatileData('search_matched_description', $matches['description']);
				}
				else {
					$e->setVolatileData('search_matched_description', elgg_get_excerpt($e->description, 100));
				}
				$entities[] = $e;
			}
		}
	}

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}




function elgg_solr_object_search($hook, $type, $return, $params) {

    $select = array(
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id','title','description')
    );
    // create a client instance
    $client = elgg_solr_get_client($select);

    // get an update query instance
    $query = $client->createSelect($select);
	
	$title_boost = elgg_solr_get_title_boost();
	$description_boost = elgg_solr_get_description_boost();
	
	// get the dismax component and set a boost query
	$dismax = $query->getEDisMax();
	$dismax->setQueryFields("title^{$title_boost} description^{$description_boost}");
	
	$boostQuery = elgg_solr_get_boost_query();
	if ($boostQuery) {
		$dismax->setBoostQuery($boostQuery);
	}
	
	// this query is now a dismax query
	$query->setQuery($params['query']);
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'desc'
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
	$hl->setSimplePrefix('<span data-hl="elgg-solr">');
	$hl->setSimplePostfix('</span>');
	
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
	$hl_prefix = elgg_solr_get_hl_prefix();
	$hl_suffix = elgg_solr_get_hl_suffix();

    $search_results = array();
    foreach ($resultset as $document) {
		$search_results[$document->id] = array();
		$snippet = '';
            
		// highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
        $highlightedDoc = $highlighting->getResult($document->id);

        if($highlightedDoc){
            foreach($highlightedDoc as $field => $highlight) {
                $snippet = implode(' (...) ', $highlight);
				// get our highlight based on the wrapped tokens
				// note, this is to prevent partial html from breaking page layouts
				preg_match('/<span data-hl="elgg-solr">(.*)<\/span>/', $snippet, $match);

				if ($match[1]) {
					$snippet = str_replace($match[1], $hl_prefix . $match[1] . $hl_suffix, $snippet);
				}
				
				$search_results[$document->id][$field] = $snippet;
            }
        }
    }
	
	// get the entities
	$entities = array();
	$entities_unsorted = array();
	if ($search_results) {
		$entities_unsorted = elgg_get_entities(array(
			'guids' => array_keys($search_results),
			'limit' => false
		));
	}
	
	foreach ($search_results as $guid => $matches) {
		foreach ($entities_unsorted as $e) {
			if ($e->guid == $guid) {
				if ($matches['title']) {
					$e->setVolatileData('search_matched_title', $matches['title']);
				}
				else {
					$e->setVolatileData('search_matched_title', $e->title);
				}
				
				if ($matches['description']) {
					$e->setVolatileData('search_matched_description', $matches['description']);
				}
				else {
					$e->setVolatileData('search_matched_description', elgg_get_excerpt($e->description, 100));
				}
				$entities[] = $e;
			}
		}
	}

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}



function elgg_solr_user_search($hook, $type, $return, $params) {

    $select = array(
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
	
	$title_boost = elgg_solr_get_title_boost();
	$description_boost = elgg_solr_get_description_boost();
	
	// get the dismax component and set a boost query
	$dismax = $query->getEDisMax();
	$dismax->setQueryFields("name^{$title_boost} username^{$title_boost} description^{$description_boost}");
	
	// no time boost for users
	/*
	$boostQuery = elgg_solr_get_boost_query();
	if ($boostQuery) {
		$dismax->setBoostQuery($boostQuery);
	}
	 * 
	 */
	
	// this query is now a dismax query
	$query->setQuery($params['query']);
	
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
    $hl->setFields(array('name', 'username', 'description'));
	$hl->setSimplePrefix('<span data-hl="elgg-solr">');
	$hl->setSimplePostfix('</span>');

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
	$hl_prefix = elgg_solr_get_hl_prefix();
	$hl_suffix = elgg_solr_get_hl_suffix();
	
	$search_results = array();
    foreach ($resultset as $document) {
		$search_results[$document->id] = array();
		$snippet = '';
            
		// highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
        $highlightedDoc = $highlighting->getResult($document->id);

        if($highlightedDoc){
            foreach($highlightedDoc as $field => $highlight) {
                $snippet = implode(' (...) ', $highlight);
				// get our highlight based on the wrapped tokens
				// note, this is to prevent partial html from breaking page layouts
				preg_match('/<span data-hl="elgg-solr">(.*)<\/span>/', $snippet, $match);

				if ($match[1]) {
					$snippet = str_replace($match[1], $hl_prefix . $match[1] . $hl_suffix, $snippet);
				}
				$search_results[$document->id][$field] = $snippet;
            }
        }
    }

	// get the entities
	$entities = array();
	$entities_unsorted = array();
	if ($search_results) {
		$entities_unsorted = elgg_get_entities(array(
			'guids' => array_keys($search_results),
			'limit' => false
		));
	}
	
	foreach ($search_results as $guid => $matches) {
		foreach ($entities_unsorted as $e) {
			if ($e->guid == $guid) {
				if ($matches['name']) {
					$name = $matches['name'];
					if ($matches['username']) {
						$name .= ' (@' . $matches['username'] . ')';
					}
					else {
						$name .= ' (@' . $e->username . ')';
					}
					$e->setVolatileData('search_matched_name', $name);
					$e->setVolatileData('search_matched_title', $name);
				}
				else {
					$name = $e->name;
					if ($matches['username']) {
						$name .= ' (@' . $matches['username'] . ')';
					}
					else {
						$name .= ' (@' . $e->username . ')';
					}
					$e->setVolatileData('search_matched_name', $name);
					$e->setVolatileData('search_matched_title', $name);
				}
				
				$desc_hl = search_get_highlighted_relevant_substrings($e->description, $params['query']);
				$e->setVolatileData('search_matched_description', $desc_hl);
				$entities[] = $e;
			}
		}
	}

    return array(
        'entities' => $entities,
        'count' => $count,
    );
}



function elgg_solr_group_search($hook, $type, $return, $params) {

    $select = array(
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
		'time_created' => 'desc'
	));
	
	$title_boost = elgg_solr_get_title_boost();
	$description_boost = elgg_solr_get_description_boost();
	
	// get the dismax component and set a boost query
	$dismax = $query->getEDisMax();
	$dismax->setQueryFields("name^{$title_boost} description^{$description_boost}");
	
	$boostQuery = elgg_solr_get_boost_query();
	if ($boostQuery) {
		$dismax->setBoostQuery($boostQuery);
	}
	
	// this query is now a dismax query
	$query->setQuery($params['query']);
	
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
	$hl->setSimplePrefix('<span data-hl="elgg-solr">');
	$hl->setSimplePostfix('</span>');

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
	$hl_prefix = elgg_solr_get_hl_prefix();
	$hl_suffix = elgg_solr_get_hl_suffix();	

	$search_results = array();
    foreach ($resultset as $document) {
		$search_results[$document->id] = array();
		$snippet = '';
            
		// highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
        $highlightedDoc = $highlighting->getResult($document->id);

        if($highlightedDoc){
            foreach($highlightedDoc as $field => $highlight) {
                $snippet = implode(' (...) ', $highlight);
				// get our highlight based on the wrapped tokens
				// note, this is to prevent partial html from breaking page layouts
				preg_match('/<span data-hl="elgg-solr">(.*)<\/span>/', $snippet, $match);

				if ($match[1]) {
					$snippet = str_replace($match[1], $hl_prefix . $match[1] . $hl_suffix, $snippet);
				}
				$search_results[$document->id][$field] = $snippet;
            }
        }
    }

	// get the entities
	$entities = array();
	$entities_unsorted = array();
	if ($search_results) {
		$entities_unsorted = elgg_get_entities(array(
			'guids' => array_keys($search_results),
			'limit' => false
		));
	}
	
	foreach ($search_results as $guid => $matches) {
		foreach ($entities_unsorted as $e) {
			if ($e->guid == $guid) {
				if ($matches['name']) {
					$name = $matches['name'];
					$e->setVolatileData('search_matched_name', $name);
					$e->setVolatileData('search_matched_title', $name);
				}
				else {
					$name = $e->name;
					$e->setVolatileData('search_matched_name', $name);
					$e->setVolatileData('search_matched_title', $name);
				}
				
				if ($matches['description']) {
					$e->setVolatileData('search_matched_description', $matches['description']);
				}
				else {
					$desc_hl = search_get_highlighted_relevant_substrings($e->description, $params['query']);
					$e->setVolatileData('search_matched_description', $desc_hl);	
				}
				
				$entities[] = $e;
			}
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
	
	$query_parts = array();
	foreach ($search_tag_names as $tagname) {
		// @note - these need to be treated as literal exact matches, so encapsulate in double-quotes
		$query_parts[] = 'tags:"' . elgg_solr_escape_special_chars($tagname . '%%' . $params['query']) . '"';
	}
	
	if (!$query_parts) {
		return array('entities' => array(), 'count' => 0);
	}
	
	$q = implode(' OR ', $query_parts);

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
		'time_created' => 'desc'
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
    $hl->setSimplePrefix('<span data-hl="elgg-solr">');
	$hl->setSimplePostfix('</span>');

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
	$hl_prefix = elgg_solr_get_hl_prefix();
	$hl_suffix = elgg_solr_get_hl_suffix();

	$search_results = array();
    foreach ($resultset as $document) {
		$search_results[$document->id] = array();
        $snippet = '';

        // highlighting results can be fetched by document id (the field defined as uniquekey in this schema)
        $highlightedDoc = $highlighting->getResult($document->id);

        if($highlightedDoc){
            foreach($highlightedDoc as $field => $highlight) {
				// a little hackery for matched tags
				$snippet = array();
                foreach ($highlight as $key => $h) {
					$matched = $hl_prefix;
					$matched .= substr(strstr(elgg_strip_tags($h), '%%'), 2);
					$matched .= $hl_suffix;
					$snippet[] = $matched;
				}

				$display = implode(', ', $snippet);
				$search_results[$document->id][$field] = $display;
            }
        }
    }
	
	// get the entities
	$entities = array();
	$entities_unsorted = array();
	if ($search_results) {
		$entities_unsorted = elgg_get_entities(array(
			'guids' => array_keys($search_results),
			'limit' => false
		));
	}
	
	foreach ($search_results as $guid => $matches) {
		foreach ($entities_unsorted as $e) {
			if ($e->guid == $guid) {
				$title = $e->title ? $e->title : $e->name;
				$description = $e->description;
				$e->setVolatileData('search_matched_title', $title);
				$e->setVolatileData('search_matched_description', elgg_get_excerpt($description));
				
				$e->setVolatileData('search_matched_extra', $matches['tags']);
				$entities[] = $e;
			}
		}
	}

	return array(
		'entities' => $entities,
		'count' => $count,
	);
}



function elgg_solr_comment_search($hook, $type, $return, $params) {
	
	$entities = array();

    $select = array(
        'start'  => $params['offset'],
        'rows'   => $params['limit'],
        'fields' => array('id', 'container_guid', 'description', 'owner_guid', 'time_created'),
    );

    // create a client instance
    $client = elgg_solr_get_client();

    // get an update query instance
    $query = $client->createSelect($select);
	$query->addSorts(array(
		'score' => 'desc',
		'time_created' => 'desc'
	));
	
	$description_boost = elgg_solr_get_description_boost();
	
	// get the dismax component and set a boost query
	$dismax = $query->getEDisMax();
	$dismax->setQueryFields("description^{$description_boost}");
	
	$boostQuery = elgg_solr_get_boost_query();
	if ($boostQuery) {
		$dismax->setBoostQuery($boostQuery);
	}
	
	// this query is now a dismax query
	$query->setQuery($params['query']);
	
	
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
   	$hl->setSimplePrefix('<span data-hl="elgg-solr">');
	$hl->setSimplePostfix('</span>');

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
	$hl_prefix = elgg_solr_get_hl_prefix();
	$hl_suffix = elgg_solr_get_hl_suffix();
	
    foreach ($resultset as $document) {
		// comments entity_guid stored as container_guid in solr
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
                $snippet = implode(' (...) ', $highlight);
				// get our highlight based on the wrapped tokens
				// note, this is to prevent partial html from breaking page layouts
				preg_match('/<span data-hl="elgg-solr">(.*)<\/span>/', $snippet, $match);

				if ($match[1]) {
					$snippet = str_replace($match[1], $hl_prefix . $match[1] . $hl_suffix, $snippet);
				}
            }
        }
		
		if (!$snippet) {
			$snippet = search_get_highlighted_relevant_substrings(elgg_get_excerpt($document->description), $params['query']);
		}
		
		$comments_data = $entity->getVolatileData('search_comments_data');
		if (!$comments_data) {
			$comments_data = array();
		}
		$comments_data[] = array(
			'annotation_id' => substr(strstr(elgg_strip_tags($document->id), ':'), 1),
			'text' => $snippet,
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