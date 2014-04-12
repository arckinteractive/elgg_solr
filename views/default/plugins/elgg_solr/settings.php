<?php

$title = elgg_echo('elgg_solr:reindex');

$body = elgg_view('output/url', array(
	'text' => elgg_echo('elgg_solr:reindex'),
	'href' => 'action/elgg_solr/reindex',
	'is_action' => true,
	'is_trusted' => true,
	'class' => 'elgg-button elgg-requires-confirmation'
));

$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:reindex:help'),
	'class' => 'elgg-subtext'
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
	'name' => 'params[path]',
	'value' => $vars['entity']->path
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:path:help'),
	'class' => 'elgg-subtext'
));

$body .= '<label>' . elgg_echo('elgg_solr:settings:protocol') . '</label><br>';
$body .= elgg_view('input/dropdown', array(
	'name' => 'params[protocol]',
	'value' => $vars['entity']->protocol,
	'options_values' => array(
		'http://' => 'http',
		'https://' => 'https'
	)
));
$body .= elgg_view('output/longtext', array(
	'value' => elgg_echo('elgg_solr:settings:protocol:help'),
	'class' => 'elgg-subtext'
));

echo elgg_view_module('main', $title, $body);