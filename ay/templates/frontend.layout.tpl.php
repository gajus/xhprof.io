<html>
<head>
	<link rel="stylesheet" href="public/css/frontend.css" type="text/css" charset="utf-8">
	
	<script type="text/javascript" src="public/js/jquery-1.8.2.min.js"></script>
	
	<!--<script type="text/javascript" src="public/js/jquery.ay-json-to-table.js"></script>-->
	<script type="text/javascript" src="public/js/jquery.ay-table-sort.js"></script>
	<script type="text/javascript" src="public/js/jquery.ay-table-sticky.js"></script>
	
	<!--<script type="text/javascript" src="public/js/d3.v2.min.js"></script>
	<script type="text/javascript" src="public/js/crossfilter.v1.min.js"></script>-->
	
	<script type="text/javascript" src="public/js/frontend.js"></script>
	
	<title>XHProf.io</title>
</head>
<body>
	<?php require __DIR__ . '/header.inc.tpl.php';?>
	
	<?=ay_display_messages()?>
	
	<?=$template['body']?>
	
	<?php require __DIR__ . '/footer.inc.tpl.php';?>
</body>
</html>