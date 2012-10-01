<?php
#return;

if(!in_array($_SERVER['HTTP_HOST'], ['xhprof.io', 'sinonimai.lt']))
{
	#return;
}

if(empty($_GET['test']))
{
	return;
}

xhprof_enable(XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_CPU);