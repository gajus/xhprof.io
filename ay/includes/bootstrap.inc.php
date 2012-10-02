<?php
session_start();

define('XHPROF_VERSION', '0.1.0');

if(isset($_GET['ay']['debug']))
{
	$_SESSION['ay']['debug']		= !empty($_GET['ay']['debug']);
}

if(isset($_GET['ay']['profiling']))
{
	$_SESSION['ay']['profiling']	= !empty($_GET['ay']['profiling']);
}

// These constants are required to maintain
// compatability with the AY framework helpers.
define('AY_ROOT', realpath(__DIR__ . '/../..'));
define('AY_DEBUG', !empty($_SESSION['ay']['debug']));

define('AY_MESSAGE_NOTICE', 1);
define('AY_MESSAGE_SUCCESS', 2);
define('AY_MESSAGE_ERROR', 3);
define('AY_MESSAGE_IMPORTANT', 4);

define('AY_INTERFACE', 'frontend');

define('AY_TIMEZONE', 'Europe/London');

define('AY_FORMAT_DATE', 'M j, Y');
define('AY_FORMAT_DATETIME', 'M j, Y H:i');

$config	= require AY_ROOT . '/ay/includes/config.inc.php';

if(!isset($config['xhprof_url'], $config['pdo']))
{
	throw new Exception('XHProf.io is not configured. Refer to the /ay/includes/config.inc.php.');
}

define('AY_URL_FRONTEND', $config['xhprof_url']);

require AY_ROOT . '/ay/includes/helpers.ay.inc.php';
require AY_ROOT . '/ay/includes/helpers.xhprof.inc.php';

// If XHProf is included using php.ini prepend/append, require_once will prevent the same class being included more than once.
require_once AY_ROOT . '/ay/classes/xhprof.data.class.php';

require AY_ROOT . '/ay/classes/xhprof.class.php';
require AY_ROOT . '/ay/classes/xhprof.callgraph.class.php';

if(filter_has_var(INPUT_POST, 'ay'))
{
	array_walk_recursive($_POST['ay'], function(&$e){
		$e	= trim($e);
	});
	
	// Flash variable keeps track of the $_POST data in case there is an error 
	// validating the form input and user needs to be returned to the form.
	$_SESSION['ay']['flash']['input']	= $_POST['ay'];
}

class AyException extends Exception {}

set_exception_handler('ay_error_exception_handler');

if(AY_DEBUG && !empty($_SESSION['ay']['profiling']))
{
	$config['pdo']->exec("SET `profiling` = 1;");
	// keep track of as many as possible queries (the maximum value is 100)
	// http://dev.mysql.com/doc/refman/5.0/en/server-system-variables.html#sysvar_profiling_history_size
	$config['pdo']->exec("SET `profiling_history_size` = 100;");
	
	register_shutdown_function(function() use ($config) {
		
		$queries	= $config['pdo']->query("SHOW PROFILES;")->fetchAll(PDO::FETCH_ASSOC);
		
		$total_duration	= 0;
		
		$queries	= array_map(function($e) use(&$total_duration) { $e['Duration'] = 1000000*$e['Duration']; $total_duration += $e['Duration']; $e['Query'] = preg_replace('/\s+/', ' ', $e['Query']); return $e; }, $queries);
		
		?>
		<style>
			.mysql-debug-table { margin: 20px; }
			.mysql-debug-table table { width: 100%; }
			.mysql-debug-table th.id,
			.mysql-debug-table th.duration { width: 100px; }
		</style>
		<?php
		echo '
		<div class="mysql-debug-table">
			<table>
				<thead>
					<tr>
						<th class="id">Query ID</th>
						<th>Query</th>
						<th class="duration">Duration</th>
					</tr>
				</thead>
				<tbody>';
		foreach($queries as $q):
			echo '<tr><td>' . $q['Query_ID'] . '</td><td>' . $q['Query'] . '</td><td>' . xhprof_format_microseconds($q['Duration']) . '</td></tr>';
		endforeach;
		echo '
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2">Total</td>
						<td>' . xhprof_format_microseconds($total_duration) . '</td>
					</tr>
				</tfoot>
			</table>
		</div>';
	});
}

if(empty($_SESSION['xhprof']['remote_version']))
{
	$ch			= curl_init();
	
	curl_setopt_array($ch, array(
		CURLOPT_URL				=> 'http://xhprof.io/version',
		CURLOPT_HEADER			=> FALSE,
		CURLOPT_RETURNTRANSFER	=> TRUE
	));
	
	$response	= curl_exec($ch);
	
	curl_close($ch);
	
	$version	= json_decode($response, TRUE);
	
	if(!empty($version['version']))
	{
		$_SESSION['xhprof']['remote_version']	= $version['version'];
	}
	
	unset($version, $response);
}

if(!empty($_SESSION['xhprof']['remote_version']) && $_SESSION['xhprof']['remote_version'] != XHPROF_VERSION)
{
	ay_message('You are running an out-of-date version of XHProf.io (' . XHPROF_VERSION . '). The <a href="http://xhprof.io/" target="_blank">current version is ' . htmlspecialchars($_SESSION['xhprof']['remote_version']) . '</a>.', AY_MESSAGE_NOTICE);
}