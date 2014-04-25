<?php

$type = get_input('type');
$subtype = get_input('subtype');
$starttime = get_input('starttime');
$endtime = get_input('endtime');
$offset = get_input('offset', 0);
$limit = get_input('limit', 10);

$options = array(
	'type' => $type,
	'created_time_lower' => $starttime,
	'created_time_upper' => $endtime,
	'offset' => $offset,
	'limit' => $limit
);

if ($subtype) {
	$options['subtype'] = $subtype;
}

$entities = elgg_get_entities($options);

if (!$entities) {
	echo 'No entities to show';
	return;
}

foreach ($entities as $e) {
	$title = $e->title ? $e->title : $e->name;
	echo elgg_view('output/url', array(
		'text' => 'Entity ' . $e->guid . ': ' . $title,
		'href' => $e->getURL()
	));
	
	echo '<br><br>';
}

$options['count'] = true;
echo elgg_view('navigation/pagination', array(
	'offset' => $offset,
	'limit' => $limit,
	'count' => elgg_get_entities($options)
));