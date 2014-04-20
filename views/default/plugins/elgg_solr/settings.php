<?php

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

$title = elgg_echo('elgg_solr:controls');

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

if (elgg_solr_has_settings()) {
	echo elgg_view_module('main', $title, $body);
}




$title = elgg_echo('elgg_solr:settings:title:adapter_options');

$body = '<label>' . elgg_echo('elgg_solr:settings:host') . '</label>';
$body .= elgg_view('input/text', array(
	'name' => 'params[host]',
	'value' => $vars['entity']->host
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:host:help'),
	'class' => 'elgg-subtext'
));

$body .= '<label>' . elgg_echo('elgg_solr:settings:port') . '</label>';
$body .= elgg_view('input/text', array(
	'name' => 'params[port]',
	'value' => $vars['entity']->port
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:port:help'),
	'class' => 'elgg-subtext'
));

$body .= '<label>' . elgg_echo('elgg_solr:settings:path') . '</label>';
$body .= elgg_view('input/text', array(
	'name' => 'params[solr_path]',
	'value' => $vars['entity']->solr_path
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:path:help'),
	'class' => 'elgg-subtext'
));

$body .= '<label>' . elgg_echo('elgg_solr:settings:core') . '</label>';
$body .= elgg_view('input/text', array(
	'name' => 'params[solr_core]',
	'value' => $vars['entity']->solr_core
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:core:help'),
	'class' => 'elgg-subtext'
));

echo elgg_view_module('main', $title, $body);


$title = elgg_echo('elgg_solr:settings:title:misc');

$body = '<label>' . elgg_echo('elgg_solr:settings:batch_size') . '</label><br>';
$body .= elgg_view('input/dropdown', array(
	'name' => 'params[reindex_batch_size]',
	'value' => $vars['entity']->reindex_batch_size,
	'options' => array(25,50,100,200,300,400,500)
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:batch_size:help'),
	'class' => 'elgg-subtext'
));


$body .= '<label>' . elgg_echo('elgg_solr:settings:extract') . '</label><br>';
$body .= elgg_view('input/dropdown', array(
	'name' => 'params[extract_handler]',
	'value' => $vars['entity']->extract_handler,
	'options_values' => array(
		'yes' => elgg_echo('option:yes'),
		'no' => elgg_echo('option:no')
	)
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:extract:help'),
	'class' => 'elgg-subtext'
));
		
echo elgg_view_module('main', $title, $body);
		