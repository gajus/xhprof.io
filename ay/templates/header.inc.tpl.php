<?php
$template['navigation'][]	= array('url' => xhprof_url('hosts'), 'name' => 'Hosts', 'class' => $template['file'] == 'hosts' ? 'template active' : 'template');
$template['navigation'][]	= array('url' => xhprof_url('uris'), 'name' => 'URIs', 'class' => $template['file'] == 'uris' ? 'template active' : 'template');
$template['navigation'][]	= array('url' => xhprof_url('requests'), 'name' => 'Requests', 'class' => $template['file'] == 'requests' || $template['file'] == 'request' ? 'template active' : 'template');
?>
<?php if(!empty($template['navigation'])):?>
<div class="navigation">
<?php foreach($template['navigation'] as $e):?>
	<a href="<?=$e['url']?>"<?php if(!empty($e['class'])):?> class="<?=$e['class']?>"<?php endif;?>><?=$e['name']?></a>
<?php endforeach;?>
</div>
<?php endif;?>

<?php if(!ay_error_present() && !empty($_GET['xhprof']['query'])):
	
$labels	= array
(
	'host_id'		=> 'Host #',
	'host'			=> 'Host',
	'uri_id'		=> 'URI #',
	'uri'			=> 'URI',
	'request_id'	=> 'Request #',
	'callee_id'		=> 'Function #',
	'datetime_from'	=> 'Date-time from',
	'datetime_to'	=> 'Date-time to'
);

?>
<div class="filters">
	<p>The following filters affect the displayed data:</p>
	<dl>
	<?php foreach($_GET['xhprof']['query'] as $k => $v):
		
		if(!isset($labels[$k]))
		{
			throw new XHProfException('Filter label is not defined.');
		}
		
	?>
		<dt><?=$labels[$k]?></dt>
		<dd><?=htmlspecialchars($v)?></dd>
	<?php endforeach;?>
	</dl>
</div>
<?php

unset($labels);

endif;?>