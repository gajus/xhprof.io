<?php
return array(
	'url_base' => 'https://dev.anuary.com/8d50658e-f8e0-5832-9e82-6b9e8aa940ac/',
	'url_static' => null, // When undefined, it defaults to $config['url_base'] . 'public/'. This should be absolute URL.
	'pdo' => new PDO('mysql:dbname=your_database_name;host=localhost;charset=utf8', 'username', 'password'),
);