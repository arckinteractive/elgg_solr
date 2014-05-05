<?php

/**
 * set default values for new plugin settings
 */
function elgg_solr_upgrade_20140504a() {
	
	$title_boost = elgg_get_plugin_setting('title_boost', 'elgg_solr');

	if (empty($title_boost) && $title_boost !== '0') {
		elgg_set_plugin_setting('title_boost', '1.5', 'elgg_solr');
	}

	$description_boost = elgg_get_plugin_setting('description_boost', 'elgg_solr');

	if (empty($description_boost) && $description_boost !== '0') {
		elgg_set_plugin_setting('description_boost', '1', 'elgg_solr');
	}

	$use_time_boost = elgg_get_plugin_setting('use_time_boost', 'elgg_solr');

	if (empty($use_time_boost)) {
		elgg_set_plugin_setting('use_time_boost', 'no', 'elgg_solr');
	}

	$time_boost_num = elgg_get_plugin_setting('time_boost_num', 'elgg_solr');

	if (empty($time_boost_num)) {
		elgg_set_plugin_setting('time_boost_num', '1', 'elgg_solr');
	}

	$time_boost_interval = elgg_get_plugin_setting('time_boost_interval', 'elgg_solr');

	if (empty($time_boost_interval)) {
		elgg_set_plugin_setting('time_boost_interval', 'year', 'elgg_solr');
	}

	$time_boost = elgg_get_plugin_setting('time_boost', 'elgg_solr');

	if (empty($time_boost) && $time_boost !== '0') {
		elgg_set_plugin_setting('time_boost', '1.5', 'elgg_solr');
	}
}
