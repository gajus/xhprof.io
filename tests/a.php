<?php
#require __DIR__ . '/../../temp6/external/header.php';

$test	= [];

$size	= 1*1000*1000;

for($i = 0; $i < $size; $i++)
{
	$test[]	= mt_rand(0,100000);
}

echo array_sum($test)/$size;