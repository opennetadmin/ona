<?php
// DON'T put whitespace at the beginning or end of this file!!!


// Debugging: lets print what's in $_REQUEST
printmsg("Get/Post vars:", 3);
foreach (array_keys($_REQUEST) as $key) printmsg("Name: $key    Value: $_REQUEST[$key]", 3);

// MP: moved this stuff to config.inc.php

// Include the basic database functions
//require_once($conf['inc_functions_db']);

// (Re)Connect to the DB now.
//global $onadb;
//$onadb = db_pconnect('mysqlt', $conf['mysql_context']);

// Include functions that replace the default session handler with one that uses MySQL as a backend
//require_once($conf['inc_db_sessions']);

// Include the GUI functions
//require_once($conf['inc_functions_gui']);

// Start the session handler (this calls a function defined below in this file)
//startSession();


// Include the GUI functions
//require_once($base.'/include/functions_auth.inc.php');



/***************/
/*  Functions  */
/***************/

///////////////////////////////////////////////////////////////////////
//  Function: printmsg (string $msg, int $level)
//
//   $msg   = Message you would like to display
//   $level = debug level that has to be reached before this message would be displayed.
//
//   Prints a message if $level is less than or equal to global debug value
//
///////////////////////////////////////////////////////////////////////
function printmsg($msg="",$debugLevel=0) {
    global $conf, $self;

    if ($debugLevel <= $conf['debug'] and isset($msg)) {

        // Get a username or "anonymous"
        if (isset($_SESSION['ona']['auth']['user']['username']))
            $username = $_SESSION['ona']['auth']['user']['username'];
        else
            $username = "anonymous";

        // Print to a log file if needed
        if ($conf['logfile'])
            ona_logmsg($msg);

        // log level 0 entries to database table
        if ($conf['log_to_db'] and $debugLevel == 0) {
            global $onadb;
            // MP TODO: log using tia64n
            list($status, $rows) = db_insert_record($onadb, 'ona_logs', array('username' => $username, 'remote_addr' => $_SERVER['REMOTE_ADDR'], 'message' => $msg,'context_name' => $self['context_name']));
        }

        // Print to syslogd if needed
        if ($conf['syslog']) {
            // MP: fix this up so it uses openlog and allows the user to set the facility?
            syslog(LOG_INFO, "ONA {$username}@{$_SERVER['REMOTE_ADDR']}: [{$self['context_name']}] $msg");
        }

        // Print to stdout (i.e. the web page) if needed
        if ($conf['stdout']) {
            if ($self['nohtml'] == 1)
                echo $msg . "\n";
            else {
                $msg = htmlentities($msg, ENT_QUOTES, $conf['php_charset']);
                echo "<b>[$debugLevel]</b>:<font style=\"
                    font-size:12px;
                    color:crimson;
                    background-color:yellow;
                    \">
                                    $msg
                </font><b></b><br>\n";
            }
        }
    }
}









///////////////////////////////////////////////////////////////////////
//  Function: logmsg(string $message, string $logfile)
//
//   Write $message into $logfile.
//   $logfile is optional and will use a default specified below.
//
//   $message  = the message to be logged to disk
//   $logfile  = the file we should log it to
//
//
///////////////////////////////////////////////////////////////////////
function ona_logmsg($message, $logfile="") {
    global $conf, $self;

    // Do a little input validation
    if (!isset($message) or $message == "") {
        return("Can't log a blank line you idiot!");
    }

    // Get logfile from $conf if it wasn't specified
    if (!$logfile) {
        $logfile = $conf['logfile'];
    }

    // Open the file
    $file = fopen($logfile, "a+");
    if (!$file) {
        return(1);
    }

    // Get the hostname (and a few other things we don't use)
    // After this we can reference $uname['nodename'] which will have our hostname
    $uname['nodename'] = "UNKNOWN_SVR_NAME";
    if (function_exists('posix_uname')) {
        $uname = posix_uname();
    }

    // Get a username or "anonymous"
    if (isset($_SESSION['ona']['auth']['user']['username'])) {
        $username = $_SESSION['ona']['auth']['user']['username'];
    }
    else {
        $username = "anonymous";
    }

    // Build the exact line we want to write to the file
    $logdata = date("M j G:i:s ") . "{$uname['nodename']} {$username}@{$_SERVER['REMOTE_ADDR']}: [{$self['context_name']}] {$message}\n";

    // Write the line to the file
    if (!fwrite($file, $logdata)) {
        return(1);
    }

    // Close the file
    fclose($file);

    // Return 0 for no errors, or >0 if there was any errors
    return(0);

}












///////////////////////////////////////////////////////////////////////
//  Function: strsize (string $string)
//
//  Returns a nicely formatted string of the size of the string $string
//
///////////////////////////////////////////////////////////////////////
function strsize($string) {
    $tmp = array("B", "KB", "MB", "GB", "TB", "PB");

    $pos = 0;
    $size = strlen($string);
    while ($size >= 1024) {
            $size /= 1024;
            $pos++;
    }

    return round($size,2)." ".$tmp[$pos];
}








///////////////////////////////////////////////////////////////////////
//  Function: truncate (string $msg, int length)
//
//   $msg    = Message you would like to (possibly) truncate
//   $length = Max length you want $msg to be
//
//   Returns $msg with a maximum length of $length.
//
///////////////////////////////////////////////////////////////////////
function truncate($msg="",$length=0) {
    global $conf;
    if ($length > 0)
        $msg = (mb_strlen($msg) < $length) ? $msg : mb_substr($msg,0,$length - 3) . "...";

    return($msg);
}








///////////////////////////////////////////////////////////////////////
//  Function: fix_input(string $in)
//
//  Basically does a "strip_slashes()" if magic quotes are turned on.
//  Returns the fixed input.
///////////////////////////////////////////////////////////////////////
function fix_input($string) {
    // Stripslashes from $_REQUEST input if magic_quotes is enabled -
    // we quote everything properly in this code :)
    if (get_magic_quotes_gpc())
        $string = stripslashes($string);

    return($string);
}








/**
 * convert line ending to unix format
 *
 * @see    formText() for 2crlf conversion
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function cleanText($text){
  $text = preg_replace("/(\015\012)|(\015)/","\012",$text);
  return $text;
}








/**
 * Simple function to replicate PHP 5 behaviour of microtime()
 */
function microtime_float() {
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}









