<?php
namespace ay\xhprof;

ob_start();

require __DIR__ . '/xhprof/includes/bootstrap.inc.php';

// Mini-dispatcher.
$template			= array
(
	'file'			=> 'error',
	'title'			=> NULL
);

$templates			= array('requests', 'request', 'uris', 'hosts', 'function');

if(empty($_GET['xhprof']['template']))
{
	$_GET['xhprof']['template']	= 'hosts';
}

if(!in_array($_GET['xhprof']['template'], $templates))
{
	throw new \Exception('Invalid template.');
}

$template['file']	= $_GET['xhprof']['template'];

// This additional step is taken to make URLs pretty. I am aware that [] should be urlencoded. However,
// that makes the URLs unreadable. It is handy to be able to amend parameters directly in the URL query.
// Furthermore, this strips out empty parameters.
if(!empty($_POST['ay']['query']))
{
	$template	= $template['file'];
	
	$query		= array_filter($_POST['ay']['query']);
	
	if(isset($query['request_ids']))
	{
		$template		= 'request';
		$ids			= explode(',', $query['request_ids']);
		
		
		if(count($ids) == 1)
		{
			$query['request_id']		= $ids[0];
			
			unset($query['request_ids']);
		}
		else if(count($ids) == 2)
		{
			$query['request_id']		= $ids[0];
			$query['second_request_id']	= $ids[1];
			
			unset($query['request_ids']);
		}
		else
		{
			ay_redirect(\AY\REDIRECT_REFERRER, 'Sorry, this feature is currently not implemented.');
		
			$template	= 'requests';
		}
		
		unset($ids);
	}
	
	\ay\redirect(url($template, $query));
}

// $_GET['xhprof']['query'] is used throughout the code to filter data. NULL value will be ignored.
// This is a convenience method to prevent repetitious variable presence checking.
if(empty($_GET['xhprof']['query']))
{
	$_GET['xhprof']['query']	= NULL;
}
else
{
	foreach($_GET['xhprof']['query'] as $e)
	{
		if(is_array($e))
		{
			throw new \Exception('Defining a filter with a multidimensional array is not supported.');
		}
	}
	
	// ay_input() will look for the default input value in this globally accessible variable.
	$input	= array('query' => $_GET['xhprof']['query']);

	if(!empty($_GET['xhprof']['query']['datetime_from']) && !validate_datetime($_GET['xhprof']['query']['datetime_from']))
	{
		\ay\message('Invalid <mark>from</mark> date-time format.');
	}
	
	if(!empty($_GET['xhprof']['query']['datetime_to']) && !validate_datetime($_GET['xhprof']['query']['datetime_to']))
	{
		\ay\message('Invalid <mark>to</mark> date-time format.');
	}
	
	if(isset($_GET['xhprof']['query']['host'], $_GET['xhprof']['query']['host_id']))
	{
		\ay\message('<mark>host_id</mark> will overwrite <mark>host</mark>. Unset either to prevent unexpected results.');
	}
	
	if(isset($_GET['xhprof']['query']['uri'], $_GET['xhprof']['query']['uri_id']))
	{
		\ay\message('<mark>uri_id</mark> will overwrite <mark>uri</mark>. Unset either to prevent unexpected results.');
	}
}

$xhprof_data_obj	= new Data($config['pdo']);

ob_start();
require BASE_PATH . '/templates/' . $template['file'] . '.tpl.php';
$template['body']	= ob_get_clean();

require BASE_PATH . '/templates/frontend.layout.tpl.php';

unset($_SESSION['ay']['flash']);