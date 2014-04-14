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
);

add_translation("en", $english);