///////////////////////////////////////////////////////////////////////
//  Function: ip_mangle($ip, [$format])
//
//  $input is the ip address in either numeric or dotted format
//  $format is the format the ip address will be returned in:
//    1 or numeric:  170666057
//    2 or dotted:   10.44.40.73
//    3 or cidr:     24 (or /24) (for netmasks only)
//    4 or binary:   1010101010101010101010101010101010101010
//
//  Options 5,6,7 are only supported by GMP module
//    5 or bin128:   1010101010101010101010101010101010101010... (128 bits)
//    6 or ipv6:     FE80:0000:0000:0000:0202:B3FF:FE1E:8329
//    7 or ipv6gz:   FE80::202:B3FF:FE1E:8329 or
//                   ::C000:280 or
//                   ::ffff:C000:280
//
//
//
//    8 or flip:     10.1.2.3 changes to 3.2.1.10
//
//
//  Wrapper around the two versions of ip_mangle.  one with GMP one without.
//  The non GMP version is not ipv6 compatible
//
//  Example:
//      print "IP is: " . ip_mangle(170666057)
///////////////////////////////////////////////////////////////////////
function ip_mangle($ip="", $format="default") {
    global $self;


    if (function_exists('gmp_init')) {
        return(ip_mangle_gmp($ip, $format));
    }
    else {
        printmsg("INFO => Falling back to non GMP enabled ip_mangle function",5);
        return(ip_mangle_no_gmp($ip, $format));
    }
}











///////////////////////////////////////////////////////////////////////
//  Function: ip_mangle($ip, [$format])
//
//  $input is the ip address in either numeric or dotted format
//  $format is the format the ip address will be returned in:
//    1 or numeric:  170666057
//    2 or dotted:   10.44.40.73
//    3 or cidr:     24 (or /24) (for netmasks only)
//    4 or binary:   1010101010101010101010101010101010101010
//    8 or flip:     10.1.2.3 changes to 3.2.1.10
//
//  Formats the input IP address into the format specified.  When a
//  format is not specified dotted format is returned unless you
//  supply a dotted input, in which case numeric format (1) is returned.
//  Returns -1 on any error and stores a message in $self['error']
//
//  Example:
//      print "IP is: " . ip_mangle(170666057)
///////////////////////////////////////////////////////////////////////
function ip_mangle_no_gmp($ip="", $format="default") {
    global $self;

    // Is input in dotted format (2)?
    if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $ip)) {
        $ip = ip2long($ip);
        if ($format == "default") { $format = 1; }
    }

    // Is it in CIDR format (3)?
    else if (preg_match('/^\/?(\d{1,2})$/', $ip, $matches)) {
        if (!($matches[1] >= 0 && $matches[1] <= 32)) {
            $self['error'] = "ERROR => Invalid CIDR mask";
            return(-1);
        }
        // So create a binary string of 1's and 0's and convert it to an int
        $ip = bindec(str_pad(str_pad("", $matches[1], "1"), 32, "0"));
        if ($format == "default") { $format = 2; }
    }

    // Is it in binary format (4)?
    else if (preg_match('/^[01]{32}$/', $ip)) {
        $ip = bindec($ip);
        if ($format == "default") { $format = 2; }
    }

    // If it has a non-digit character, it's invalid.
    else if (preg_match('/\D/', $ip)) {
        $ip = -1;
    }

    // Then input must be in numeric format (1)
    else {
        // We flip it to dotted and back again to make sure it's a valid address
        $ip = long2ip($ip);
        $ip = ip2long($ip);
        if ($format == "default") { $format = 2; }
    }


    // If the address wasn't valid return an error
    if ($ip == -1) {
        $self['error'] = "ERROR => Invalid IP address";
        return(-1);
    }


    // Is output format 1 (numeric)?
    if ($format == 1 or $format == 'numeric') {
        return(sprintf("%u", $ip));
    }

    // Is output format 2 (dotted)?
    else if ($format == 2 or $format == 'dotted') {
        return(long2ip($ip));
    }

    // Is output format 3 (CIDR)?
    else if ($format == 3 or $format == 'cidr') {
        // Make sure the address is a valid mask - convert it to 1's and 0's,
        // then make sure it's all 1's followed by all 0's.
        $binary = str_pad(decbin($ip), 32, "0", STR_PAD_LEFT);
        if (!(preg_match('/^1+0*$/', $binary))) {
            $self['error'] = "ERROR => IP address specified is not a valid netmask";
            return(-1);
        }
        // Return the number of 1's at the beginning of the binary representation of $ip
        return(strlen(rtrim($binary,"0")));
    }

    // Is output format 4 (binary string)?
    else if ($format == 4 or $format == 'binary') {
        // Convert the integer to it's 32 bit binary representation
        return(str_pad(decbin($ip), 32, "0", STR_PAD_LEFT));
    }

    // Is output format 8 (flipped IP string)?
    else if ($format == 8 or $format == 'flip') {
        $octet = explode('.',long2ip(sprintf("%s", $ip)));
        return(sprintf("%s.%s.%s.%s",$octet[3],$octet[2],$octet[1],$octet[0]));
    }

    else {
        $self['error'] = "ERROR => ip_mangle() Invalid IP address format specified!";
        return(-1);
    }

}






