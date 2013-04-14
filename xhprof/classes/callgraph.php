<?php
namespace ay\xhprof;

class Callgraph
{
	/**
	 * param	array	$callstack	The callstack must have UIDs.
	 * param	boolean	$output	TRUE will output the content to the stdout and set the Content-Type to text/plain
	 * param	boolean	$debug	TRUE will output a less complicated DOT script.
	 */
	public function dot($callstack, $output = FALSE, $debug = FALSE)
	{
		$players	= array();
		$calls		= array();
	
		$mother		= $callstack[0];
		
		if(!isset($mother['uid']))
		{
			throw new CallgraphException('Invalid callstack input. UIDs are not populated.');
		}
		
		$group_colors	= array('#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf');
		
		foreach($callstack as $e)
		{
			$callee_uid	= $e['uid'] . '_' . $e['callee_id'];
			
			if($e['caller'])
			{
				$calls[]	= "\t" . $e['uid'] . ' -> ' . $callee_uid . ';';
			}
			
			if(isset($players[$callee_uid]))
			{
				throw new CallgraphException('Duplicate player is not possible in an exclusive callstack.');
			}
			
			if($debug)
			{
				$players[$callee_uid]	= "\t" . $callee_uid . '[shape=square, label="' . $e['callee'] . '"];';
			}
			else
			{
				$ct	= '';
			
				if($e['caller'])
				{
					$ct	= '<tr>
						<td align="left" width="50">ct</td>
						<td align="left">' . $e['metrics']['ct'] . '</td>
					</tr>';
				}
				
				$column_group_color	= '';
				
				if(!empty($e['group']) && $e['group']['index'] < 10)
				{
					$column_group_color	= ' bgcolor="' . $group_colors[$e['group']['index']-1] . '"';
				}
				
				$players[$callee_uid]	= "\t" . $callee_uid . '[shape=none, label=<
				<table border="0" cellspacing="0" cellborder="1" cellpadding="5">
					<tr>
						<td colspan="2" align="left"' . $column_group_color . '>' . $e['callee'] . '</td>
					</tr>
					' . $ct . '
					<tr>
						<td align="left">wt</td>
						<td align="left" bgcolor="0.000 ' . sprintf('%.3f', $mother['metrics']['wt'] ? $e['metrics']['wt']/$mother['metrics']['wt'] : 0) . ' 1.000">' . format_microseconds($e['metrics']['wt'], false) . '</td>
					</tr>
					<tr>
						<td align="left">cpu</td>
						<td align="left" bgcolor="0.000 ' . sprintf('%.3f', $mother['metrics']['cpu'] ? $e['metrics']['cpu']/$mother['metrics']['cpu'] : 0) . ' 1.000">' . format_microseconds($e['metrics']['cpu'], false) . '</td>
					</tr>
					<tr>
						<td align="left">mu</td>
						<td align="left" bgcolor="0.000 ' . sprintf('%.3f', $mother['metrics']['mu'] ? $e['metrics']['mu']/$mother['metrics']['mu'] : 0) . ' 1.000">' . format_bytes($e['metrics']['mu'], 2, false) . '</td>
					</tr>
					<tr>
						<td align="left">pmu</td>
						<td align="left" bgcolor="0.000 ' . sprintf('%.3f', $mother['metrics']['pmu'] ? $e['metrics']['pmu']/$mother['metrics']['pmu'] : 0) . ' 1.000">' . format_bytes($e['metrics']['pmu'], 2, false) . '</td>
					</tr>
				</table>
				>];';
			}
		}
		
		$dot		=
			implode(PHP_EOL, $players) . PHP_EOL . PHP_EOL .
			implode(PHP_EOL, $calls);
		
		$dot		= "digraph\r{\r{$dot}\r}";
		
		if(!$output)
		{
			return $dot;
		}
		
		header('Content-Type: text/plain');
		
		echo $dot;
		
		exit;
	}
	
	public function graph($dot_script)
	{
		$descriptors	= array
		(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w')
		);
		
		$process		= proc_open('dot -Tpng', $descriptors, $pipes, BASE_PATH);
		
		if($process === FALSE)
		{
			throw new CallgraphException('Failed to initiate DOT process.');
		}
		
		fwrite($pipes[0], $dot_script);
		fclose($pipes[0]);
		
		$output			= stream_get_contents($pipes[1]);
		$error			= stream_get_contents($pipes[2]);
		
		fclose($pipes[1]);
		fclose($pipes[2]);
		
		proc_close($process);
		
		if(!empty($error))
		{
			throw new CallgraphException('DOT produced an error.');
		}
		
		if(empty($output))
		{
			throw new CallgraphException('DOT did not output anything.');
		}
		
		#header('Content-Type: image/svg+xml');
		header('Content-Type: image/png');
		
		echo $output;
		
		exit;
	}
}

class CallgraphException extends \Exception {}