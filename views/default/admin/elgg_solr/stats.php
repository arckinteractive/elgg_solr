<?php
	$type = get_input('type');
	$subtype = get_input('subtype');
	$time = get_input('time');
	$block = get_input('block', false);
	
	if ($type == 'comments' && !$subtype) {
		$stats = elgg_solr_get_comment_stats($time, $block);
	}
	else {
		$stats = elgg_solr_get_stats($time, $block, $type, $subtype);
	}
	
	$datetime = elgg_solr_get_display_datetime($time, $block);
?>
<div class="elgg-solr-stats">
	<h2><?php echo $type; echo $subtype ? ':' . $subtype : ''; ?></h2>
	<h2><?php echo $datetime; ?></h2>
	<br>
<table>
	<tr>
		<td>
			<strong><?php echo elgg_echo('elgg_solr:time:interval'); ?></strong>
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
	<?php
	
		foreach ($stats as $key => $value):
			$system_total += (int)$value['count'];
			$indexed_total += (int)$value['indexed'];
		?>
	<tr>
		<td>
			<?php echo $key; ?>
		</td>
		<td>
			<?php
				echo elgg_view('output/url', array(
					'text' => (int)$value['count'],
					'href' => "admin/elgg_solr/list_entities?type={$type}&subtype={$subtype}&starttime={$value['starttime']}&endtime={$value['endtime']}"
				));
			?>
		</td>
		<td>
			<?php echo (int)$value['indexed']; ?>
		</td>
		<td>
			<?php
				
				$url = "action/elgg_solr/reindex?type={$type}";
				if ($subtype) {
					$url .= "&subtype={$subtype}";
				}
				$url .= "&starttime={$value['starttime']}&endtime={$value['endtime']}";
				
				echo elgg_view('output/url', array(
					'text' => elgg_echo('elgg_solr:reindex'),
					'href' => $url,
					'is_trusted' => true,
					'is_action' => true,
					'class' => 'elgg-requires-confirmation'
				));
				
				if ($value['block']) {
					echo ' | ';
				
					echo elgg_view('output/url', array(
						'text' => elgg_echo("elgg_solr:stats:by{$value['block']}"),
						'href' => "admin/elgg_solr/stats?time={$value['starttime']}&block={$value['block']}&type={$type}&subtype={$subtype}"
					));
				}
			?>
		</td>
	</tr>
	<?php endforeach; ?>
	<tr>
		<td>
			<strong><?php echo elgg_echo('elgg_solr:totals'); ?></strong>
		</td>
		<td>
			<?php echo (int) $system_total; ?>
		</td>
		<td>
			<?php echo (int) $indexed_total; ?>
		</td>
		<td></td>
	</tr>
</table>
<?php
echo elgg_view('output/longtext', array(
		'value' => elgg_echo('elgg_solr:indexed:compare', array(
			$indexed_total,
			$system_total
		)
	)
));
?>
</div>











