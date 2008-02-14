<?
// Include map portal functions
include('include/functions_network_map.inc.php');




//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a block record and all associated info in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $ona;
    global $images, $color, $style;
    $html = '';
    $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Build $ip from $form['ip_block_start']
    $form['ip_block_start'] = ip_complete($form['ip_block_start'], '0');

    // Since we currently only display /24 (C) class networks, the
    // last quad needs to be a .0.
    $ip = $form['ip_block_start'] = preg_replace('/\.\d+$/', '.0', $form['ip_block_start']);

    // Find out if $ip is valid
    $ip = ip_mangle($ip, 'numeric');
    if ($ip == -1) {
        $js .= "alert('The IP address specified is invalid!');";
    }

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = 'Map: ' . ip_mangle($ip, 'dotted');
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // Define the window's inner html
    $html .= <<<EOL
    <div id="{$window_name}_content" style="padding: 2px 4px;">
        <form id="block_search_form" onsubmit="el('zoom_block_button').onclick(); return false;">
        <div id="{$window_name}_tools">
            <b>IP Address</b>
            <input type="hidden" id="{$window_name}_zoom" name="zoom" value="8">
            <input id="{$window_name}_ip_block_start" name="{$window_name}_ip_block_start" value="{$form['ip_block_start']}" class="edit" type="text" size="15" />
            <a id="zoom_block_button" title="Zoom block"
                       class="act"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'ip_block_start=>' + el('{$window_name}_ip_block_start').value + ',zoom=>' + el('{$window_name}_zoom').value + '\', \'display\');');"
            ><img src="{$images}/silk/bullet_go.png" border="0"></a>&nbsp;
        <br><br>
        </div>
        </form>

        <div id="{$window_name}_portal">
            <span id="{$window_name}_substrate"></span>
        </div>

    </div>
EOL;

    // Position/size the portal ourselves
    $js .=<<<EOL
        var _el = el('{$window_name}_portal');

        /* Now calculate where we will sit */
        var my_height = el('work_space_content').offsetHeight - el('{$window_name}_tools').offsetHeight - 40;
        var my_width  = el('{$window_name}_tools').offsetWidth - 10;

        /* Finally reposition/resize the window */
        _el.style.position = 'relative';
        _el.style.height   = my_height + 'px';

EOL;

    // Get javascript to setup the map portal
    $js .= get_portal_js($window_name, $ip);
    //*** Send a fake mouseup event to draw the initial map view ***
    $js .= "el('{$window_name}_portal').myonmouseup('fake event');";


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}







?>