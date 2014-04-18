<?php

$english = array(
	'elgg_solr' => "Elgg Solr",
	'elgg_solr:controls' => "Solr Controls",
	'elgg_solr:settings:title:adapter_options' => "Adapter Options",
	'elgg_solr:settings:host' => "Host",
	'elgg_solr:settings:host:help' => "Enter the host of the solr installation without http(s) prefix.  eg. <strong>solr.example.com</strong>",
	'elgg_solr:settings:port' => "Port",
	'elgg_solr:settings:port:help' => "Enter the port # of solr installation.  eg. <strong>8983</strong>",
	'elgg_solr:settings:path' => "Path",
	'elgg_solr:settings:path:help' => "Path to the solr endpoint with starting and trailing slashes. eg. <strong>/solr/</strong>",
	'elgg_solr:settings:protocol' => "Protocol",
	'elgg_solr:settings:protocol:help' => "Select the protocol to connect to the solr instance.",
	'elgg_solr:reindex' => "Solr Re-Index",
	'elgg_solr:success:reindex' => "Solr reindex is now running in the background, note that it may take a long time to complete",
	'elgg_solr:reindex:confirm' => "This will reindex all content on the site.  It may take a very long time for large amounts of data.  Are you sure you want to continue?",
	'elgg_solr:delete_index' => "Delete Solr Index",
	'elgg_solr:delete_index:confirm' => "This will delete the index removing all content in solr.  Are you sure you want to continue?",
	'elgg_solr:success:delete_index' => "Solr Index has been deleted",
	'elgg_solr:error:index_running' => "Reindex could not be started as a previous session is still running.  If the previous session has stopped unexpectedly you must unlock the index first.",
	'elgg_solr:reindex:unlock' => "Unlock Reindex",
	'elgg_solr:reindex:unlocked' => "Reindex has been unlocked",
	'elgg_solr:settings:title:misc' => "Miscellaneous Settings",
	'elgg_solr:settings:batch_size' => "Batch Size",
	'elgg_solr:settings:batch_size:help' => "The higher this value the faster the reindex function runs.  However the higher this value the more memory it will use.  Use too much memory and reindex will fail to work.  If unsure leave it at the default value of 50.",
);

add_translation("en", $english);