///////////////////////////////////////////////////////////////////////
//  Function: ip_mangle($ip, [$format])
//
//  $input is the ip address in either numeric or dotted format
//  $format is the format the ip address will be returned in:
//    1 or numeric:  170666057
//    2 or dotted:   10.44.40.73
//    3 or cidr:     24 (or /24) (for netmasks only)
//    4 or binary:   1010101010101010101010101010101010101010 (32 bits)
//    5 or bin128:   1010101010101010101010101010101010101010... (128 bits)
//    6 or ipv6:     FE80:0000:0000:0000:0202:B3FF:FE1E:8329
//    7 or ipv6gz:   FE80::202:B3FF:FE1E:8329 or
//                   ::C000:280 or
//                   ::ffff:C000:280
//    8 or flip:     10.1.2.3 changes to 3.2.1.10
//
//  (currently unsupported for input or output)  0:0:0:0:0:0:192.0.2.128
//  (currently unsupported for input or output)  ::ffff:192.0.2.128
//  (currently unsupported for input or output)  ::192.0.2.128
//
//  Formats the input IP address into the format specified.  When a
//  format is not specified dotted format is returned unless you
//  supply a dotted input, in which case numeric format (1) is returned.
//  Returns -1 on any error and stores a message in $self['error']
//
//  Example:
//      print "IP is: " . ip_mangle(170666057)
///////////////////////////////////////////////////////////////////////
function ip_mangle_gmp($ip="", $format="default") {
    // is_ipv4 returns TRUE if $inp can be represented as an IPv4 address
    //                 FALSE if $inp cannot be represented as an IPv4 address (e.g. IPv6)
    // Note: $inp is a 'gmp' resource, created by 'gmp_init()'.
    if(!function_exists("is_ipv4")) {
        function is_ipv4($inp) {
            if(gmp_cmp(gmp_init("0xffffffff"), $inp) >= 0)
                return TRUE;
            return FALSE;
        }
    }

    // Split a string into an array, each element of length $length characters.
    // This function doesn't exist in PHP < 5.0.0.
    if (!function_exists("str_split")) {
        function str_split($str,$length = 1) {
            if ($length < 1) return false;
            $strlen = strlen($str);
            $ret = array();
            for ($i = 0; $i < $strlen; $i += $length) {
                $ret[] = substr($str,$i,$length);
            }
            return $ret;
        }
    }

    // Converts an ipv6 formatted input string to a GMP resource
    if (!function_exists("ip2gmp6")) {
        function ip2gmp6($ip) {
            // Expand '::' to zero stanzas
            if (substr_count($ip, '::'))
                $ip = str_replace('::',
                    str_repeat(':0000', 8-substr_count($ip, ':')) . ':', $ip);
            $ip = explode(':', $ip) ;
            $r_ip = '';
            // Insert any missing leading zeros in each stanza.
            foreach ($ip as $v)
                   $r_ip .= str_pad($v, 4, 0, STR_PAD_LEFT) ;
            return (gmp_init($r_ip, 16));
        }
    }

    // When given an uncompressed IPv6 address string of the form
    // 0000:1111:2222:3333:4444:5555:6666:7777:8888, this
    // will return a 'compressed' IPv6 address string.  It replaces the
    // longest consecutive sequence of "0000" stanzas with double
    // colons ("::") and strips any leading zeros in each stanza.
    //
    // For example, input string fe80:0000:0000:0000:00ff:0000:a033:05b7
    // will be output as string  fe80::ff:0:a033:5b7.
    if (!function_exists("ipv6gz")) {
        function ipv6gz($ip) {
            $e = explode(':', $ip);
            $e[] = "XXXX";    // add a sentinel value
            $zeros = array("0000");
            $result = array_intersect ($e, $zeros );
            // $result now contains only the non-zero stanzas from $ip
            if (sizeof($result) > 0) {
                // Find the longest sequence of zero stanzas
                $begin = $start = ""; $len = 0;
                foreach($e as $key=>$val) {
                    if($val === "0000") {
                        if($begin === "") {
                            $begin = $key;
                            if ($start === "")
                                $start = $begin;
                        }
                    } else {
                        if($begin !== "") {
                            if($key-$begin > $len) {
                                $len = $key-$begin;
                                $start = $begin;
                            }
                            $begin = "";
                        }
                    }
                }
                array_pop($e);    // remove the sentinel value

                // Replace that sequence with '::', strip leading zeros, etc
                $newip=array();
                foreach($e as $key=>$val) {
                    if($start !== "" && $key > $start && $key < $start+$len) continue;
                    if($start !== "" && $key === $start)
                        $val = '';
                    else
                        $val = base_convert($val, 16, 16);
                    $newip[] = $val;
                }
                // Corner cases: (1) If the final stanza is compressed, add one more
                // empty array element, so we will end with two colons, not just one.
                // (2) If the whole string was zeros, then add another empty element.
                // (3) If the beginning stanza is compressed, prepend an empty array
                // element, _unless_ cases (1) and (2) were both true.
                if($start+$len == 8) { $newip[] = ''; }
                if($len == 8) { $newip[] = ''; }
                if($start == 0 && $len < 8) { array_unshift($newip, ''); }
                $ip = implode(':', $newip);
            }
            return $ip;
        }
    }

    global $self;

    // Is input in IPv4 dotted format (2)?
    if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $ip)) {
        if($ip != long2ip(ip2long($ip))) {
            $self['error'] = "ERROR => Invalid IPv4 address";
            return(-1);
        }
        $ip = gmp_init(sprintf("%u", ip2long($ip)), 10);
        if ($format == "default") { $format = "numeric"; }
    }

    // Is it in IPv4/IPv6 CIDR format (3)?
    else if (preg_match('/^\/?(\d{1,3})$/', $ip, $matches)) {
        if (!($matches[1] >= 0 && $matches[1] <= 128)) {
            $self['error'] = "ERROR => Invalid CIDR mask";
            return(-1);
        }

        // So create a binary string of 1's and 0's and convert it to an int
        if($matches[1] <= 32) { $cidr_bits = 32; }
        else { $cidr_bits = 128; }
        $ip = gmp_init(str_pad(str_pad("", $matches[1], "1"), $cidr_bits, "0"), 2);
        if ($format == "default") {
            // Default to IPv6 output if the input was IPv6, IPv4 otherwise
            if (is_ipv4($ip))
                $format = "dotted";
            else
                $format = "ipv6";
        }
    }

    // Is it in 32-bit binary format (4)?
    else if (preg_match('/^[01]{32}$/', $ip)) {
        //$ip = bindec($ip);
        $ip = gmp_init(strval($ip), 2);
        if ($format == "default") { $format = "dotted"; }
    }

    // Is it in 128-bit binary format (5)?
    else if (preg_match('/^[01]{128}$/', $ip)) {
        $ip = gmp_init(strval($ip), 2);
        if ($format == "default") { $format = "ipv6"; }
    }

    // Is it in ipv6 format (6)?
    // This matches the 'full' uncompressed IPv6 format, the format without
    // leading zeros in each stanza, and also 'compressed' IPv6 format, which
    // substitutes '::' for multiple zero stanzas.
    else if (   (substr_count($ip, '::') == 1 && substr_count($ip, ':::') == 0 &&
                 preg_match('/^:|([0-9A-F]{1,4}:){0,7}:|(:[0-9A-F]{1,4}){0,7}$/i', $ip)) or
             preg_match('/^([0-9A-F]{1,4}:){7}([0-9A-F]{1,4})$/i', $ip)) {
        $ip = ip2gmp6($ip);
        if ($format == "default") { $format = "numeric"; }
    }

    // If at this point, it has a non-digit character, it's invalid.
    else if (preg_match('/\D/', $ip)) {
        $ip = -1;
    }

    // If we get here, then the input must be in numeric format (1)
    else {
        $ip = gmp_init(strval($ip), 10);
        if ($format == "default") {
            if(is_ipv4($ip))
                $format = "dotted";
            else
                $format = "ipv6";
        }
    }


    // If the address wasn't valid return an error --
    // check for out-of-range values (< 0 or > 2**128)
    if (gmp_cmp(gmp_init(-1), $ip) >= 0 or
        gmp_cmp(gmp_pow("2", 128), $ip) <= 0) {
        $self['error'] = "ERROR => Invalid IP address";
        return(-1);
    }


    // Is output format 1 (numeric)?
    if ($format == 1 or $format == 'numeric')
        //return(sprintf("%u", $ip));
        return(sprintf("%s", gmp_strval($ip, 10)));

    // Is output format 2 (dotted)?
    else if ($format == 2 or $format == 'dotted') {
        if(!is_ipv4($ip)) {
            return(ipv6gz(implode(":", str_split(str_pad(gmp_strval($ip, 16), 32, "0", STR_PAD_LEFT), 4))));
           // $self['error'] = "ERROR => Invalid IPv4 address";
           // return(-1);
        }
        return(long2ip(sprintf("%s", gmp_strval($ip))));
    }

    // Is output format 3 (CIDR)?
    else if ($format == 3 or $format == 'cidr') {
        // Make sure the address is a valid mask - convert it to 1's and 0's,
        // then make sure it's all 1's followed by all 0's.
        if(is_ipv4($ip))
            $cidr_bits = 32;
        else
            $cidr_bits = 128;
        $binary = str_pad(gmp_strval($ip, 2), $cidr_bits, "0", STR_PAD_LEFT);
        if (!(preg_match('/^1+0*$/', $binary))) {
            $self['error'] = "ERROR => IP address specified is not a valid netmask";
            return(-1);
        }
        // Return the number of 1's at the beginning of the binary representation of $ip
        return(strlen(rtrim(gmp_strval($ip, 2), "0")));

    }

    // Is output format 4 (32-bit binary string)?
    else if ($format == 4 or $format == 'binary') {
        // Convert the integer to its 32-bit binary representation
        return(str_pad(gmp_strval($ip, 2), 32, "0", STR_PAD_LEFT));
    }

    // Is output format 5 (128-bit binary string)?
    else if ($format == 5 or $format == 'bin128') {
        // Convert the number to its 128-bit binary representation
        return(str_pad(gmp_strval($ip, 2), 128, "0", STR_PAD_LEFT));
    }

    // Is output format 6 (uncompressed IPv6 string)?
    else if ($format == 6 or $format == 'ipv6') {
        // Convert the number to 8x 16-bit hexidecimal stanzas.
        return(implode(":", str_split(str_pad(gmp_strval($ip, 16), 32, "0", STR_PAD_LEFT), 4)));
    }

    // Is output format 7 (compressed IPv6 string)?
    else if ($format == 7 or $format == 'ipv6gz') {
        return(ipv6gz(implode(":", str_split(str_pad(gmp_strval($ip, 16), 32, "0", STR_PAD_LEFT), 4))));
    }

    // Is output format 8 (flipped IP string)?
    else if ($format == 8 or $format == 'flip') {
        if(!is_ipv4($ip)) {
            // Turn it into a ip6.arpa PTR record format with nibbles and all!
            return(strrev(implode(".", str_split(str_pad(gmp_strval($ip, 16), 32, "0", STR_PAD_LEFT), 1))));
            //$self['error'] = "ERROR => Invalid IPv4 address";
            //return(-1);
        }
        $octet = explode('.',long2ip(sprintf("%s", gmp_strval($ip))));
        return(sprintf("%s.%s.%s.%s",$octet[3],$octet[2],$octet[1],$octet[0]));
    }

    else {
        $self['error'] = "ERROR => ip_mangle() Invalid IP address format specified!";
        return(-1);
    }
}









