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
	'elgg_solr:reindex_unlock:confirm' => "If the previous reindex has not finished, starting a new one could impact performance.  Only unlock the reindex if the previous reindex terminated unexpectedly.",
	'elgg_solr:reindex:unlock' => "Unlock Reindex",
	'elgg_solr:reindex:unlocked' => "Reindex has been unlocked",
	'elgg_solr:settings:title:misc' => "Miscellaneous Settings",
	'elgg_solr:settings:batch_size' => "Batch Size",
	'elgg_solr:settings:batch_size:help' => "The higher this value the faster the reindex function runs.  However the higher this value the more memory it will use.  Use too much memory and reindex will fail to work.  If unsure leave it at the default value of 50.",
	'elgg_solr:settings:core' => "Core",
	'elgg_solr:settings:core:help' => "If running a multi-core solr instance enter the name of the core Elgg should use eg. <strong>collection1</strong><br>  Leave blank for default.",
	'elgg_solr:settings:extract' => "Use Extract Handler?",
	'elgg_solr:settings:extract:help' => "The extract handler will index files uploaded to your site and allow them to be searchable by content.  While very common, not all solr implementations may have this configured.  If your does not then disable this setting.",
	'elgg_solr:indexed:compare' => "%s of %s items have been indexed.  The more these numbers differ the more inaccurate searching will be, run the 'Solr Reindex' to fix an inaccurate index."
);

add_translation("en", $english);
