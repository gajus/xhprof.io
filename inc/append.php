<?php

return;
if(!in_array($_SERVER['HTTP_HOST'], ['xhprof.io', 'sinonimai.lt']))
{
	#return;
}

$xhprof_data	= xhprof_disable();

if(function_exists('fastcgi_finish_request'))
{
	fastcgi_finish_request();
}

// CLI environment is currently not supported
if(php_sapi_name() == 'cli')
{
	return;
}

$config			= require __DIR__ . '/../ay/includes/config.inc.php';

require_once __DIR__ . '/../ay/classes/xhprof.class.php';

$xhprof_data_obj	= new XHProfData($config['pdo']);
$xhprof_data_obj->save($xhprof_data);