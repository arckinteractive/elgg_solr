<?php
admin_gatekeeper();

$time = $vars['time'];

$filename = elgg_get_config("dataroot") . "elgg_solr/{$time}.txt";

$line = elgg_solr_get_log_line($filename);

if (!$line) {
	return;
}

if (elgg_is_xhr()) {
	// all we need to supply is the line
	header('Content-Type: application/json');
	echo $line;
	return;
}

elgg_require_js('elgg_solr/js/progress');

/*
 * 'percent' => '',
		'count' => 0, // report prior to indexing this entity
		'typecount' => 0,
		'fullcount' => 0,
		'type' => '',
		'querytime' => 0,
		'message' => 'Reindex complete'
 */

echo '<br><br>';
echo '<div id="solr-progress-results" data-time="' . $time . '">';
echo '<div>Currently indexing type: <span class="type"></span></div>';
echo '<div>Estimated type total: <span class="typetotal"></span></div>';
echo '<div>Indexed total: <span class="indexedtotal"></span></div>';
echo '<div>Batch Complete: <span class="percent"></span></div>';
echo '<div>Batch Query Time: <span class="querytime"></span></div>';
echo '<div>Last Log Update: <span class="logdate"></span></div>';
echo '<div>Message: <span class="message"></span></div>';
echo '<div><span class="restart"></span></div>';
echo '</div>';