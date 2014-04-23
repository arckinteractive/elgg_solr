<?php

echo elgg_view('elgg_solr/admin_nav');

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
		