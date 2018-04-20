<?php

// ip_decimal is the ona/mysql decimal(39,0) value.
//  $format is the format the ip address will be returned in:
//  //    1 or numeric:  170666057
//  //    2 or dotted:   10.44.40.73
//
function inet_format($ip_decimal)
{

	// function ip_mangle($ip="", $format="default")
	$ip = ip_mangle ( $ip_decimal, 2); // dotted 
	return inet_pton($ip);

}

?>
