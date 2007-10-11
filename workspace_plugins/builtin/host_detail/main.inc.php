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
                    <form id="form_host_{$record['id']}"
                        ><input type="hidden" name="host_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>


                <div>
                    <span align="right" nowrap="true" title="Device type ID: {$device_type['id']}"><b>Device Type</b>&nbsp;</span>
                    <span class="padding" align="left" title="{$record['devicefull']}">{$record['device']}</span><br>
                </div>

                <div>
                    <span align="right" {$notes_valign} nowrap="true"><b>Notes</b>&nbsp;</span>
                    <span class="padding" align="left" {$notes_width}>{$record['notes']}</span><br>
                </div>

                <div>
                    <span align="right" nowrap="true"><b>Serial Number</b>&nbsp;</span>
                    <span class="padding" align="left">{$record['serial_number']}</span><br>
                </div>

                <div>
                    <span align="right" nowrap="true"><b>Asset Tag</b>&nbsp;</td>
                    <span class="padding" align="left">{$record['asset_tag']}</span><br>
                </div>

EOL;

?>