<?php

$english = array(
	'elgg_solr' => "Elgg Solr",
	'elgg_solr:settings:title:adapter_options' => "Adapter Options",
	'elgg_solr:settings:host' => "Host",
	'elgg_solr:settings:host:help' => "Enter the host of the solr installation without http(s) prefix.  eg. <strong>solr.example.com</strong>",
	'elgg_solr:settings:port' => "Port",
	'elgg_solr:settings:port:help' => "Enter the port # of solr installation.  eg. <strong>8389</strong>",
	'elgg_solr:settings:path' => "Path",
	'elgg_solr:settings:path:help' => "Path to the solr endpoint with starting and trailing slashes. eg. <strong>/solr/</strong>",
	'elgg_solr:settings:protocol' => "Protocol",
	'elgg_solr:settings:protocol:help' => "Select the protocol to connect to the solr instance.",
	'elgg_solr:reindex' => "ReIndex",
	'elgg_solr:reindex:help' => "Clicking the above link will delete the existing index and rebuild from scratch.  @WARNING - this can take a long time for large amounts of data.  The page will likely time out, but the index will be built in the background.",
);

add_translation("en", $english);
