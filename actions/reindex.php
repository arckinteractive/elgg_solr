<?php

//vroomed
elgg_register_event_handler('shutdown', 'system', 'elgg_solr_reindex');

system_message(elgg_echo('elgg_solr:success:reindex'));
