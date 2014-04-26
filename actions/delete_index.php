<?php

//elgg_solr_push_doc('<delete><query>*:*</query></delete>');
// create a client instance
$client = elgg_solr_get_client();

// get an update query instance
$update = $client->createUpdate();

// add the delete query and a commit command to the update query
$update->addDeleteQuery('*:*');
$update->addCommit();

// this executes the query and returns the result
$result = $client->update($update);

system_message(elgg_echo('elgg_solr:success:delete_index'));
forward(REFERER);