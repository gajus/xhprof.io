<?php
function a()
{
	b();
}

function b()
{
	c();
	d();
}

function c()
{
	for($i = 0; $i < 10; $i++)
	{
		d();
	}
}

function d()
{
	
}

a();
d();