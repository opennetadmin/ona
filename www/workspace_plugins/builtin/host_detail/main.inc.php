<?

$titlehtml = '';

if (auth('host_modify',$debug_val)) {
    $titlehtml .= <<<EOL
                <a title="Edit host. ID: {$record['id']}"
                class="act"
                onClick="xajax_window_submit('edit_host', xajax.getFormValues('form_host_{$record['id']}'), 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>
EOL;
}
if (auth('host_del',$debug_val)) {
    $titlehtml .= <<<EOL
                <a title="Delete host"
                class="act"
                onClick="var doit=confirm('Are you sure you want to delete this host?');
                            if (doit == true)
                                xajax_window_submit('edit_host', xajax.getFormValues('form_host_{$record['id']}'), 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
}

$titlehtml .= <<<EOL
                &nbsp;{$record['name']}.<a title="View domain. ID: {$record['domain_id']}"
                                                class="domain"
                                                onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                                            >{$record['domain_fqdn']}</a>
EOL;








$modbodyhtml = '';
$modbodyhtml .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr>
                    <td align="right" nowrap="true" title="Device type ID: {$record['device_type_id']}"><b>Device Type</b>&nbsp;</td>
                    <td nowrap="true" class="padding" align="left" title="{$record['devicefull']}">{$record['device']}</td>
                </tr>

                <tr>
                    <td align="right" {$notes_valign} nowrap="true"><b>Notes</b>&nbsp;</td>
                    <td nowrap="true" class="padding" align="left" {$notes_width}>{$record['notes']}</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Serial Number</b>&nbsp;</td>
                    <td nowrap="true" class="padding" align="left">{$record['serial_number']}</td>
                </tr>

                <tr>
                    <td align="right" nowrap="true"><b>Asset Tag</b>&nbsp;</td>
                    <td nowrap="true" class="padding" align="left">{$record['asset_tag']}</td>
                </tr>
            </table>
EOL;

?>