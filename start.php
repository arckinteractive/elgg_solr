<?php

require_once 'lib/functions.php';
require_once 'lib/hooks.php';
require_once 'lib/events.php';

elgg_register_event_handler('init', 'system', 'elgg_solr_init');

/**
 *  Init elgg_solr plugin
 */
function elgg_solr_init() {
	
	// if the plugin is not configured lets leave search alone
	if (!elgg_solr_has_settings()) {
		return true;
	}
	
	elgg_register_library('Solarium', dirname(__FILE__) . '/lib/Solarium/Autoloader.php');
	
	// unregister default search hooks
	elgg_unregister_plugin_hook_handler('search', 'object', 'search_objects_hook');
	elgg_unregister_plugin_hook_handler('search', 'user', 'search_users_hook');
	elgg_unregister_plugin_hook_handler('search', 'group', 'search_groups_hook');

    elgg_register_plugin_hook_handler('search', 'object:file', 'elgg_solr_file_search');
	elgg_register_plugin_hook_handler('search', 'object', 'elgg_solr_object_search');
	elgg_register_plugin_hook_handler('search', 'user', 'elgg_solr_user_search');
	elgg_register_plugin_hook_handler('search', 'group', 'elgg_solr_group_search');
	
	elgg_register_plugin_hook_handler('cron', 'hourly', 'elgg_solr_cron_index');

    elgg_register_event_handler('create', 'all', 'elgg_solr_add_update_entity', 1000);
    elgg_register_event_handler('update', 'all', 'elgg_solr_add_update_entity', 1000);
    elgg_register_event_handler('delete', 'object', 'elgg_solr_delete_entity', 1000);
	elgg_register_event_handler('create', 'metadata', 'elgg_solr_metadata_update');
	elgg_register_event_handler('update', 'metadata', 'elgg_solr_metadata_update');
	
	// when to update the user index
	elgg_register_plugin_hook_handler('usersettings:save', 'user', 'elgg_solr_user_settings_save', 1000);
	elgg_register_event_handler('profileupdate','user', 'elgg_solr_add_update_entity', 1000);
	
	
	// register functions for indexing
	elgg_solr_register_solr_entity_type('object', 'file', 'elgg_solr_add_update_file');
	elgg_solr_register_solr_entity_type('user', 'default', 'elgg_solr_add_update_user');
	elgg_solr_register_solr_entity_type('object', 'default', 'elgg_solr_add_update_object_default');
	elgg_solr_register_solr_entity_type('group', 'default', 'elgg_solr_add_update_group_default');
	
	elgg_register_action('elgg_solr/reindex', dirname(__FILE__) . '/actions/reindex.php', 'admin');
	elgg_register_action('elgg_solr/delete_index', dirname(__FILE__) . '/actions/delete_index.php', 'admin');
	elgg_register_action('elgg_solr/reindex_unlock', dirname(__FILE__) . '/actions/reindex_unlock.php', 'admin');
}
