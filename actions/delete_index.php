<?php

elgg_solr_push_doc('<delete><query>*:*</query></delete>');

system_message(elgg_echo('elgg_solr:success:delete_index'));
forward(REFERER);