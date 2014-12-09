<?php

$english = array(
	'admin:administer_utilities:solr_index' => "Solr Index",
	'elgg_solr' => "Elgg Solr",
	'elgg_solr:index:controls' => "Solr Index Controls",
	'elgg_solr:settings' => "Solr Settings",
	'elgg_solr:settings:title:adapter_options' => "Adapter Options",
	'elgg_solr:settings:host' => "Host",
	'elgg_solr:settings:host:help' => "Enter the host of the solr installation without http(s) prefix.  eg. <strong>solr.example.com</strong>",
	'elgg_solr:settings:port' => "Port",
	'elgg_solr:settings:port:help' => "Enter the port # of the solr installation.  eg. <strong>8983</strong>",
	'elgg_solr:settings:path' => "Path",
	'elgg_solr:settings:path:help' => "Path to the solr endpoint with starting and trailing slashes. eg. <strong>/solr/</strong>",
	'elgg_solr:settings:protocol' => "Protocol",
	'elgg_solr:settings:protocol:help' => "Select the protocol to connect to the solr instance.",
	'elgg_solr:reindex:full' => "Full Re-Index",
	'elgg_solr:reindex' => "Re-Index",
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
	'elgg_solr:settings:extract:help' => "The extract handler will index files uploaded to your site and allow them to be searchable by content.  While very common, not all solr implementations may have this configured.  If yours does not then disable this setting.",
	'elgg_solr:indexed:compare' => "%s of %s items have been indexed.  The more these numbers differ the more inaccurate searching will be, run a re-index to fix an inaccurate index.  If the Reindex is locked but the indexed count does not change after refreshing the page the reindex script may have terminated unexpectedly.  If this happens repeatedly ensure that your server has plenty of memory, try reducing the batch size (on the settings page).  If some items refuse to be indexed they may include some corrupt characters such as those copied from an invalid character set.  While the indexer tries to work around them some items simply may not be indexable.",
	'elgg_solr:missing:settings' => "You must configure Solr before accessing advanced settings",
	'elgg_solr:type:subtype' => "Type:Subtype",
	'elgg_solr:system:count' => "System Count",
	'elgg_solr:solr:count' => "Solr Indexed Count",
	'elgg_solr:totals' => "Totals",
	'elgg_solr:stats:byyear' => "View Year",
	'elgg_solr:stats:bymonth' => "View Month",
	'elgg_solr:stats:byday' => "View Day",
	'elgg_solr:stats:byhour' => "View Hour",
	'elgg_solr:stats:byminute' => "View Minute",
	'elgg_solr:time:interval' => "Interval",
	'admin:elgg_solr' => "Elgg Solr",
	'admin:elgg_solr:stats' => "Index by time",
	'elgg_solr:time:all' => "All Time",
	'admin:elgg_solr:list_entities' => "Entity List",
	'elgg_solr:settings:title:use_solr' => "Solr Search",
	'elgg_solr:settings:use_solr' => "Use Solr for returning search results?",
	'elgg_solr:settings:use_solr:help' => "Setting this to 'No' will let search fall back to the default search handlers while allowing you to keep this plugin active.  Useful for using Elgg default search while rebuilding the solr index.",
	'elgg_solr:index:delete' => "Delete Index",
	'elgg_solr:settings:title:query' => "Advanced Query Settings",
	'elgg_solr:settings:query:title_boost' => "Title Match Boost",
	'elgg_solr:settings:query:title_boost:help' => "A multiplier boost to relevancy when a title match is found.  The higher this number compared to other field boosts the more the title will be considered relevant.  Default: 1.5",
	'elgg_solr:settings:query:description_boost' => "Description Match Boost",
	'elgg_solr:settings:query:description_boost:help' =>"A multiplier boost to relevancy when a description match is found.  The higher this number compared to other field boosts the more the description will be considered relevant.  Default: 1",
	'elgg_solr:settings:query:time_boost' => "Boost the relevancy of most recently created content?",
	'elgg_solr:settings:query:time_boost:settings' => "Recent content boost",
	'elgg_solr:settings:query:time_boost:period' => "Increase relevance of content created within the last",
	'elgg_solr:time:day' => "Day(s)",
	'elgg_solr:time:week' => "Week(s)",
	'elgg_solr:time:month' => "Month(s)",
	'elgg_solr:time:year' => "Year(s)",
	'elgg_solr:settings:query:time_boost:by' => "with a boost of",
	'elgg_solr:settings:query:time_boost:help' => "Boost the relevancy of recent content.  The higher the boost value the more relevant it will become.  Default: 1.5",
	'elgg_solr:settings:highlight:prefix' => "Highlight Prefix",
	'elgg_solr:settings:highlight:prefix:help' => "HTML to insert in front of strings found to match the query.  Eg. &lt;strong&gt;",
	'elgg_solr:settings:highlight:suffix' => "Highlight Suffix",
	'elgg_solr:settings:highlight:suffix:help' => "HTML to insert after strings found to match the query.  Eg. &lt;/strong&gt;",
	'elgg_solr:settings:show_score' => "Show relevance score in results?",
	'elgg_solr:settings:show_score:help' => "This will only be displayed to admins, but can be useful for testing/tweaking your boost values.",
	'elgg_solr:relevancy' => "Relevance Score: %s",
	'elgg_solr:reindex:restart' => "If the reindex stops unexpectedly you can restart it from where it left off: %s<br>Note: only use this if you are sure the process has halted."
);

add_translation("en", $english);
