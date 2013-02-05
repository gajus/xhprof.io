<?php
namespace ay\xhprof;
?>
<html>
<head>
	<link rel="stylesheet" href="<?=$config['url_static']?>css/frontend.css" type="text/css" charset="utf-8">
	
	<script type="text/javascript" src="<?=$config['url_static']?>js/jquery-1.9.0.min.js"></script>
	
	<script type="text/javascript" src="<?=$config['url_static']?>js/jquery.ay-toggle-element.js"></script>
	<script type="text/javascript" src="<?=$config['url_static']?>js/jquery.ay-table-sort.js"></script>
	<script type="text/javascript" src="<?=$config['url_static']?>js/jquery.ay-table-sticky.js"></script>
	
	<script type="text/javascript" src="<?=$config['url_static']?>js/d3.v2.js"></script>
	<script type="text/javascript" src="<?=$config['url_static']?>js/crossfilter.v1.js"></script>
	<script type="text/javascript" src="<?=$config['url_static']?>js/d3.crossfilter.ay-histogram.js"></script>
	<script type="text/javascript" src="<?=$config['url_static']?>js/d3.ay-pie-chart.js"></script>
	
	<script type="text/javascript" src="<?=$config['url_static']?>js/frontend.js"></script>
	
	<title>XHProf.io</title>
</head>
<body class="template-<?=$template['file']?>">
	<?php require __DIR__ . '/header.inc.tpl.php';?>
	
	<?=\ay\display_messages()?>
	
	<?=$template['body']?>
	
	<?php require __DIR__ . '/footer.inc.tpl.php';?>
</body>
</html>