<?php

namespace Utils\S;

// * Put <array>Array of <array>Lines into CSV file
function to_csv_file($filename, array $arr, $delim=",", $enclose='"')
{
	$fp = \fopen($filename, "w");
	to_csv_file($fp, $arr, $delim, $enclose);
	\fclose($fp);
	return true;
}

function to_csv_filefp($fp,array $arr, $delim=",", $enclose='"')
{
	foreach ( $arr as $key=>$value )
	{
		$str = "";
		if ( !\is_array($value) )
			$va = array($value);
		else
			$va = $value;

		$str = to_csv_string($arr, $delim, $enclose);

		$str .= "\n";
		\fputs($fp, $str);
	}
	return true;
}

// * Makes CSV string from array
function to_csv_string(array $values, $delim=",", $enclose='"')
{
	$str = "";

	foreach ( $values as $n=>$item )
	{
		$str .= $n ? $delim : "";

		if ( !\is_scalar($item) )
			throw new \Exception("Cannot convert non scalar value to csv item");

		if ( !\is_numeric($item) )
		{
			$str .= $enclose . \addslashes($item) . $enclose;
		}
		else
		{
			$str .= $item;
		}
	}

	return $str;
}