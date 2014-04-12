<?php

admin_gatekeeper();

$title = 'Solr';

$content = elgg_view('elgg_solr/solr');

$body = elgg_view_layout('one_column', array(
    'content' => $content,
    'title' => null,
    'filter' => '',
));

echo elgg_view_page($title, $body);
