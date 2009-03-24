<?php

$title_left_html = '';

// Calculate the percentage of the subnet that's used (total size - allocated hosts - dhcp pool size)
$usage_html = get_subnet_usage_html($record['id']);

if (auth('subnet_modify',$debug_val)) {
    $title_left_html .= <<<EOL
                <a title="Edit subnet. ID: {$record['id']}"
                    class="act"
                    onClick="xajax_window_submit('edit_subnet', xajax.getFormValues('form_subnet_{$record['id']}'), 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>
EOL;
}
if (auth('subnet_del',$debug_val)) {
    $title_left_html .= <<<EOL
                <a title="Delete subnet. ID: {$record['id']}"
                    class="act"
                    onClick="xajax_window_submit('edit_subnet', xajax.getFormValues('form_subnet_{$record['id']}'), 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
}

$title_left_html .= <<<EOL
                <b>{$record['name']}</b>&nbsp;
EOL;




$title_right_html .= <<<EOL
                <a href="?work_space={$extravars['window_name']}&subnet={$record['name']}"><img title="Direct link to {$record['name']}" src="{$images}/silk/application_link.png" border="0"></a>
EOL;




$modbodyhtml .= <<<EOL


        <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
EOL;

// Display the vlan info line only if there is a vlan associated
if ($record['vlan_id']) {
    $modbodyhtml .= <<<EOL
            <tr>
                <td align="right" nowrap="true"><b>Vlan</b>&nbsp;</td>
                <td class="padding" align="left">
                    <a title="View Vlan Campus"
                        class="nav"
                    onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_vlan_campus\', \'vlan_campus_id=>{$record['vlan_campus_id']}\', \'display\')');"
                    >{$record['vlan_campus_name']}</a>&nbsp;&#047;&nbsp;<a title="View Vlan"
                        class="nav"
                        onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_vlan\', \'vlan_id=>{$record['vlan_id']}\', \'display\')');"
                    >{$record['vlan_name']}</a>
                    </td>
            </tr>
EOL;
    }

$modbodyhtml .= <<<EOL
            <tr>
                <td align="right" nowrap="true"><b>IP Address</b>&nbsp;</td>
                <td class="padding" align="left">
                    {$record['ip_addr']}&nbsp;
                </td>
            </tr>

            <tr>
                <td align="right" nowrap="true"><b>Netmask</b>&nbsp;</td>
                <td class="padding" align="left">{$record['ip_mask']} (/{$record['ip_subnet_mask_cidr']})&nbsp;</td>
            </tr>

            <tr>
                <td align="right" nowrap="true"><b>Usage</b>&nbsp;</td>
                <td class="padding" align="left" valign="middle">{$usage_html}</td>
            </tr>

            <tr>
                <td align="right" nowrap="true"><b>Type</b>&nbsp;</td>
                <td class="padding" align="left">{$record['type']}&nbsp;</td>
            </tr>

        </table>
EOL;

    // Requires the include of the functions_network_map.inc.php file at the beginning of this file
    $wspl = workspace_plugin_loader('subnet_map',$record,$extravars);
    $modbodyhtml .= $wspl[0]; $modjs .= $wspl[1];

?>