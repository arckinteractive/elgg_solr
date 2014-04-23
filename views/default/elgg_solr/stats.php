<?php

$stats = array();
$registered_types = get_registered_entity_types();

foreach ($registered_types as $type => $subtypes) {
	$options = array(
		'type' => $type,
		'count' => true
	);

	if ($subtypes) {
		if (!is_array($subtypes)) {
			$subtypes = array($subtypes);
		}
		
		foreach ($subtypes as $s) {
			$options['subtype'] = $s;
			$count = elgg_get_entities($options);
			$indexed = elgg_solr_get_indexed_count("type:{$type}", array('subtype' => "subtype:{$s}"));
			$stats["{$type}:{$s}"] = array('count' => $count, 'indexed' => $indexed);
		}
		continue;
	}
	
	$count = elgg_get_entities($options);
	$indexed = elgg_solr_get_indexed_count("type:{$type}");
	
	$stats[$type] = array('count' => $count, 'indexed' => $indexed);
}

?>
<table>
	<tr>
		<td>
			<strong><?php echo elgg_echo('elgg_solr:type:subtype'); ?></strong>
		</td>
		<td>
			<strong><?php echo elgg_echo('elgg_solr:system:count'); ?></strong>
		</td>
		<td>
			<strong><?php echo elgg_echo('elgg_solr:solr:count'); ?></strong>
		</td>
	</tr>
	<?php foreach ($stats as $key => $value):	?>
	<tr>
		<td>
			<?php echo $key; ?>
		</td>
		<td>
			<?php echo (int)$value['count']; ?>
		</td>
		<td>
			<?php echo (int)$value['indexed']; ?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>













