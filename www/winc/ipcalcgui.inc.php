<?php

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


// NOTE: yes it is annoying but I had to do the <br> thing due to windows
// not being able to handle the new lines via a standard <pre> statement.
// I went with this as it keeps things consistant between platforms.  The pre is now a span
    $text .= <<<EOL
<br />
<br />
Input: IP={$ipinfo['in_ip']}    MASK={$ipinfo['in_mask']}<br />
<br />
IP={$ipinfo['in_ip']}<br />
  Dotted:  {$ipinfo['ip_dotted']}<br />
  Numeric: {$ipinfo['ip_numeric']}<br />
  binary:  {$ipinfo['ip_binary']}<br />
  ipv6:    {$ipinfo['ip_ipv6']}<br />
  ipv6gz:  {$ipinfo['ip_ipv6gz']}<br />
  flip:    {$ipinfo['ip_flip']}<br />
<br />
MASK={$ipinfo['mask_dotted']}<br />
  Dotted:      {$ipinfo['mask_dotted']}<br />
  Numeric:     {$ipinfo['mask_numeric']}<br />
  Cidr:        /{$ipinfo['mask_cidr']}<br />
  binary:      {$ipinfo['mask_binary']}<br />
  bin invert:  {$ipinfo['mask_bin_invert']}<br />
  ipv6:        {$ipinfo['mask_ipv6']}<br />
  ipv6gz:      {$ipinfo['mask_ipv6gz']}<br />
  flip:        {$ipinfo['mask_flip']}<br />
  IP invert:   {$ipinfo['mask_dotted_invert']}<br />
<br />
The subnet your IP falls in is:   {$ipinfo['truenet']}/{$ipinfo['mask_cidr']} ({$ipinfo['mask_dotted']})<br />
<br />
Total addresses using this mask:  {$ipinfo['ip_total']}<br />
Usable addresses using this mask: {$ipinfo['ip_usable']}<br />
Last address using this mask:     {$ipinfo['ip_last']}<br />
<br />

EOL;



    $response = new xajaxResponse();
    $response->addAssign('ipcalc_data', "innerHTML", $text);
    return($response->getXML());
}








?>