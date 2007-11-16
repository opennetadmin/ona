<?

//////////////////////////////////////////////////////////////////////////////
// Function: ws_submit($input)
//
// Description:
//     Inserts dynamic content into a tool-tip popup.
//     $form is a string array that should look something like this:
//       "tooltip=>something,id=>element_id,something_id=>143324"
//////////////////////////////////////////////////////////////////////////////
function ws_ipcalcgui_submit($window_name, $form='') {
    global $conf, $self, $onadb, $tip_style;
    global $font_family, $color, $style, $images;
    $html = $js = '';

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);


    $text = '';
    $ipinfo = ipcalc_info($form['ip'],$form['mask']);

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
  Cidr:        /{$ipinfo['mask_cidr']}
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



    $response = new xajaxResponse();
    $response->addAssign('ipcalc_data', "innerHTML", $text);
    return($response->getXML());
}








?>