<?php

const ELGG_SOLR_PLUGIN_VERSION = 20160522;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/hooks.php';
require_once __DIR__ . '/lib/events.php';

// load if it exists.  If not then it's not been composer installed or
// core has it in its vendor dir
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

elgg_register_event_handler('init', 'system', 'elgg_solr_init');

/**
 *  Init elgg_solr plugin
 */
function elgg_solr_init() {

	elgg_extend_view('admin.css', 'css/elgg_solr.css');

	// if the plugin is not configured lets leave search alone
	if (!elgg_solr_has_settings()) {
		return true;
	}


	if (elgg_get_plugin_setting('use_solr', 'elgg_solr') != 'no') {
		// unregister default search hooks
		elgg_unregister_plugin_hook_handler('search', 'object', 'search_objects_hook');
		elgg_unregister_plugin_hook_handler('search', 'user', 'search_users_hook');
		elgg_unregister_plugin_hook_handler('search', 'group', 'search_groups_hook');
		elgg_unregister_plugin_hook_handler('search', 'tags', 'search_tags_hook');

		elgg_register_plugin_hook_handler('search', 'object:file', 'elgg_solr_file_search');
		elgg_register_plugin_hook_handler('search', 'object', 'elgg_solr_object_search');
		elgg_register_plugin_hook_handler('search', 'user', 'elgg_solr_user_search');
		elgg_register_plugin_hook_handler('search', 'group', 'elgg_solr_group_search');
		elgg_register_plugin_hook_handler('search', 'tags', 'elgg_solr_tag_search');
	}

	elgg_register_plugin_hook_handler('cron', 'daily', 'elgg_solr_daily_cron');


	elgg_register_event_handler('create', 'all', 'elgg_solr_add_update_entity', 1000);
	elgg_register_event_handler('update', 'all', 'elgg_solr_add_update_entity', 1000);
	elgg_register_event_handler('delete', 'all', 'elgg_solr_delete_entity', 1000);
	elgg_register_event_handler('create', 'metadata', 'elgg_solr_metadata_update');
	elgg_register_event_handler('update', 'metadata', 'elgg_solr_metadata_update');
	elgg_register_event_handler('delete', 'metadata', 'elgg_solr_metadata_update');
	elgg_register_event_handler('create', 'annotation', 'elgg_solr_annotation_update');
	elgg_register_event_handler('update', 'annotation', 'elgg_solr_annotation_update');
	elgg_register_event_handler('delete', 'annotation', 'elgg_solr_annotation_delete');
	elgg_register_event_handler('create', 'relationship', 'elgg_solr_relationship_create');
	elgg_register_event_handler('delete', 'relationship', 'elgg_solr_relationship_delete');
	elgg_register_event_handler('upgrade', 'system', 'elgg_solr_upgrades');
	elgg_register_event_handler('disable', 'all', 'elgg_solr_disable_entity');
	elgg_register_event_handler('enable', 'all', 'elgg_solr_enable_entity');
	elgg_register_event_handler('shutdown', 'system', 'elgg_solr_entities_sync');
	elgg_register_event_handler('shutdown', 'system', 'elgg_solr_annotations_sync');
	
	elgg_register_plugin_hook_handler('elgg_solr:can_index', 'annotation', 'elgg_solr_annotation_can_index');

	elgg_set_config('elgg_solr_sync', []);
	elgg_set_config('elgg_solr_delete', []);

	// register functions for indexing
	elgg_solr_register_solr_entity_type('object', 'file', 'elgg_solr_add_update_file');
	elgg_solr_register_solr_entity_type('user', 'default', 'elgg_solr_add_update');
	elgg_solr_register_solr_entity_type('object', 'default', 'elgg_solr_add_update');
	elgg_solr_register_solr_entity_type('group', 'default', 'elgg_solr_add_update');

	// register hooks for indexing special objects
	elgg_register_plugin_hook_handler('elgg_solr:index', 'user', 'elgg_solr_index_user');
	elgg_register_plugin_hook_handler('elgg_solr:index', 'group', 'elgg_solr_index_group');

	elgg_register_action('elgg_solr/reindex', __DIR__ . '/actions/reindex.php', 'admin');
	elgg_register_action('elgg_solr/delete_index', __DIR__ . '/actions/delete_index.php', 'admin');
	elgg_register_action('elgg_solr/reindex_unlock', __DIR__ . '/actions/reindex_unlock.php', 'admin');
	elgg_register_action('elgg_solr/settings/save', __DIR__ . '/actions/plugin_settings.php', 'admin');
	elgg_register_action('elgg_solr/restart_reindex', __DIR__ . '/actions/restart_reindex.php', 'admin');
	elgg_register_action('elgg_solr/stop_reindex', __DIR__ . '/actions/stop_reindex.php', 'admin');

	elgg_register_admin_menu_item('administer', 'solr_index', 'administer_utilities');

	elgg_register_ajax_view('elgg_solr/ajax/progress');
}
