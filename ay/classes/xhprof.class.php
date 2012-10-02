<?php
class XHProf
{
	private $request;

	public function __construct($request)
	{
		$defined_functions		= get_defined_functions();
	
		foreach($request['callstack'] as &$call)
		{
			$call['internal']	= in_array($call['callee'], $defined_functions['internal']);
			$call['group']		= $this->getGroup($call);
			
			unset($call);
		}
	
		$this->request	= $request;
	}
	
	public function getCallstack()
	{
		return $this->request['callstack'];
	}
	 
	/**
	 * The raw data contains inclusive metrics of a function for each
	 * unique parent function it is called from. The total inclusive metrics
	 * for a function is therefore the sum of inclusive metrics for the
	 * function across all parents.
	 *
	 * @param	array	$whilelist Function IDs to include to the metrics. This will also include the parents and children of the whitelisted functions.
	 *
	 * @author	Gajus Kuizinas <g.kuizinas@anuary.com>
	 */
	private function computeInclusiveMetrics()
	{
		$functions	= array();
		
		foreach($this->request['callstack'] as $call)
		{
			if(!isset($functions[$call['callee_id']]))
			{
				$functions[$call['callee_id']]		= $call;
			
				continue;
			}
			
			foreach($functions[$call['callee_id']]['metrics'] as $k => &$v)
			{
				$v	+= $call['metrics'][$k];
			
				unset($v);
			}
		}
		
		return $functions;
	}
	
	/**
	 * Analyze the raw data, and compute a per-function (flat)
	 * inclusive and exclusive metrics.
	 *
	 * @author	Gajus Kuizinas <g.kuizinas@anuary.com>
	 * @param	int	$function_id	Defines the "current function." This will limit the output to parent function, current function ($function_id) and any direct children.
	 */
	public function getAggregatedStack($function_id = NULL)
	{
		$functions	= $this->computeInclusiveMetrics();
		
		// Format array to carry exclusive metrics and a copy of the inclusive metrics.
		foreach($functions as &$f)
		{
			$f['metrics']	= array
			(
				'ct'		=> $f['metrics']['ct'],
				'inclusive'	=> $f['metrics'],
				'exclusive'	=> $f['metrics']
			);
			
			unset($f['metrics']['inclusive']['ct'], $f['metrics']['exclusive']['ct'], $f);
		}
		
		#ay($this->request['callstack']);
		
		// Find all the function parents and deduct their resource usage from the child.
		foreach($this->request['callstack'] as $call)
		{
			if(empty($call['caller_id']))
			{
				continue;
			}
			
			foreach(['wt', 'cpu', 'mu', 'pmu'] as $v)
			{
				$functions[$call['caller_id']]['metrics']['exclusive'][$v]	-= $call['metrics'][$v];
			}
		}
		
		if($function_id !== NULL)
		{
			$functions	= array_filter($functions, function($e) use ($function_id) { return $e['caller_id'] == $function_id || $e['callee_id'] == $function_id; });
		}
		
		return array_values($functions);
	}
	
	public function assignUID()
	{
		$callstack	= $this->request['callstack'];
	
		foreach($callstack as &$c)
		{
			$c['parents']			= [];
			
			unset($c);
		}
		
		$callstack[0]['caller_id']	= 'xhprof';
		
		// Populate every tree node with an array of every ancestor they have.
		$populate_tree_parent		= function(&$node) use (&$populate_tree_parent, &$callstack)
		{
			if($node['caller_id'])
			{
				$node['parents'][]	= $node['caller_id'];
			}
			
			foreach($callstack as &$e)
			{
				// find children
				if($node['callee_id'] == $e['caller_id'])
				{
					// let the child know who is his father
					$e['parents']	= $node['parents'];
					
					$populate_tree_parent($e);
				}
				
				unset($e);
			}
		};
		
		$populate_tree_parent($callstack[0]);
		
		return array_map(function($e){
			// UID is used to determine the child-parent relation in an exclusive callgraph.
			$e['uid']	= implode('_', $e['parents']);
			
			// Unless we were looking to build an inclusive callgraph, this data is reduntant. We are not.
			unset($e['parents']);
			
			return $e;
		}, $callstack);
	}
	
	public function groupStack($aggregated_stack)
	{
		$groups	= array();
		
		foreach($aggregated_stack as $e)
		{
			// exclude "main()"
			if(empty($e['group']))
			{
				continue;
			}
		
			if(!isset($groups[$e['group']['index']]))
			{
				$groups[$e['group']['index']]	= array
				(
					'index'		=> $e['group']['index'],
					'name'		=> $e['group']['name'],
					'metrics'	=> $e['metrics']['exclusive']
				);
			}
			else
			{
				$groups[$e['group']['index']]['metrics']['wt']	+= $e['metrics']['exclusive']['wt'];
				$groups[$e['group']['index']]['metrics']['cpu']	+= $e['metrics']['exclusive']['cpu'];
				$groups[$e['group']['index']]['metrics']['mu']	+= $e['metrics']['exclusive']['mu'];
				$groups[$e['group']['index']]['metrics']['pmu']	+= $e['metrics']['exclusive']['pmu'];
			}
		}
		
		return $groups;
	}
	
	private $groups	= array();
	
	private function getGroup($e)
	{
		if(empty($e['caller']))
		{
			return;
		}
		
		if(!isset($e['internal'], $e['callee']))
		{
			throw new XHProfException('Data format does not comply with the XHProf conventions.');
		}
		
		if($e['internal'])
		{
			$group	= 'internal';
		}
		else
		{
			$group	= explode('::', $e['callee'], 2);
			
			$group	= count($group) === 1 ? 'user-defined function' : $group[0];
		}
		
		if(!isset($this->groups[$group]))
		{
			$this->groups[$group]	= array
			(
				'index'	=> count($this->groups)+1,
				'name'	=> $group
			);
		}
		
		return $this->groups[$group];
	}
}

class XHProfException extends Exception {}