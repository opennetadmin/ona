<?php

$title_left_html = '';

if (auth('host_modify',$debug_val)) {
    $title_left_html .= <<<EOL
                <a title="Edit host. ID: {$record['id']}"
                class="act"
                onClick="xajax_window_submit('edit_host', xajax.getFormValues('form_host_{$record['id']}'), 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>
EOL;
}
if (auth('host_del',$debug_val)) {
    $title_left_html .= <<<EOL
                <a title="Delete host"
                class="act"
                onClick="xajax_window_submit('tooltips', 'name=>edit_host', 'window_progressbar');xajax_window_submit('edit_host', xajax.getFormValues('form_host_{$record['id']}'), 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
}

$title_left_html .= <<<EOL
                &nbsp;{$record['name']}.<a title="View domain. ID: {$record['domain_id']}"
                                                class="domain"
                                                onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                                            >{$record['domain_fqdn']}</a>
EOL;




$title_right_html .= <<<EOL
                <a href="?work_space={$extravars['window_name']}&host={$record['id']}"><img title="Direct link to {$record['fqdn']}" src="{$images}/silk/application_link.png" border="0"></a>
EOL;


// Define the tag type
$tagtype = 'host';

// Print tag list
$modjs .= <<<EOL
    xajax_window_submit('tooltips', 'type => {$tagtype}, reference => {$record['id']}, updateid => taglist_{$extravars['window_name']}', 'tag_html');
EOL;

// print the add tag button if you have access
if (auth('host_add') or auth('subnet_add') ) {
  // setup a tag quickfind button
  $modjs .= <<<EOL
    /* Setup the Quick Find Tag icon */
    var _button = el('qf_tag_{$extravars['window_name']}');
    _button.style.cursor = 'pointer';
    _button.onclick =
        function(ev) {
            if (!ev) ev = event;
            /* Create the popup div */
            wwTT(this, ev,
                 'id', 'tt_qf_tag_{$extravars['window_name']}',
                 'type', 'static',
                 'direction', 'south',
                 'delay', 0,
                 'styleClass', 'wwTT_qf',
                 'javascript',
                 "xajax_window_submit('tooltips', '" +
                     "tooltip=>qf_tag," +
                     "type=>{$tagtype}," +
                     "updateid=>taglist_{$extravars['window_name']}," +
                     "reference=>{$record['id']}," +
                     "id=>tt_qf_tag_{$extravars['window_name']}," +
                     "input_id=>set_tag_{$extravars['window_name']}');"
            )
        };
EOL;

  $addtaghtml .= <<<EOL
                       <span id="qf_tag_{$extravars['window_name']}">
                        <img title="Add a tag" src="{$images}/silk/tag_blue.png" border="0"
                       /></span>
EOL;
}

// print the tag section into the workspace
$taghtml = <<<EOL
                <tr>
                    <td align="right" nowrap="true"><b>Tags</b>&nbsp;</td>
                    <td nowrap="true" class="tag" align="left" >
                       {$addtaghtml}
                       <span id='taglist_{$extravars['window_name']}'></span>
                    </td>
                </tr>
EOL;


$modbodyhtml .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tr>
                    <td align="right" nowrap="true" title="Device type ID: {$record['device_type_id']}"><b>Device Type</b>&nbsp;</td>
                    <td nowrap="true" class="padding" align="left" title="{$record['devicefull']}">{$record['device']}</td>
                </tr>

                $taghtml

                <tr>
                    <td align="right" {$notes_valign} nowrap="true"><b>Notes</b>&nbsp;</td>
                    <td nowrap="true" class="padding" align="left" {$notes_width}><textarea readonly size="256" cols=25 rows=3 class="display_notes">{$record['notes']}</textarea></td>
                </tr>
EOL;


$modbodyhtml .= <<<EOL
            </table>
EOL;

    $wspl = workspace_plugin_loader('location_detail',$record,$extravars);
    $modbodyhtml .= $wspl[0]; $modbodyjs .= $wspl[1];

?>
