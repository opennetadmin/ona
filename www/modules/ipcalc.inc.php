<?
// DON'T put whitespace at the beginning or end of this file!!!




///////////////////////////////////////////////////////////////////////
//  Function: ipcalc (string $options='')
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
//  Example: list($status, $result) = ipcalc('ip=address,mask=address');
///////////////////////////////////////////////////////////////////////
function ipcalc($options) {
    global $conf, $self;
    printmsg('DEBUG => ipcalc('.$options.') called', 3);

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

ipcalc v{$version}
Displays various IP representations and basic subnet calculations

  Synopsis: ipcalc(OPTIONS)

  Required:
    ip=STRING       32 or 128-bit Internet address
    mask=STRING     the mask you want to use
\n
EOM

        ));
    }

// FIXME MP: fix the fact that I"m not testing for the GMP module.. it will fail for ipv6 stuff

// MP: I could also provide a more parsable output as well.. maybe even XML wonderfulness

    $text = '';
    $ipinfo = ipcalc_info($options['ip'],$options['mask']);

// MP: I removed the following as they are tooooo large
//bin128:  {$ipinfo['ip_bin128']}
//bin128:      {$ipinfo['mask_bin128']}



    $text .= <<<EOL

Input: IP={$ipinfo['in_ip']}    MASK={$ipinfo['in_mask']}

IP={$ipinfo['in_ip']}
  Dotted:  {$ipinfo['ip_dotted']}
  Numeric: {$ipinfo['ip_numeric']}
  binary:  {$ipinfo['ip_binary']}
  ipv6:    {$ipinfo['ip_ipv6']}
  ipv6gz:  {$ipinfo['ip_ipv6gz']}
  flip:    {$ipinfo['ip_flip']}

MASK={$ipinfo['mask_dotted']}
  Dotted:      {$ipinfo['mask_dotted']}
  Numeric:     {$ipinfo['mask_numeric']}
  Cidr:        {$ipinfo['mask_cidr']}
  binary:      {$ipinfo['mask_binary']}
  bin invert:  {$ipinfo['mask_bin_invert']}
  ipv6:        {$ipinfo['mask_ipv6']}
  ipv6gz:      {$ipinfo['mask_ipv6gz']}
  flip:        {$ipinfo['mask_flip']}
  IP invert:   {$ipinfo['mask_dotted_invert']}

The subnet your IP falls in is:   {$ipinfo['truenet']}/{$ipinfo['mask_cidr']} ({$ipinfo['mask_dotted']})

Total addresses using this mask:  {$ipinfo['ip_total']}
Usable addresses using this mask: {$ipinfo['ip_usable']}
Last address using this mask:     {$ipinfo['ip_last']}


EOL;

    return(array(0, $text));
}


?>