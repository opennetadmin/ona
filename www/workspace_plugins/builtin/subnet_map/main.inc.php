<?php

// This module will display a small dragable subnet map.  It is required that you place the following statement
// at the top of your display page that calls this function:
//
//    include('include/functions_network_map.inc.php');

$title_left_html = '';


// Get the numeric IP address of our subnet (we replace the last quad with a .0)
$ip = ip_mangle(preg_replace('/\.\d+$/', '.0', $record['ip_addr']), 'numeric');
$ip_subnet = ip_mangle($record['ip_addr'], 'numeric');
$ip_netmask = ip_mangle($record['ip_mask'], 'numeric');
$net_end = ((4294967295 - $ip_netmask) + $ip_subnet);

$title_left_html .= <<<EOL
    <a title="Display full sized subnet map"
        class="act"
        onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_block_map\', \'ip_block_start=>{$record['ip_addr']}\', \'display\');');"
    ><img src="{$images}/silk/shape_align_left.png" border="0"></a>
    <a title="Highlight current subnet"
        class="act"
        onClick="
            var _el = el('{$ip_subnet}_block');
            if (_el) {
            if (_el.style.isHighlighted) {
                _el.style.backgroundColor = '{$color['bgcolor_map_subnet']}';
                _el.style.isHighlighted = false;
            }
            else {
                _el.style.backgroundColor = '{$color['bgcolor_map_selected']}';
                _el.style.isHighlighted = true;
            }
            }
        "
    ><img src="{$images}/silk/paintbrush.png" border="0"></a>&nbsp;&nbsp;Subnet Allocation Map
EOL;


$title_right_html = '';






$modbodyhtml .= <<<EOL
        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">

            <tr><td colspan="99" nowrap="true" align="center">
                <input type="hidden" id="{$extravars['window_name']}_zoom" name="zoom" value="7">
                <div id="{$extravars['window_name']}_portal" onclick="el('dragmessage').style.display='';el('{$extravars['window_name']}_portal').style.color = '#FFFFFF';el('{$extravars['window_name']}_portal').style.height = '150px';el('{$extravars['window_name']}_portal').myonmouseup('fake event');" style="text-align: center;color: #999999;position: relative; height: 19px; width: 355px;">
                    Click here to show map
                    <span id="{$extravars['window_name']}_substrate"></span>
                </div>
                </td>
                <td>
                    <span id="dragmessage" style="display: none;font-size:10px;position:fixed;margin-left: -11px;">&uarr;<br><br>D<br>R<br>A<br>G<br><br>&darr;<br></span>
                </td>
            </tr>
        </table>
EOL;

// Get a list of blocks that touches this subnet
list($status, $rows, $blocks) = db_get_records($onadb, 'blocks', "{$ip_subnet} BETWEEN ip_addr_start AND ip_addr_end OR {$net_end} BETWEEN ip_addr_start AND ip_addr_end OR ip_addr_start BETWEEN {$ip_subnet} and {$net_end}");
if ($rows) {
    $modbodyhtml .= <<<EOL
        <div style="border: 1px solid; border-bottom: none">
            <div class="list-header">This subnet is related to following block(s):</div>
EOL;

    foreach($blocks as $block) {
        $block['ip_addr_start_text'] = ip_mangle($block['ip_addr_start'], 'dotted');
        $block['ip_addr_end_text'] = ip_mangle($block['ip_addr_end'], 'dotted');
        $modbodyhtml .= <<<EOL
            <div class="list-row"><a title="View block. ID: {$block['id']}"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_block\', \'block_id=>{$block['id']}\', \'display\')');"
                    >{$block['name']}</a> ({$block['ip_addr_start_text']} - {$block['ip_addr_end_text']})</div>
EOL;
    }
    $modbodyhtml .= <<<EOL
        </div>
EOL;
}

// Get javascript to setup the map portal mouse handlers
// Force ip end to be less than ip start to prevent Block highlighting
$modjs .= get_portal_js($extravars['window_name'], $ip, $ip -1);


?>