///////////////////////////////////////////////////////////////////////
//  Function: ipcalc_info($ip,$mask)
//
//  Gathers various bits of IP info for use in an ipcalc tools/modules
//  Returns an array of those bits of info.
//
//  Example:
//      $level = ipcalc_info($ip,$mask)
///////////////////////////////////////////////////////////////////////
function ipcalc_info($ip='', $mask='') {
    global $conf, $self;


// MP: fix the fact that I"m not testing for the GMP module.. it will fail for ipv6 stuff
    $retarray = array();

    $retarray['in_ip'] = $ip;
    $retarray['in_mask'] = $mask;
    $retarray['mask_cidr'] = ip_mangle($retarray['in_mask'], 'cidr');

    // Process the IP address
    $retarray['ip_dotted'] = ip_mangle($retarray['in_ip'], dotted);
    $retarray['ip_numeric'] = ip_mangle($retarray['in_ip'], numeric);
    $retarray['ip_binary'] = ip_mangle($retarray['in_ip'], binary);
    $retarray['ip_bin128'] = ip_mangle($retarray['in_ip'], bin128);
    $retarray['ip_ipv6'] = ip_mangle($retarray['in_ip'], ipv6);
    $retarray['ip_ipv6gz'] = ip_mangle($retarray['in_ip'], ipv6gz);
    $retarray['ip_flip'] = ip_mangle($retarray['in_ip'], flip);

    // Process the mask
    $retarray['mask_dotted'] = ip_mangle($retarray['in_mask'], dotted);
    $retarray['mask_numeric'] = ip_mangle($retarray['in_mask'], numeric);
    $retarray['mask_binary'] = ip_mangle($retarray['in_mask'], binary);
    $retarray['mask_bin128'] = ip_mangle($retarray['in_mask'], bin128);
    $retarray['mask_ipv6'] = ip_mangle($retarray['in_mask'], ipv6);
    $retarray['mask_ipv6gz'] = ip_mangle($retarray['in_mask'], ipv6gz);
    $retarray['mask_flip'] = ip_mangle($retarray['in_mask'], flip);


    // Invert the binary mask
    $inverted = str_replace("0", "x", ip_mangle($retarray['in_mask'], binary));
    $inverted = str_replace("1", "0", $inverted);
    $inverted = str_replace("x", "1", $inverted);
    $retarray['mask_bin_invert'] = $inverted;
    $retarray['mask_dotted_invert'] = ip_mangle($inverted, dotted);


    // Check boundaries
    // This section checks that the IP address and mask are valid together.
    // if the IP address does not fall on a proper boundary based on the provided mask
    // we will return the 'truenet' that it would fall into.
    $retarray['netboundary'] = 1;
    $ip1 = $retarray['ip_binary'];
    $ip2 = str_pad(substr($ip1, 0, $retarray['mask_cidr']), 32, '0');
    $ip1 = ip_mangle($ip1, 'dotted');
    $ip2 = ip_mangle($ip2, 'dotted');
    $retarray['truenet'] = $ip2; // this is the subnet IP that your IP would fall in given the mask provided.
    if ($ip1 != $ip2)
        $retarray['netboundary'] = 0;  // this means the IP passed in is NOT on a network boundary

    // Get IP address counts
    $total = (0xffffffff - ip_mangle($retarray['in_mask'], 'numeric')) + 1;
    $usable = $total - 2;
    $lastip = ip_mangle($ip2, numeric) - 1 + $total;

    $retarray['ip_total'] = $total;
    $retarray['ip_usable'] = $usable;
    $retarray['ip_last'] = ip_mangle($lastip, dotted);


    return($retarray);
}


















