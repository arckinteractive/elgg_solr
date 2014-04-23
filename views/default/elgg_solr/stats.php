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

// comments
$stats['comments'] = array(
	'count' => elgg_get_annotations(array('annotation_name' => 'generic_comment', 'count' => true)),
	'indexed' => elgg_solr_get_indexed_count('type:annotation', array('subtype' => 'subtype:generic_comment'))
);

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
		<td>
			
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
		<td>
			<?php
				$type_subtype = explode(':', $key);
				
				$url = "action/elgg_solr/reindex?type={$type_subtype[0]}";
				if ($type_subtype[1]) {
					$url .= "&subtype={$type_subtype[1]}";
				}
				echo elgg_view('output/url', array(
					'text' => elgg_echo('elgg_solr:reindex'),
					'href' => $url,
					'is_trusted' => true,
					'is_action' => true,
					'class' => 'elgg-requires-confirmation'
				));
			?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>













