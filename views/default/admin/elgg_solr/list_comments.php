<?php

$type = get_input('type');
$subtype = get_input('subtype');
$starttime = get_input('starttime');
$endtime = get_input('endtime');
$offset = get_input('offset', 0);
$limit = get_input('limit', 10);

$options = array(
	'annotation_name' => 'generic_comment',
	'annotation_created_time_lower' => $starttime,
	'annotation_created_time_upper' => $endtime,
	'offset' => $offset,
	'limit' => $limit,
	'count' => true
);


$count = elgg_get_annotations($options);

if (!$count) {
	echo 'No comments to show';
	return;
}

unset($options['count']);
echo elgg_list_annotations($options);