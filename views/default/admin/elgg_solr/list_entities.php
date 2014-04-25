<?php

$type = get_input('type');
$subtype = get_input('subtype');
$starttime = get_input('starttime');
$endtime = get_input('endtime');
$offset = get_input('offset', 0);
$limit = get_input('limit', 10);

if ($type == 'comments' && !$subtype) {
	// show an alternate view for comments
	echo elgg_view('admin/elgg_solr/list_comments', $vars);
	return;
}

$options = array(
	'type' => $type,
	'created_time_lower' => $starttime,
	'created_time_upper' => $endtime,
	'full_view' => false,
	'offset' => $offset,
	'limit' => $limit,
	'count' => true
);

if ($subtype) {
	$options['subtype'] = $subtype;
}
else {
	$options['subtype'] = ELGG_ENTITIES_NO_VALUE;
}

$count = elgg_get_entities($options);

if (!$count) {
	echo 'No entities to show';
	return;
}

unset($options['count']);
echo elgg_list_entities($options);