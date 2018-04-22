<?php
$starttime = get_input('starttime', false);
$endtime = get_input('endtime', false);
$type = get_input('type', false);
$subtype = get_input('subtype', false);

if ($type) {
	$q = "type_s:{$type}";
	
	if ($subtype) {
		$q .= " AND subtype_s:{$subtype}";
	}
}
else {
	$q = '*:*';
}

if ($starttime && $endtime) {
	$q .= " AND time_created_i:[{$starttime} TO {$endtime}]";
}

// create a client instance
$client = elgg_solr_get_client();

// get an update query instance
$update = $client->createUpdate();

// add the delete query and a commit command to the update query
$update->addDeleteQuery($q);

$update->addCommit();

// this executes the query and returns the result
try {
	$client->update($update);
	system_message(elgg_echo('elgg_solr:success:delete_index'));
} catch (Exception $exc) {
	register_error($exc->getTraceAsString());
}

forward(REFERER);