<?php
namespace ay\xhprof;

session_start();

if (isset($_GET['ay']['debug'])) {
	$_SESSION['ay']['debug'] = !empty($_GET['ay']['debug']);
}

if (isset($_GET['ay']['profiling'])) {
	$_SESSION['ay']['profiling'] = !empty($_GET['ay']['profiling']);
}

define('VERSION', '0.0.0');
define('BASE_PATH', realpath(__DIR__ . '/..'));

// These constants are required to maintain
// compatibility with the "ay" framework components.
define('ay\DEBUG', !empty($_SESSION['ay']['debug']));

define('ay\REDIRECT_REFERRER', 1);

define('ay\MESSAGE_NOTICE', 1);
define('ay\MESSAGE_SUCCESS', 2);
define('ay\MESSAGE_ERROR', 3);
define('ay\MESSAGE_IMPORTANT', 4);

define('ay\FORMAT_DATE', 'M j, Y');
define('ay\FORMAT_DATETIME', 'M j, Y H:i');

require BASE_PATH . '/includes/helpers.ay.inc.php';
require BASE_PATH . '/includes/helpers.xhprof.inc.php';

set_exception_handler('ay\error_exception_handler');
set_error_handler('ay\error_exception_handler');

$config = array();

if (file_exists(BASE_PATH . '/includes/config.inc.php')) {
    $config	= require BASE_PATH . '/includes/config.inc.php';
}

// Allow to overwrite default configuration in execution environment.
if (isset($xhprof_config)) {
	$config = array_merge($config, $xhprof_config);
}

if (!isset($config['url_base'], $config['pdo'])) {
	throw new \Exception('XHProf.io is not configured. Refer to /xhprof/includes/config.inc.sample.php.');
}

if (!isset($config['url_static'])) {
	$config['url_static'] = $config['url_base'] . 'public/';
}

define('BASE_URL', $config['url_base']);

// This class is likely already included by php.ini prepend/append settings
require_once BASE_PATH . '/classes/data.php';

require BASE_PATH . '/classes/model.php';
require BASE_PATH . '/classes/callgraph.php';

if (\AY\DEBUG && !empty($_SESSION['ay']['profiling'])) {
	require BASE_PATH . '/includes/profiler.inc.php';
}

if (filter_has_var(INPUT_POST, 'ay')) {
	array_walk_recursive($_POST['ay'], function(&$e){
		$e	= trim($e);
	});
	
	// Flash variable keeps track of the $_POST data in case there is an error 
	// validating the form input and user needs to be returned to the form.
	$_SESSION['ay']['flash']['input']	= $_POST['ay'];
}

if (empty($_SESSION['xhprof']['remote_version'])) {
	$ch			= curl_init();
	
	curl_setopt_array($ch, array(
		CURLOPT_URL => 'https://raw.github.com/gajus/xhprof.io/master/version.json',
		CURLOPT_HEADER => FALSE,
		CURLOPT_RETURNTRANSFER => TRUE
	));
	
	$response	= curl_exec($ch);
	
	curl_close($ch);
	
	$version	= json_decode($response, TRUE);
	
	if (!empty($version['version'])) {
		$_SESSION['xhprof']['remote_version']	= $version['version'];
	}
	
	unset($version, $response);
}

if (!empty($_SESSION['xhprof']['remote_version']) && $_SESSION['xhprof']['remote_version'] != VERSION) {
	\ay\message('You are running an out-of-date version of XHProf.io (' . VERSION . '). The <a href="http://xhprof.io/" target="_blank">current version is ' . htmlspecialchars($_SESSION['xhprof']['remote_version']) . '</a>.', \AY\MESSAGE_NOTICE);
	
	unset($_SESSION['xhprof']['remote_version']);
}