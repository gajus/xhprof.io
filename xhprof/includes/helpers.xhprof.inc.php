<?php
namespace ay\xhprof;

function url($template = NULL, array $xhprof_query = NULL, array $xhprof = array())
{
	if($template === NULL)
	{
		return BASE_URL;
	}
	
	$query	= array
	(
		'xhprof'	=> $xhprof
	);
	
	$query['xhprof']['template']	= $template;
	
	if($xhprof_query)
	{
		$query['xhprof']['query']	= $xhprof_query;
	}
	
	// Technically, this invalidates the URL. However, I prefer a readable URL.
	return BASE_URL . '?' . str_replace(array('%5B', '%5D'), array('[', ']'), http_build_query($query));
}

/**
 * @author Andrew Moore, http://www.php.net/manual/en/function.uniqid.php#94959.
 */
function uuid()
{
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		
		// 16 bits for "time_mid"
		mt_rand( 0, 0xffff ),
		
		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand( 0, 0x0fff ) | 0x4000,
		
		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand( 0, 0x3fff ) | 0x8000,
		
		// 48 bits for "node"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}

function calculate_percentage_change($original_value, $new_value)
{
	if($original_value == 0)
	{
		return 'undefined';
	}
	
	return format_number((($new_value-$original_value)/$original_value)*100) . '%';
}

/**
 * Returns the shortest expression of the number available or 0 if number is smaller than 0.0000001.
 * @return	float	If number is less than 1E-5, will return a string.
 */
function format_number($number)
{
	$multiplier	= 100;
	$cap		= 5;
	
	$value		= 0;
	
	while($value == 0)
	{
		$value		= floor($number*$multiplier)/$multiplier;
		
		if(!--$cap)
		{
			// the number is too small to be relevant
			break;
		}
		
		$multiplier	= 10*$multiplier;
	}
	
	if( strpos((string) $value, 'E') !== FALSE )
	{
		return rtrim(sprintf('%F', $value), 0);
	}
	
	return $value;
}

function format_bytes($size, $precision = 2, $format = TRUE)
{
	if($size == 0)
	{
		return 0;
	}
	
    $base		= log(abs($size)) / log(1024);
    $suffixes	= array('b', 'k', 'M', 'G', 'T');
    
    $number		= round(pow(1024, $base - floor($base)), $precision);
    $suffix		= $suffixes[floor($base)];

    return $format ? '<span class="value">' . $number . '</span> <span class="measure">' . $suffix . '</span>' : $number . ' ' . $suffix;
}

function format_microseconds($time, $format = TRUE)
{
	$time	= (int) $time;

	$pad	= FALSE;
	$suffix	= 'µs';

	if (abs($time) >= 1000)
	{
		$time	= $time / 1000;
		$suffix	= 'ms';
		
		if (abs($time) >= 1000)
		{
			$pad	= TRUE;
			
			$time	= $time / 1000;
			$suffix	= 's';
			
			if (abs($time) >= 60)
			{
				$time	= $time / 60;
				$suffix	= 'm';
			}
		}
	}
	
	if($pad)
	{
		$time	= sprintf('%.4f', $time);
	}
	
	return $format ? '<span class="value">' . $time . '</span> <span class="measure">' . $suffix . '</span>' : $time . ' ' . $suffix;
}

/**
 * @param data|int $data Raw XHProf metrics data or specific metric value when $name is provided.
 * @param string $name Metrics name will force return data formatted just for the selected metrics.
 */
function format_metrics($data, $name = null)
{
	$format	= array(
		'request_count' => '\number_format',
		
		'ct' => 'ay\xhprof\format_number',
		'wt' => 'ay\xhprof\format_microseconds',
		'cpu' => 'ay\xhprof\format_microseconds',
		'mu' => 'ay\xhprof\format_bytes',
		'pmu' => 'ay\xhprof\format_bytes'
	);
	
	if ($name) {
		if (!isset($format[$name])) {
			throw new HelpersException('Invalid metrics parameter "' . $name . '".');
		}
		return array('raw' => $data, 'formatted' => call_user_func($format[$name], $data));
	}
	
	foreach ($data as $k => $v) {
		if (isset($format[$k])) {
			$data[$k] = array('raw' => $v, 'formatted' => call_user_func($format[$k], $v));
		}
	}
	
	return $data;
}

function validate_datetime($input)
{
	$format	= 'Y-m-d H:i:s';

	$date	= \DateTime::createFromFormat($format, $input);
	
	if($date === FALSE)
	{
		$format	= 'Y-m-d';
	
		$date	= \DateTime::createFromFormat($format, $input);
	}
	
	return (boolean) $date;
}

class HelpersException extends \Exception {}