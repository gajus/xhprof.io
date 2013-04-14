<?php
namespace ay\xhprof;

use PDO;

class Data
{
	private $db;

	public function __construct(PDO $db)
	{		
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
			$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, FALSE);
		}

		$this->db	= $db;
	}
 
    public function get($id)
    {
	    $sth	= $this->db->prepare("
	    	SELECT
	    		`r1`.`id`,
	    		UNIX_TIMESTAMP(`r1`.`request_timestamp`) `request_timestamp`,
	    		`rh1`.`id` `host_id`,
	    		`rh1`.`host`,
	    		`ru1`.`id` `uri_id`,
	    		`ru1`.`uri`
	    	FROM
	    		`requests` `r1`
	    	INNER JOIN
	    		`request_hosts` `rh1`
	    	ON
	    		`rh1`.`id` = `r1`.`request_host_id`
	    	INNER JOIN
	    		`request_uris` `ru1`
	    	ON
	    		`ru1`.`id` = `r1`.`request_uri_id`
	    	WHERE
	    		`r1`.`id` = :id
	    	LIMIT 1;");
	    	
	    $sth->execute(array('id' => $id));
	    
	    $request	= $sth->fetch(PDO::FETCH_ASSOC);
	    
	    $sth->closeCursor();
	    
	    if(!$request)
	    {
		    return FALSE;
	    }
	    
	   $sth	= $this->db->prepare("
		    SELECT
		    	`c1`.`ct`,
		    	`c1`.`wt`,
		    	`c1`.`cpu`,
		    	`c1`.`mu`,
		    	`c1`.`pmu`,
		    	`p1`.`id` `caller_id`,
		    	`p1`.`name` `caller`,
		    	`p2`.`id` `callee_id`,
		    	`p2`.`name` `callee`
		    FROM
		    	`calls` `c1`
		    LEFT JOIN
		    	`players` `p1`
		    ON
		    	`p1`.`id` = `c1`.`caller_id`
		    INNER JOIN
		    	`players` `p2`
		    ON
		    	`p2`.`id` = `c1`.`callee_id`
		    WHERE
		    	`c1`.`request_id` = :request_id
		    ORDER BY
		    	`c1`.`id` DESC;
	    	");
	    $sth->bindValue(':request_id', $request['id'], PDO::PARAM_INT);
	    $sth->execute();
	    
	    $request['callstack']	= $sth->fetchAll(PDO::FETCH_ASSOC);
	    
	    // The data input will never change. Therefore,
	    // I arrange all the values manually.
	    
	    if($request['callstack'][0]['caller'] !== NULL)
	    {
		    throw new DataException('Data order does not follow the suit. Mother entry is expected to be the first in the callstack.');
	    }
	    
	    $request['total']		= array
	    (
	    	'ct'	=> 0,
	    	'wt'	=> $request['callstack'][0]['wt'],
	    	'cpu'	=> $request['callstack'][0]['cpu'],
	    	'mu'	=> $request['callstack'][0]['mu'],
	    	'pmu'	=> $request['callstack'][0]['pmu']
	    );
	    
	    // Format the data.
	    $request['callstack']	= array_map(function($e) use (&$request)
	    {
	    	$request['total']['ct']		+= $e['ct'];
	    
	    	return array
	    	(
	    		'caller_id'	=> $e['caller_id'],
	    		'caller'	=> $e['caller'],
	    		'callee_id'	=> $e['callee_id'],
	    		'callee'	=> $e['callee'],
	    		'metrics'	=> array
	    		(
	    			'ct'	=> $e['ct'],
	    			'wt'	=> $e['wt'],
	    			'cpu'	=> $e['cpu'],
	    			'mu'	=> $e['mu'],
	    			'pmu'	=> $e['pmu']
	    		)
	    	);
	    }, $request['callstack']);
	    
	    return $request;
    }
    
	/**
	 * @param	array	$xhprof_data	The raw XHProf data.
	 */
	public function save(array $xhprof_data)
	{
		if(!isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']))
		{
			throw new DataException('XHProf.io cannot function in a server environment that does not define REQUEST_METHOD, HTTP_HOST or REQUEST_URI.');
		}
		
		$sth	= $this->db->prepare("
			(SELECT 'method_id', `id` FROM `request_methods` WHERE `method` = :method LIMIT 1)
			UNION ALL
			(SELECT 'host_id', `id` FROM `request_hosts` WHERE `host` = :host LIMIT 1)
			UNION ALL
			(SELECT 'uri_id', `id` FROM `request_uris` WHERE `uri` = :uri LIMIT 1);");
		
		$sth->execute(array('method' => $_SERVER['REQUEST_METHOD'], 'host' => $_SERVER['HTTP_HOST'], 'uri' => $_SERVER['REQUEST_URI']));
		
		$request	= $sth->fetchAll(PDO::FETCH_KEY_PAIR);

		if(!isset($request['method_id']))
		{
			$this->db
				->prepare("INSERT INTO `request_methods` SET `method` = :method;")
				->execute(array('method' => $_SERVER['REQUEST_METHOD']));
		
			$request['method_id']	= $this->db->lastInsertId();
		}
		
		if(!isset($request['host_id']))
		{
			$this->db
				->prepare("INSERT INTO `request_hosts` SET `host` = :host;")
				->execute(array('host' => $_SERVER['HTTP_HOST']));
		
			$request['host_id']		= $this->db->lastInsertId();
		}
		
		if(!isset($request['uri_id']))
		{
			$this->db
				->prepare("INSERT INTO `request_uris` SET `uri` = :uri;")
				->execute(array('uri' => $_SERVER['REQUEST_URI']));
		
			$request['uri_id']		= $this->db->lastInsertId();
		}
		
		$sth	= $this->db->prepare("INSERT INTO `requests` SET `request_host_id` = :request_host_id, `request_uri_id` = :request_uri_id, `request_method_id` = :request_method_id, `https` = :https;");
		
		$sth->bindValue(':request_host_id', $request['host_id'], PDO::PARAM_INT);
		$sth->bindValue(':request_uri_id', $request['uri_id'], PDO::PARAM_INT);
		$sth->bindValue(':request_method_id', $request['method_id'], PDO::PARAM_INT);
		$sth->bindValue(':https', empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off' ? 0 : 1, PDO::PARAM_INT);
		
		$sth->execute();
		
		$request_id	= $this->db->lastInsertId();
		
		$sth1		= $this->db->prepare("INSERT INTO `calls` SET `request_id` = :request_id, `ct` = :ct, `wt` = :wt, `cpu` = :cpu, `mu` = :mu, `pmu` = :pmu, `caller_id` = :caller_id, `callee_id` = :callee_id;");
		$sth2		= $this->db->prepare("SELECT `id` FROM `players` WHERE `name` = :name LIMIT 1;");
		$sth3		= $this->db->prepare("INSERT INTO `players` SET `name` = :name;");
		
		foreach($xhprof_data as $call => $data)
		{
			$sth1->bindValue(':request_id', $request_id, PDO::PARAM_INT);
			$sth1->bindValue(':ct', $data['ct'], PDO::PARAM_INT);
			$sth1->bindValue(':wt', $data['wt'], PDO::PARAM_INT);
			$sth1->bindValue(':cpu', $data['cpu'], PDO::PARAM_INT);
			$sth1->bindValue(':mu', $data['mu'], PDO::PARAM_INT);
			$sth1->bindValue(':pmu', $data['pmu'], PDO::PARAM_INT);
			
			$call	= explode('==>', $call);
						
			if(count($call) == 1)
			{
			    // callee
				$sth2->execute(array('name' => $call[0]));
			    
			    $callee_id		= $sth2->fetch(PDO::FETCH_COLUMN);
			    
			    $sth2->closeCursor();
			    
			    if(!$callee_id)
			    {
				    $sth3->execute(array('name' => $call[0]));
				    
				    $callee_id	= $this->db->lastInsertId();
			    }
			    
			    $sth1->bindValue(':caller_id', NULL, PDO::PARAM_NULL);
				$sth1->bindValue(':callee_id', $callee_id);
			}
			else
			{
				// caller
				$sth2->execute(array('name' => $call[0]));
			    
			    $caller_id		= $sth2->fetch(PDO::FETCH_COLUMN);
			    
			    $sth2->closeCursor();
			    
			    if(!$caller_id)
			    {
				    $sth3->execute(array('name' => $call[0]));
				    
				    $caller_id	= $this->db->lastInsertId();
			    }
			    
			    // callee
				$sth2->execute(array('name' => $call[1]));
			    
			    $callee_id		= $sth2->fetch(PDO::FETCH_COLUMN);
			    
			    $sth2->closeCursor();
			    
			    if(!$callee_id)
			    {
				    $sth3->execute(array('name' => $call[1]));
				    
				    $callee_id	= $this->db->lastInsertId();
			    }
			    
			
				$sth1->bindValue(':caller_id', $caller_id, PDO::PARAM_INT);
				$sth1->bindValue(':callee_id', $callee_id, PDO::PARAM_INT);
			}
			
			$sth1->execute();
			
			if(count($call) == 1)
		    {
		    	$call_id	= $this->db->lastInsertId();
		    
			    $this->db
			    	->prepare("UPDATE `requests` SET `request_caller_id` = :request_caller_id WHERE `id` = :request_id;")
			    	->execute(array('request_caller_id' => $call_id, 'request_id' => $request_id));
		    }
		}
		
		return $request_id;
	}
	
	public function getHosts(array $query = NULL)
	{
		$this->aggregateRequestData($query, array('datetime_from', 'datetime_to', 'host', 'host_id'));
	
		$data				= array();
		
		$data['discrete']	= $this->db->query("
			SELECT
				`host_id`,
				`host`,
				
				COUNT(`request_id`) `request_count`,
				
				AVG(`wt`) `wt`,
				AVG(`cpu`) `cpu`,
				AVG(`mu`) `mu`,
				AVG(`pmu`) `pmu`
			FROM
				`temporary_request_data`
			GROUP BY
				`host_id`
			ORDER BY
				`host`;
		")->fetchAll(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	public function getUris(array $query = NULL)
	{
		$this->aggregateRequestData($query);
	
		$data				= array();
		
		$data['discrete']	= $this->db->query("
			SELECT
				`host_id`,
				`host`,
				`uri_id`,
				`uri`,
				
				COUNT(`request_id`) `request_count`,
				
				AVG(`wt`) `wt`,
				AVG(`cpu`) `cpu`,
				AVG(`mu`) `mu`,
				AVG(`pmu`) `pmu`
			FROM
				`temporary_request_data`
			GROUP BY
				`uri_id`
			ORDER BY
				`host`;
		")->fetchAll(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	public function getRequests(array $query = NULL)
	{
		$this->aggregateRequestData($query);
	
		$data				= array();		
		
		$data['discrete']	= $this->db->query("SELECT * FROM `temporary_request_data`;")->fetchAll(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	public function getMetricsSummary()
	{
		$data	= $this->db->query("
			SELECT
				COUNT(`request_id`),
				
				MIN(`wt`),
				MAX(`wt`),
				AVG(`wt`),
				
				MIN(`cpu`),
				MAX(`cpu`),
				AVG(`cpu`),
				
				MIN(`mu`),
				MAX(`mu`),
				AVG(`mu`),
				
				MIN(`pmu`),
				MAX(`pmu`),
				AVG(`pmu`)
			FROM
				`temporary_request_data`;
		")
			->fetch(PDO::FETCH_NUM);
		
		if(!$data)
		{
			throw new DataException('Cannot aggregate non-existing metrics.');
		}
			
		$return	= array
		(
			'request_count'	=> $data[0],
			'wt'			=> array_combine(array('min', 'max', 'avg'), array_slice($data, 1, 3)),
			'cpu'			=> array_combine(array('min', 'max', 'avg'), array_slice($data, 4, 3)),
			'mu'			=> array_combine(array('min', 'max', 'avg'), array_slice($data, 7, 3)),
			'pmu'			=> array_combine(array('min', 'max', 'avg'), array_slice($data, 10, 3))
		);
		
		// I've tried so much more sophisticated approaches to calculate the Nth percentile,
		// though with large datasets that's virtually impossible (+30s). See b40d38b commit.
		
		$percentile_offset	= floor($return['request_count']*.95);
		
		foreach(array('wt', 'cpu', 'mu', 'pmu') as $column)
		{
			// I've excluded median on purpose, because it is relatively costly calculation, arguably of any value.
			$return[$column]['95th']	= $this->db->query("SELECT `{$column}` FROM `temporary_request_data` ORDER BY `{$column}` ASC LIMIT {$percentile_offset}, 1;")->fetch(PDO::FETCH_COLUMN);
			$return[$column]['mode']	= $this->db->query("SELECT `{$column}` FROM `temporary_request_data` GROUP BY `{$column}` ORDER BY COUNT(`{$column}`) DESC LIMIT 1;")->fetch(PDO::FETCH_COLUMN);
		}
		
		return $return;
	}
	
	private function buildQuery(array $query = NULL, array $whitelist = array())
	{
		if(empty($whitelist))
		{
			$whitelist	= array('datetime_from', 'datetime_to', 'host', 'host_id', 'uri', 'uri_id', 'request_id');
		}
	
		$return		= array
		(
			'where'			=> ''
		);
		
		if($query === NULL)
		{
			return $return;
		}
		
		$whitelist	= array_merge($whitelist, array('dataset_size'));
		
		if(count(array_diff_key($query, array_flip($whitelist))))
		{
			throw new DataException('Not supported filter parameters cannot be present in the query.');
		}
		
		// build WHERE query
		if(isset($query['request_id']))
		{
			$return['where']	.= ' AND `r1`.`id` = :request_id ';
		}
		
		if(isset($query['uri'], $query['uri_id']))
		{
			throw new DataException('Numerical index overwrites the string matching. Unset either to prevent unexpected results.');
		}
		else if(isset($query['uri']))
		{
			$return['where']	.= ' AND `ru1`.`uri` LIKE :uri ';
		}
		else if(isset($query['uri_id']))
		{
			$return['where']	.= ' AND `r1`.`request_uri_id` = :uri_id ';
		}
		
		if(isset($query['host'], $query['host_id']))
		{
			throw new DataException('Numerical index overwrites the string matching. Unset either to prevent unexpected results.');
		}
		else if(isset($query['host']))
		{
			$return['where']	.= ' AND `rh1`.`host` LIKE :host ';
		}
		else if(isset($query['host_id']))
		{
			$return['where']	.= ' AND `r1`.`request_host_id` = :host_id ';
		}
		
		if(isset($query['datetime_from']))
		{
			$return['where']	.= ' AND `r1`.`request_timestamp` > :datetime_from ';
		}
		
		if(isset($query['datetime_to']))
		{
			$return['where']	.= ' AND `r1`.`request_timestamp` < :datetime_to ';
		}
		
		return $return;
	}
	
	/**
	 * This method creates a temporary table (ENGINE=InnoDB|MEMORY). The table is populated
	 * with the data necessary to analyze requests matching the query.
	 * 
	 * @param	array	$query User-input used to generate the query WHERE and LIMIT clause.
	 * @param	array	$whitelist	Is required whenever any of the filters (currently, self::getHosts only) does not support either of the standard $query parameters.
	 * @todo add BTREE indexes
	 */
	private function aggregateRequestData($query, array $whitelist = array())
	{
		$query['dataset_size']	= empty($query['dataset_size']) ? 1000 : intval($query['dataset_size']);
		
		$sql_query				= $this->buildQuery($query, $whitelist);
		
		$data_query = "
			SELECT
				r1.id request_id,
				UNIX_TIMESTAMP(r1.request_timestamp) request_timestamp,
				
				rh1.id host_id,
				rh1.host,
				
				ru1.id uri_id,
				ru1.uri,
				
				rm1.method request_method,
				
				c1.wt,
				c1.cpu,
				c1.mu,
				c1.pmu
			FROM
				requests r1
			INNER JOIN
				request_hosts rh1
			ON
				rh1.id = r1.request_host_id
			INNER JOIN
				request_uris ru1
			ON
				ru1.id = r1.request_uri_id
			INNER JOIN
				request_methods rm1
			ON
				rm1.id = r1.request_method_id
			INNER JOIN
				calls c1
			ON 
				c1.id = r1.request_caller_id
			WHERE
				1=1 {$sql_query['where']}
			LIMIT
				:dataset_size
		";
		
		#header('Content-Type: text/plain'); die(var_dump($data_query));
		#header('Content-Type: text/plain'); $sth = $this->db->prepare($data_query); $sth->execute(['dataset_size' => 1000]); die(var_dump($sth->fetchAll(PDO::FETCH_ASSOC)));
		
		$sth = $this->db->prepare("CREATE TEMPORARY TABLE `temporary_request_data` ENGINE=InnoDB AS ({$data_query});");
		
		$sth->execute($query);
	}
}

class DataException extends \Exception {}