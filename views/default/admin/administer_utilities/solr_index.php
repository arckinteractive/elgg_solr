<?php

if (!elgg_solr_has_settings()) {
	register_error(elgg_echo('elgg_solr:missing:settings'));
	forward('admin/plugin_settings/elgg_solr');
}

echo elgg_view('elgg_solr/admin_nav');

elgg_register_menu_item('elgg_solr_controls', array(
	'name' => 'solr_delete_index',
	'text' => elgg_echo('elgg_solr:delete_index'),
	'href' => 'action/elgg_solr/delete_index',
	'is_action' => true,
	'is_trusted' => true,
	'link_class' => 'elgg-button elgg-button-action elgg-requires-confirmation',
	'confirm' => elgg_echo('elgg_solr:delete_index:confirm')
));
		
if (elgg_get_plugin_setting('reindex_running', 'elgg_solr')) {
	elgg_register_menu_item('elgg_solr_controls', array(
		'name' => 'solr_reindex_unlock',
		'text' => elgg_echo('elgg_solr:reindex:unlock'),
		'href' => 'action/elgg_solr/reindex_unlock',
		'is_action' => true,
		'is_trusted' => true,
		'link_class' => 'elgg-button elgg-button-action elgg-requires-confirmation',
		'confirm' => elgg_echo('elgg_solr:reindex_unlock:confirm')
	));
}
else {
	elgg_register_menu_item('elgg_solr_controls', array(
		'name' => 'solr_reindex',
		'text' => elgg_echo('elgg_solr:reindex'),
		'href' => 'action/elgg_solr/reindex',
		'is_action' => true,
		'is_trusted' => true,
		'link_class' => 'elgg-button elgg-button-action elgg-requires-confirmation',
		'confirm' => elgg_echo('elgg_solr:reindex:confirm')
	));
}


$title = elgg_echo('elgg_solr:index:controls');

$body = elgg_view_menu('elgg_solr_controls', array(
	'class' => 'elgg-menu-hz',
	'item_class' => 'mrm',
));

$body .= '<br><br>';

$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:indexed:compare', array(
		elgg_solr_get_indexed_count(),
		elgg_solr_get_indexable_count()
	))
));
echo elgg_view_module('main', $title, $body);






echo elgg_view('elgg_solr/stats');