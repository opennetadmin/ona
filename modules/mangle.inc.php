<?
// DON'T put whitespace at the beginning or end of this file!!!




///////////////////////////////////////////////////////////////////////
//  Function: mangle_ip (string $options='')
//
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = mangle_ip('ip=address,format=string');
///////////////////////////////////////////////////////////////////////
function mangle_ip($options) {
    global $conf, $self;
    printmsg('DEBUG => mangle_ip('.$options.') called', 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['ip']) {
        $self['error'] = 'ERROR => Insufficient parameters';
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1,
<<<EOM

mangle_ip v{$version}
Converts between various IP address representations

  Synopsis: mangle_ip(OPTIONS)

  Required:
    ip=<inet_addr>  32 or 128-bit Internet address

  Optional:
    format=<specifier>  Desired output format, specified as a string or number
                        '1' or 'numeric' : return ip as an integer
                        '2' or 'dotted'  : return ip as an IPv4 address
                        '3' or 'cidr'    : return ip as a CIDR netmask
                        '4' or 'binary'  : return ip as a 32-bit binary string
                        '5' or 'bin128'  : return ip as a 128-bit binary string
                        '6' or 'ipv6'    : return ip as an IPv6 address

\n
EOM

        ));
    }

    // Now what?  We need to call ip_mangle() with our options
    if (!$options['format'])
        $options['format'] = 'default';
    $retval = "\n" . ip_mangle($options['ip'], $options['format']) . "\n";

    if ($self['error'] != '')
        return (array(1, $self['error'] . "\n" . $retval));
    else
        return (array(0, $retval));
}


?>