///////////////////////////////////////////////////////////////////////
//  Function: sanitize_security_level($int, $default)
//
//  Takes a string and returns either the same number if it's a valid
//  security level or $default if $int is empty.
//  Will return an error if the level is greater than your current
//  level as defined in the session.
//  Returns -1 on any error and stores a message in $self['error']
//
//  Example:
//      $level = sanitize_security_level($level)
///////////////////////////////////////////////////////////////////////
function sanitize_security_level($string="", $default=-1) {
    global $conf, $self;
    if ($default == -1) $default = $conf['ona_lvl'];
    if ($string == "") return($default);

    // If it's valid, use it..
    if (is_numeric($string) and $string <= 99) {
        // Make sure it's not higher than the user's current level
        if ($string <= $_SESSION['auth']['user']['level'])
            return($string);
        else {
            $self['error'] = "ERROR => Security-level can't be higher than your own level!";
            return(-1);
        }
    }
    else {
        $self['error'] = "ERROR => Invalid security-level specified!";
        return(-1);
    }
}









///////////////////////////////////////////////////////////////////////
//  Function: sanitize_hostname($string)
//
//  Takes a string and returns either the same string if it's a valid
//  hostname or FALSE if not.
//  Returns FALSE on any error and stores a message in $self['error']
//
//  Example:
//      $hostname = sanitize_hostname('hostname');
//      if ($hostname) { do($something); }
///////////////////////////////////////////////////////////////////////
function sanitize_hostname($string="") {
    global $self;
    if ($string == "") { return(false); }

    // The rules for hostnames:
    //  * Must start and end with an alphanumeric character
    //  * Must consist of the valid character set: a-z0-9.-_
    //  * Can not have more than one consecutive period
    //  * Length must range between 1 and 63 characters

    // lets test out if it has a / in it to strip the view name portion
    if (strstr($string,'/')) {
        list($dnsview,$string) = explode('/', $string);
    }

    // We lower case all dns names
    $string = strtolower($string);

    // If it is a wildcard, let it through
    if ($string == "*") { return($string); }

    // If it's valid, use it..
    if (preg_match('/^([a-z0-9_\*]([a-z0-9_\.\-]*))?[a-z0-9]$/', $string)) {
        // Make sure it doesn't have more than one "." in a row
        if (stristr($string, '..')) { return(false); }
        // The syntax is ok, make sure it's not too long
        if (strlen($string) > 63) { return(false); }
        // It's ok!  Return it.
        return($string);
    }
    $self['error'] = "ERROR => Invalid hostname!";
    return(false);
}














///////////////////////////////////////////////////////////////////////
//  Function: mac_mangle($mac_address, [$format])
//
//  $mac_address is a mac address in almost any format
//  $format is the format the mac address will be returned in:
//    1 = non formatted raw form: A9B1CCD2392D
//    2 = typical format:         A9:B1:CC:D2:39:2D
//    3 = cisco format:           A9B1.CCD2.392D
//
//  Formats the input MAC address into the format specified.  When a
//  format is not specified, and input is in format 2 or 3, format 1
//  is returned -- if input is in format 1, format 2 is returned.
//  Returns -1 on any error and stores a message in $self['error']
//
//  Example:
//      print "MAC is: " . mac_mangle('A9B1CCD2392D')
///////////////////////////////////////////////////////////////////////
function mac_mangle($input="", $format="default") {
    global $self;

    // Make sure we got input
    if (!$input) { $self['error'] = "ERROR => MAC address was null"; return(-1); }

    $matches = array();

    // Is input in raw format? (1)
    if (preg_match('/^([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})$/', $input, $matches)) {
        if ($format == "default") { $format = 2; }
    }

    // Is input in typical format? (2)
    else if (preg_match('/^([A-Fa-f0-9]{2}).([A-Fa-f0-9]{2}).([A-Fa-f0-9]{2}).([A-Fa-f0-9]{2}).([A-Fa-f0-9]{2}).([A-Fa-f0-9]{2})$/', $input, $matches)) {
        if ($format == "default") { $format = 1; }
    }

    // Is input in cisco format? (3)
    else if (preg_match('/^([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})\.([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})\.([A-Fa-f0-9]{2})([A-Fa-f0-9]{2})$/', $input, $matches)) {
        if ($format == "default") { $format = 1; }
    }

    else {
        $self['error'] = "ERROR => Invalid MAC address";
        return(-1);
    }

    // Output in format 1 (raw)?
    if ($format == 1) {
        return(strtoupper($matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5] . $matches[6]));
    }

    // Output in format 2 (typical)?
    else if ($format == 2) {
        return(strtoupper($matches[1] . ':' . $matches[2] . ':' . $matches[3] . ':' . $matches[4] . ':' . $matches[5] . ':' . $matches[6]));
    }

    // Output in format 2 (cisco)?
    else if ($format == 3) {
        return(strtoupper($matches[1] . $matches[2] . '.' . $matches[3] . $matches[4] . '.' . $matches[5] . $matches[6]));
    }

    else {
        $self['error'] = "ERROR => mac_mangle() Invalid MAC format specified!";
        return(-1);
    }

}












///////////////////////////////////////////////////////////////////////
//  Function: string ip_complete($ip, [$filler=0])
//
//  Completes a partial ip address with $filler and returns it.
//  Returns -1 if the input string doesn't at least match /\d+\./
//
//  $input is a partial or complete ip address in dotted format.
//  $filler is the number to replace incomplete ip pieces with.
//
//  Examples:
//    $string = ip_complete('192.168', '0');
//    $string == '192.168.0.0'
//
//    $string = ip_complete('192.168.', '255');
//    $string == '192.168.255.255'
//
//  Note: If the IP address is invalid -1 is returned.  So if the
//        input string is something like '192.515' -1 will be returned.
///////////////////////////////////////////////////////////////////////
function ip_complete($ip='', $filler=0) {
    global $self;

    // If it looks like ipv6 just return it
// fill out :: with ffff:ffff .. need to figure out how many remaining octets there would be
// :: is already masking it to all 0000:0000 so ipv6 auto does the default method here... may need a : to turn into :: though
    if (strlen($ip) > 11) return($ip);
    if (strpos($ip, ':')) return($ip);

    // Make sure it looks like a partial IP address
    if (!preg_match('/^(\d+)\.(\d+)?\.?(\d+)?\.?(\d+)?$/', $ip, $matches)) { return(-1); }

    // Build $ip with $filler
    $ip = $matches[1];
    if (is_numeric($matches[2])) { $ip .= ".{$matches[2]}"; } else { $ip .= ".{$filler}"; }
    if (is_numeric($matches[3])) { $ip .= ".{$matches[3]}"; } else { $ip .= ".{$filler}"; }
    if (is_numeric($matches[4])) { $ip .= ".{$matches[4]}"; } else { $ip .= ".{$filler}"; }

    return(ip_mangle($ip, 'dotted'));
}









