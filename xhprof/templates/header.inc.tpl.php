<?php
namespace ay\xhprof;

$navigation	= array
(
	array('url' => url('hosts'), 'name' => 'Hosts', 'class' => $template['file'] == 'hosts' ? 'template active' : 'template'),
	array('url' => url('uris'), 'name' => 'URIs', 'class' => $template['file'] == 'uris' ? 'template active' : 'template'),
	array('url' => url('requests'), 'name' => 'Requests', 'class' => $template['file'] == 'requests' || $template['file'] == 'request' ? 'template active' : 'template')
);
?>
<div id="navigation">
	<div class="button-filter">Filter</div>
	<div class="button-summary">Summary</div>

<?php foreach($navigation as $e):?>
	<a href="<?=$e['url']?>"<?php if(!empty($e['class'])):?> class="<?=$e['class']?>"<?php endif;?>><?=$e['name']?></a>
<?php endforeach;?>

<?php if($template['file'] == 'request' && empty($_GET['xhprof']['query']['second_request_id'])):?>
	<a href="<?=url('request', array('request_id' => $request['id']), array('callgraph' => 1))?>" class="callgraph" target="_blank">Callgraph</a>
<?php endif;?>
</div>
<?php
unset($navigation);

if(!\ay\error_present() && !empty($_GET['xhprof']['query'])):
	
$labels	= array
(
	'host_id'			=> 'Host #',
	'host'				=> 'Host',
	'uri_id'			=> 'URI #',
	'uri'				=> 'URI',
	'request_id'		=> 'Request #',
	'second_request_id'	=> 'Second Request #',
	'callee_id'			=> 'Function #',
	'datetime_from'		=> 'Date-time from',
	'datetime_to'		=> 'Date-time to',
	'dataset_size'		=> 'Dataset Size'
);

?>
<div class="filters">
	<p>The following filters affect the displayed data:</p>
	<dl>
	<?php foreach($_GET['xhprof']['query'] as $k => $v):
		
		if(!isset($labels[$k]))
		{
			throw new \Exception('Filter label is not defined.');
		}
		
		if($k == 'request_id'):
	?>
		<dt><?=$labels[$k]?></dt>
		<dd><a href="<?=url('request', array('request_id' => $v))?>"><?=htmlspecialchars($v)?></a></dd>
		<?php else:?>
		<dt><?=$labels[$k]?></dt>
		<dd><?=htmlspecialchars($v)?></dd>
		<?php endif;?>
	<?php endforeach;?>
	</dl>
</div>
<?php

unset($labels);

endif;?>