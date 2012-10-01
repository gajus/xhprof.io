<?php
function xhprof_url($template = NULL, array $xhprof_query = NULL, array $ay_query = array())
{
	if($template === NULL)
	{
		return AY_URL_FRONTEND;
	}
	
	$query					= array();
	
	$query['ay']			= array('template' => $template) + $ay_query;
	
	if($xhprof_query)
	{
		$query['xhprof']	= array('query' => $xhprof_query);
	}
	
	return AY_URL_FRONTEND . '?' . str_replace(array('%5B', '%5D'), array('[', ']'), http_build_query($query));
}

function xhprof_uuid()
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

function xhprof_format_metrics(array $data)
{
	$format	= array
	(
		'request_count'	=> 'number_format',
		
		'ct'			=> 'xhprof_format_number',
		'wt'			=> 'xhprof_format_microseconds',
		'cpu'			=> 'xhprof_format_microseconds',
		'mu'			=> 'xhprof_format_bytes',
		'pmu'			=> 'xhprof_format_bytes'
	);
	
	/*array_walk_recursive($data, function($v, $k) use ($format) {
		if(isset($format[$k]))
		{
			$v	= array('raw' => $v, 'formatted' => call_user_func($format[$k], $v));
		}
	});
	
	return $data;*/
	
	foreach($data as $k => $v)
	{
		if(isset($format[$k]))
		{
			$data[$k]	= array('raw' => $v, 'formatted' => call_user_func($format[$k], $v));
		}
	}
	
	return $data;
}

function xhprof_calculate_percentage_change($original_value, $new_value)
{
	if($original_value == 0)
	{
		return 'undefined';
	}
	
	return xhprof_format_number((($new_value-$original_value)/$original_value)*100) . '%';
}

// @todo why the inconsistency? xhprof_number_format()
function xhprof_format_number($number)
{
	$multiplier	= 100;
	$cap		= 5;
	
	$value		= 0;
	
	while($value == 0)
	{
		$value		= floor($number*$multiplier)/$multiplier;
		
		if(!--$cap)
		{
			// the number is too small to be relavent
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

function xhprof_format_bytes($size, $precision = 2, $format = TRUE)
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

function xhprof_format_microseconds($time, $format = TRUE)
{
	$time	= (int) $time;

	$pad	= FALSE;
	$suffix	= 'µs';

	if ($time >= 1000)
	{
		$time	= $time / 1000;
		$suffix	= 'ms';
		
		if ($time >= 1000)
		{
			$pad	= TRUE;
			
			$time	= $time / 1000;
			$suffix	= 's';
			
			if ($time >= 60)
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

function xhprof_recursive_unset(&$array, $blacklist)
{
	foreach($blacklist as $key)
	{
		unset($array[$key]);
	}
	
	foreach ($array as &$value)
	{
		if(is_array($value))
		{
			xhprof_recursive_unset($value, $unwanted_key);
		}
	}
}

function xhprof_validate_datetime($input)
{
	$format	= 'Y-m-d H:i:s';

	$date	= DateTime::createFromFormat($format, $input);
	
	if($date === FALSE)
	{
		$format	= 'Y-m-d';
	
		$date	= DateTime::createFromFormat($format, $input);
	}
	
	return (boolean) $date;
}