///////////////////////////////////////////////////////////////////////
//  Function: date_mangle(int $timestamp | string $date)
//
//  Returns int $timestamp as a MySQL formatted date string, or
//  Returns string $date (a onadb formatted date) as an int timestamp.
//  Returns -1 on error.
///////////////////////////////////////////////////////////////////////
function date_mangle($time=-1) {
    // Do a little input validation
    if ($time == -1) return(-1);

    if (preg_match('/(\d\d\d\d)\D(\d\d)\D(\d\d)\s+(\d\d)\D(\d\d)\D(\d\d)/', $time, $parts))
        $date = strtotime("{$parts[2]}/{$parts[3]}/{$parts[1]} {$parts[4]}:{$parts[5]}:{$parts[6]}");
    else
        $date = date('Y-m-d H:i:s', $time);

    return($date);
}







///////////////////////////////////////////////////////////////////////
//  Function: tzsecs($tz_offset)
//
//  Returns the int $tz_offset * 60 * 60 .. effectivly converting the
//  timezone offset to seconds.
///////////////////////////////////////////////////////////////////////
function tzsecs($tz_offset=0) {
    return($tz_offset * 60 * 60);
}







///////////////////////////////////////////////////////////////////////
//  Function: gmtime()
//
//  Returns the current UTC time in seconds since 1970.
///////////////////////////////////////////////////////////////////////
function gmtime() {
    $time = time() + (date('Z') * -1);
    return(date('U', $time));
}





///////////////////////////////////////////////////////////////////////
//  Function: validate_email($email_address)
//
//  Returns the original string if it looks like a valid email address,
//  false if not.
///////////////////////////////////////////////////////////////////////
function validate_email($input) {
    if (preg_match('/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$/i', $input))
        return $input;
    return false;
}





///////////////////////////////////////////////////////////////////////
//  Function: validate_url($url)
//
//  Returns the original string if it looks like a valid url,
//  false if not.
///////////////////////////////////////////////////////////////////////
function validate_url($input) {
    $input = strtolower($input);
    if (preg_match('/^\w+$/', $input))
        return $input;
    return false;
}





///////////////////////////////////////////////////////////////////////
//  Function: validate_username($username)
//
//  Returns the original string if it looks like a valid email address,
//  false if not.
///////////////////////////////////////////////////////////////////////
function validate_username($input) {
    if (preg_match('/^[a-z0-9\.\-_]+$/i', $input))
        return $input;
    return false;
}













///////////////////////////////////////////////////////////////////////
//  Function: startSession()
//
//  Call this function to start the PHP session
//  This function should not be used in place of securePage()!
//  This function does not make sure they have a valid username,
//  call securePage() first.
//
//  Returns 0 on success, 1 on failure.
//
///////////////////////////////////////////////////////////////////////
function startSession() {
    global $conf;

    // If the command line agent, dcm.pl, is making the request, don't really start a session.
    if (preg_match('/console-module-interface/', $_SERVER['HTTP_USER_AGENT'])) {

        // Pretend to log them in
        if (preg_match('/unix_username=([^&]+?)(&|$)/', $_REQUEST['options'], $matches)) {
            $_SESSION['ona']['auth']['user']['username'] = $matches[1];
        }

        return(1);
    }

    // Set the name of the cookie (nicer than default name)
    session_name("ONA_SESSION_ID");

   // Set cookie to expire at end of session
   // secure cookie
   if (isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) {

       session_set_cookie_params(0, '/', $_SERVER["SERVER_NAME"], 1);
   }
   // normal cookie
   else {
       session_set_cookie_params(0, '/');
   }

    // (Re)start the session
    session_start();

    // According to PHP.net comment this is a good thing to do:
    // http://us2.php.net/manual/en/function.session-start.php
    header("Cache-control: private");

    // Display session variables
//     if ($conf['debug'] >= 0) {
//         print "Session Variables:<pre>";
//         var_export($_SESSION);
//         print "</pre>\n";
//     }

    return(0);
}









///////////////////////////////////////////////////////////////////////
//  Function: securePage()
//
//  Call this function at the top of each page that needs to be
//  secure (i.e. it requires a user who is properly logged in)
//
//  MP: not used currently.. login is built into the html_desktop
//
///////////////////////////////////////////////////////////////////////
function securePage() {
    global $conf;

    // If the sessionID is not present start the session (reading the
    // users cookie and loading their settings from disk)
    if ( ONA_SESSION_ID != "" ) startSession();

    // Make sure their session is still active
    if (!(isset($_SESSION['ona']['auth']['user']['username']))) {
        //header("Location: {$https}{$baseURL}/login.php?expired=1");
        exit();
    }

    return(0);
}









///////////////////////////////////////////////////////////////////////
//  Function: loggedIn()
//
//  Returns true if the user is authenticated, false if not
//
///////////////////////////////////////////////////////////////////////
function loggedIn() {
    // Make sure their session is still active
    if (isset($_SESSION['ona']['auth']['user']['username']))
        return true;
    return false;
}







//////////////////////////////////////////////////////////////////////////////
// Returns true if the current user has access to the requested resource,
// false if not.
//////////////////////////////////////////////////////////////////////////////
function auth($resource,$msg_level=1) {

    if (!is_string($resource)) return false;
    if (array_key_exists($resource, (array)$_SESSION['ona']['auth']['perms'])) {
        printmsg("DEBUG => auth() User[{$_SESSION['ona']['auth']['user']['username']}] has the {$resource} permission",5);
        return true;
    }
    printmsg("DEBUG => auth() User[{$_SESSION['ona']['auth']['user']['username']}] does not have the {$resource} permission",$msg_level);
    return false;
}





//////////////////////////////////////////////////////////////////////////////
// Returns true if the current user has a "level" greater than or equal to
// the level passed into the function.  Returns false if not.
//////////////////////////////////////////////////////////////////////////////
function authlvl($level) {

    // FIXME: hack until we get auth stuff working:
    printmsg("DEBUG => FIXME: authlvl() always returns true for now", 1);
    return true;

    if (!is_numeric($level)) return false;
    if ($_SESSION['ona']['auth']['user']['level'] >= $level) {
        printmsg("DEBUG => authlvl() {$_SESSION['ona']['auth']['user']['username']}'s level is >= {$level}",1);
        return true;
    }
    printmsg("DEBUG => authlvl() {$_SESSION['ona']['auth']['user']['username']}'s level is not >= {$level}",1);
    return false;
}




