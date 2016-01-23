<?php

namespace Utils\A;

// * Function trims each row and removes empty
// Does not affect on non-scalar values
function trim(array &$array)
{
	if ( empty($array) )
		return false;

	\array_walk($array,function(&$value)
	{
		if ( !\is_scalar($value) )
			return;

		$value = \trim($value);
	});

	$array = \array_filter($array, function($value)
	{
		if ( !\is_scalar($value) )
			return true;
		return $value !== "";
	});

	return true;
}

// * Returns first non null argument
// # value or <null> if fail
function coalesce()
{
	$arr = \func_get_args();
	return coalescea($arr);
}

// * Returns first non null item
// # value or <null> if fail
function coalescea(array $arr)
{
	foreach ( $arr as $v )
	{
		if ( !\is_null($v) )
			return $v;
	}
	return null;
}

// * Returns first non null or non false argument
// # key or <null> if fail
function coalescenf()
{
	$arr = \func_get_args();
	return coalescea_nf($arr);
}

// * Return first non null or non false array element
// # key or <null> if fail
function coalescea_nf(array $arr)
{
	foreach ( $arr as $val )
	{
		if ( !\is_null($val) || $val !== false )
		{
			return $val;
		}
	}
	return null;
}

// * Returns key which value is not null
// # key or <null> if fail
function coalesce_key(array $arr)
{
	foreach ( $arr as $k=>$arg )
	{
		if ( !\is_null($arg) )
			return $k;
	}
	return null;
}

// * Returns key which value is not null or false
// # key or <null> if fail
function coalescea_key_nf(array $arr)
{
	foreach ( $arr as $k=>$arg )
	{
		if ( !\is_null($arg) && $arg !== false )
			return $k;
	}
	return null;
}