///////////////////////////////////////////////////////////////////////
//  Function: load_module($name)
//
//  Runs a require_once($filename) to load the module named $name
//  Returns 0 on success, 1 on failure.  On failure $self['error']
//  will contain an error description.
//  Note: $conf['dcm_module_dir'] must be defined!
//
//  Example:
//      load_module('my_module');
///////////////////////////////////////////////////////////////////////
function load_module($name='') {
    global $conf, $self, $onadb;

    if (!$name) {
        $self['error'] = "ERROR => load_module() No module specified!";
        return(1);
    }

    // If the module is already loaded, return success
    if (function_exists($name)) { return(0); }

    // Make sure we're connected to the DB
    // require_once($conf['inc_functions_db']);

    // Use cache if possible
    if (!is_array($self['cache']['modules']) or !array_key_exists('get_module_list', $self['cache']['modules'])) {
        // Get a list of the valid "modules" and their descriptions.
        require_once($conf['dcm_module_dir'] . '/get_module_list.inc.php');
        list($status, $self['cache']['modules']) = get_module_list('type=array');
    }

    // Make sure the user requested a valid "module"
    if (!array_key_exists($name, $self['cache']['modules'])) {
        // Otherwise print an error
        $self['error'] = "ERROR => The requested module is not valid!";
        return(1);
    }

    // Make sure the include file containing the function(s)/module(s) requested exists..
    // We have to find out which file it's in.
    list($status, $rows, $module) = db_get_record($onadb, 'dcm_module_list', array('name' => $name));
    if ($status or $rows != 1) {
        $self['error'] = 'ERROR => The specified module does not exist';
        return(1);
    }
    $file = $conf['dcm_module_dir'] . '/' . $module['file'];

    if (!is_file($file)) {
        // Otherwise print an error
        $self['error'] = "ERROR => The include file ({$file}) for the {$name} module doesn't exist!";
        return(1);
    }

    // Include the file
    // The file should define a function called generate_config() to which we pass a node-name,
    // and receive a configuration file.
    require_once($file);

    // Test that the module function existed in the file we just loaded
    if (!function_exists($name)) {
        $self['error'] = "ERROR => The module function {$name} doesn't exist in file: {$file}";
        return(1);
    }

    return(0);
}










///////////////////////////////////////////////////////////////////////
//  Function: run_module(string $module, string or array $module_options, $transaction=1)
//
//  Runs the specified module and returns the status and output
//  of the specified module.
//
//  Input Values:
//    $module  = the name of the module to run.
//    $options = an array of key => value pairs to pass to the module,
//               or an already formatted string of the key => value
//               pairs.
//    $transaction = set to 0 or false to disable transaction code.
//
//  Return Values:
//    Returns a two part array: array($status, $output)
//      $status = exit status of the specified module, generally 0 on
//                success and non-zero on error.
//      $output = textual output of the specified module.  This can
//                occasionally be an error message generated by this
//                function itself.
//
//  Example:
//      list($status, $text) = run_module('alias_del', array('alias' => 'time01'));
///////////////////////////////////////////////////////////////////////
function run_module($module='', $options='', $transaction=1) {
    global $conf, $self, $onadb;

    // Build the options array string from $options_string if we need to
    // This is only used for logging!  If $options_string is an array it
    // is passed untouched to the module.
    $options_string = $options;
    if (is_array($options)) {
        $options_string = '';
        $and = '';
        foreach (array_keys($options) as $key) {
            // Quote any "special" characters in the value.
            // Specifically the '=' and '&' characters need to be escaped.
            $options[$key] = str_replace(array('=', '&'), array('\=', '\&'), $options[$key]);
            // If the key has no value or it is the javascript key, dont print it.
            if (($options[$key] != "") and ($key != 'js')) {
                $options_string .= "{$and}{$key}={$options[$key]}";
                $and = '&';
            }
        }
    }

    // get the options as an array so we can look for logging info
    $local_options = parse_options($options);

    // If the user passes in an option called 'module_loglevel' then use it as the run module output level
    // otherwise default it to 1 so it will print out as normal.
    $log_level = 1;
    if ($local_options['module_loglevel']) {
        $log_level = $local_options['module_loglevel'];
    }

    // Remove config info as it can be huge and could have sensitive info in it.
    // This could cause issues since I"m doing & as an anchor at the end.  see how it goes.
    // The module that is called could also display this information depending on debug level
    $options_string = preg_replace("/config=.*&/", '', $options_string);

    printmsg("INFO => Running module: {$module} options: {$options_string}", $log_level);

    // Load the module
    if (load_module($module)) { return(array(1, $self['error'] . "\n")); }

    // Start an DB transaction (If the database supports it)
    if ($transaction) $has_trans = $onadb->BeginTrans();
    if (!$has_trans) printmsg("WARNING => Transactions support not available on this database, this can cause problems!", 1);

    // If begintrans worked and we support transactions, do the smarter "starttrans" function
    if ($has_trans) {
        printmsg("DEBUG => Commiting transaction", 2);
        $onadb->StartTrans();
    }

    // Start a timer so we can display moudle run time if debugging is enabled
    $start_time = microtime_float();

    // Run the function
    list($status, $output) = $module($options);

    // Stop the timer, and display how long it took
    $stop_time = microtime_float();
    printmsg("DEBUG => [Module_runtime] " . round(($stop_time - $start_time), 2) . " seconds -- [Total_SQL_Queries] " . $self['db_get_record_count'] . " --  [Module_exit_code] {$status}", 1);

    // Either commit, or roll back the transaction
    if ($transaction and $has_trans) {
        if ($status != 0) {
            printmsg("INFO => There was a module error, marking transaction for a Rollback!", 1);
            //$onadb->RollbackTrans();
            $onadb->FailTrans();
        }
    }

    if ($has_trans) {
        // If there was any sort of failure, make sure the status has incremented, this catches sub module output errors;
        if ($onadb->HasFailedTrans()) $status = $status + 1;

        // If the user passed the rollback flag then dont commit the transaction
// FIXME: not complete or tested.. it would be nice to have an ability for the user to pass
//        a rollback flag to force the transaction to rollback.. good for testing adds/modify.
//        The problem is sub modules will fire and then the whole thing stops so you wont see/test the full operation.
//         if ($local_options['rollback']) {
//             printmsg("INFO => The user requested to mark the transaction for a rollback, no changes made.", 0);
//             $output .= "INFO => The user requested to mark the transaction for a rollback, no changes made.\n";
//             $status = $status + 1;
//         }

        printmsg("DEBUG => Commiting transaction", 2);
        $onadb->CompleteTrans();
    }

    // Return the module's output
    return(array($status, $output));
}







///////////////////////////////////////////////////////////////////////
//  Function: parse_options($options)
//
//  Takes an options string (passed to a dcm module) and returns
//  an array of the key=value pairs included in it.
//  Values are allowed to contain "=" and "&" characters as long as
//  they are escaped with a "\" character.
//
//  Example:
//      $options = parse_options('key=value&key2=value2');
//      if ($options['key'] == 'value') { Blah; }
///////////////////////////////////////////////////////////////////////
function parse_options($options="") {

    // If it's already an array, just return it
    if (is_array($options)) { return($options); }

    $newoptions = array();

    // Replace "\&" and "\=" with a random string
    $replace_and = '{' . md5(mt_rand(100000, 900000)) . '}';
    $replace_eq  = '{' . md5(mt_rand(100000, 900000)) . '}';
    $options = str_replace(
                   array('\&', '\='),
                   array($replace_and, $replace_eq),
                   $options
               );

    // Parse incoming options - split on '&'
    foreach (explode('&', $options) as $set) {
        $pair = array('','');

        // Now split on '='
        $pair = explode('=', $set);

        // Replace previously escaped & and = characters with the real thing
        $pair = str_replace(
                    array($replace_and, $replace_eq),
                    array('&', '='),
                    $pair);

        // And set the key=value in $newoptions
        $newoptions[$pair[0]] = $pair[1];
    }

    return($newoptions);
}














///////////////////////////////////////////////////////////////////////
//  Function: sanitize_YN($string, $default)
//
//  Takes a string and returns either 'Y' or 'N'.  'Y' is returned
//  by default if $string is empty, unless $default is changed.
//
//  Example:
//      $string = sanitize_YN($string)
///////////////////////////////////////////////////////////////////////
function sanitize_YN($string="", $default="Y") {
    if ($string == "")
        return($default);
    $string = strtolower($string);
    if ($string == 'no' or $string == 'n' or $string == 'off' or $string == '0')
        return('N');
    else if ($string == 'yes' or $string == 'y' or $string == 'on' or $string == '1')
        return('Y');
    else
        return($default);
}









/**
 * show diff
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * Modified by Brandon Zehm, pulled from dokuwiki.
 * Requires DifferenceEngine.php from dokuwiki.
 *
 * Input: two strings
 * Output: html
 */
function html_diff($old, $new, $oldname='', $newname='', $stdout=1) {
    global $conf;
    $html = '';

    if(!($old and $new)) return('ERROR => Insufficient parameters passed to html_diff()!');

    // Load diff code
    require_once($conf['inc_diff']);

    $df = new Diff(explode("\n",htmlspecialchars($old)),
                   explode("\n",htmlspecialchars($new)));
    $tdf = new TableDiffFormatter();

    $html .= <<<EOL
\n    <table class="diff" width="100%">
        <tr>
          <td colspan="2" width="50%" class="diff-header">
            {$oldname}
          </td>
          <td colspan="2" width="50%" class="diff-header">
            {$newname}
          </td>
        </tr>
EOL;
    $html .= $tdf->format($df) . "</table>";

    if ($stdout)
        echo $html;
    else
        return($html);
}






/**
 * show diff
 *
 * @author Matt Pascoe <matt@opennetadmin.com>
 * Requires DifferenceEngine.php from dokuwiki.
 *
 * Input: two strings
 * Output: unified text diff
 */
function text_diff($old, $new) {
    global $conf;
    $html = '';

    if(!($old and $new)) return('ERROR => Insufficient parameters passed to text_diff()!');

    // Load diff code
    require_once($conf['inc_diff']);

    $df = new Diff(explode("\n",$old),
                   explode("\n",$new));
    $tdf = new UnifiedDiffFormatter();

    $text .= $tdf->format($df);

    return($text);
}





///////////////////////////////////////////////////////////////////////
//  Function: format_array($array)
//
//  Takes an array and returns a formatted string of the contents
//  of the array for display. Usually used in the ona_xxx_display()
//  functions to display database records.
//
//  Example:
//      $string = format_array($array)
///////////////////////////////////////////////////////////////////////
function format_array($array=array()) {

    $text = '';
    foreach (array_keys($array) as $key) {

        // Make some data look pretty
        if      ($key == 'ip_addr')        { $array[$key] = ip_mangle($array[$key], 'dotted'); }
        else if ($key == 'ip_addr_start')  { $array[$key] = ip_mangle($array[$key], 'dotted'); }
        else if ($key == 'ip_addr_end')    { $array[$key] = ip_mangle($array[$key], 'dotted'); }
        else if ($key == 'ip_mask')    { $array[$key] = ip_mangle($array[$key]). '  (/'.ip_mangle($array[$key],'cidr').')'; }
        else if ($key == 'mac_addr') { $array[$key] = mac_mangle($array[$key]); if ($array[$key] == -1) $array[$key] = ''; }
        else if ($key == 'host_id')           {
            list($status, $rows, $host) = ona_find_host($array[$key]);
            if ($host['id'])
                $array[$key] = str_pad($array[$key], 20) . strtolower("({$host['fqdn']})");
        }
        else if ($key == 'server_id')         {
            list($status, $rows, $server) = ona_get_server_record(array('id' => $array[$key]));
            list($status, $rows, $host) = ona_find_host($server['host_id']);
            if ($host['id'])
                $array[$key] = str_pad($array[$key], 20) . strtolower("({$host['fqdn']})");
        }
        else if ($key == 'subnet_id')        {
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $array[$key]));
            if ($subnet['id'])
                $array[$key] = str_pad($array[$key], 20) . strtoupper("({$subnet['name']})");
        }
        else if ($key == 'domain_id' or $key == 'primary_dns_domain_id') {
            list($status, $rows, $domain) = ona_get_domain_record(array('id' => $array[$key]));
            $array[$key] = str_pad($array[$key], 20) . strtolower("({$domain['fqdn']})");
        }
        else if ($key == 'interface_id') {
            list($status, $rows, $interface) = ona_get_interface_record(array('id' => $array[$key]));
            $array[$key] = str_pad($array[$key], 20) . '(' .ip_mangle($interface['ip_addr'], 'dotted') . ')';
        }
        else if ($key == 'device_type_id') {
            list($status, $rows, $devtype) = ona_get_device_type_record(array('id' => $array[$key]));
            if ($devtype['id']) {
                list($status, $rows, $model) = ona_get_model_record(array('id' => $devtype['model_id']));
                list($status, $rows, $role)  = ona_get_role_record(array('id' => $devtype['role_id']));
                list($status, $rows, $manu)  = ona_get_manufacturer_record(array('id' => $model['manufacturer_id']));
                $array[$key] = str_pad($array[$key], 20) . "({$manu['name']}, {$model['name']} ({$role['name']}))";
            }
        }
        else if ($key == 'custom_attribute_type_id') {
            list($status, $rows, $ca) = ona_get_custom_attribute_type_record(array('id' => $array[$key]));
            if ($ca['id'])
                $array[$key] = str_pad($array[$key], 20) . "({$ca['name']})";
        }

        // Align columns
        if ($array[$key]) { $text .= str_pad("  {$key}", 30) . $array[$key] . "\n"; }
    }

    // Return a nice string :)
    return($text);
}










?>
