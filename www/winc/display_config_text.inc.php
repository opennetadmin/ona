<?php

// If we get called directly (via launch_app.php), call work_space, and tell it to call us back.
// Yes, it's more of Brandon's black magic ;)
$window['js'] = <<<EOL
    removeElement('{$window_name}');
    xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'host_id=>{$_REQUEST['host_id']}\', \'display\')');
EOL;




//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays a list of config text records in the work_space div.
//   A host_id and config_type_id must be supplied.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb, $baseURL;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // Dont display this if they dont have access
    if (!auth('host_config_admin',$debug_val)) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>You don't have access to this page</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    if ($form['type_id']) {
        list($status, $rows, $rec) = ona_get_config_type_record(array('id' => $form['type_id']));
        $default_type = $rec['name'];
    }

    // Load the host record
    list($status, $rows, $host) = ona_find_host($form['host_id']);
    if (!$host['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Get configurations info
    list($status, $rows, $configs) = db_get_records($onadb,'configurations',array('host_id' => $host['id']),'ctime DESC');


    // Update History Title (and tell the browser to re-draw the history div)
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = "Configs: {$host['name']}";
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    $style['content_box'] = <<<EOL
        margin: 10px 20px;
        padding: 2px 4px;
        background-color: #FFFFFF;
        vertical-align: top;
EOL;

    $style['label_box'] = <<<EOL
        font-weight: bold;
        padding: 2px 4px;
        border: solid 1px {$color['border']};
        background-color: {$color['window_content_bg']};
EOL;

    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

    $html .= <<<EOL
    <!-- FORMATTING TABLE -->
    <div style="{$style['content_box']}">
    <table cellspacing="0" border="0" cellpadding="0"><tr>

        <!-- START OF FIRST COLUMN OF SMALL BOXES -->
        <td nowrap="true" valign="top" style="padding-right: 15px;">
EOL;


    // Config archive INFORMATION
    $html .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">

                <tr><td colspan="99" nowrap="true" style="{$style['label_box']}">
                    <!-- LABEL -->

                    <b>Configuration archives for:</b>
                    <a title="View host. ID: {$host['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$host['id']}\', \'display\')');"
                    >{$host['name']}</a
                    >.<a title="View domain. ID: {$host['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$host['domain_id']}\', \'display\')');"
                    >{$host['domain_fqdn']}</a>
EOL;

        $html .= <<<EOL
                </td></tr>

            </table>
EOL;
    // END CONFIG INFORMATION


    $html .= <<<EOL
        <!-- END OF FIRST COLUMN OF SMALL BOXES -->
        </td>

    </tr></table>
    </div>
    <!-- END OF TOP SECTION -->
EOL;











    // Config archive LIST
    $tab = 'configs';
    $submit_window = "list_{$tab}";
    $form_id = "{$submit_window}_filter_form";
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $content_id = "{$window_name}_{$submit_window}";
    $html .= <<<EOL
    <!-- Archive LIST -->
    <div style="border: 1px solid {$color['border']}; margin: 10px 20px;">

        <!-- Tab & Quick Filter -->
        <table id="{$form_id}_table" cellspacing="0" border="0" cellpadding="0">
            <tr>
                <td id="{$form_id}_{$tab}_tab" class="table-tab-active">
                    Associated {$tab} <span id="{$form_id}_{$tab}_count"></span>
                </td>

                <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                    <form id="{$form_id}" onSubmit="return false;">
                    <input id="{$form_id}_page" name="page" value="1" type="hidden">
                    <input name="content_id" value="{$content_id}" type="hidden">
                    <input name="form_id" value="{$form_id}" type="hidden">
                    <input name="host_id" value="{$host['id']}" type="hidden">
                    <div id="{$form_id}_filter_overlay"
                         title="Filter"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    ></div>
                    <input
                        id="{$form_id}_filter"
                        name="filter"
                        class="filter"
                        type="text"
                        value="{$default_type}"
                        size="15"
                        maxlength="20"
                        alt="Quick Filter"
                        onFocus="el('{$form_id}_filter_overlay').style.display = 'none';"
                        onBlur="if (this.value == '') el('{$form_id}_filter_overlay').style.display = 'inline';"
                        onKeyUp="
                            if (typeof(timer) != 'undefined') clearTimeout(timer);
                            code = 'if ({$form_id}_last_search != el(\'{$form_id}_filter\').value) {' +
                                   '    {$form_id}_last_search = el(\'{$form_id}_filter\').value;' +
                                   '    document.getElementById(\'{$form_id}_page\').value = 1;' +
                                   '    xajax_window_submit(\'{$submit_window}\', xajax.getFormValues(\'{$form_id}\'), \'display_list\');' +
                                   '}';
                            timer = setTimeout(code, 700);"
                    >
                    </form>
                </td>

            </tr>
        </table>

        <div id='{$content_id}'>
            {$conf['loading_icon']}
        </div>

        <!-- Tool LINKS -->
        <div class="act-box" style="padding: 2px 4px; border-top: 1px solid {$color['border']}">

            <input type="button"
                name="compare"
                value="Compare selected configs"
                class="button"
                onClick="
                  var tmp = 1; var OLD = el('old' + tmp);
                  while (OLD) {
                    if (OLD.checked) break;
                      OLD = el('old' + tmp++);
                    }
                    var tmp = 1; var NEW = el('new' + tmp);
                    while (NEW) {
                      if (NEW.checked) break;
                        NEW = el('new' + tmp++);
                    }
                    if ((!OLD) || (!NEW))
                      alert('ERROR => You must select and old and new config to compare them!');
                    else
                      xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'host_id=>{$form['host_id']}, old_id=>' + OLD.value + ',new_id=>' + NEW.value + '\', \'display\')');
            ">
        </div>

    </div>

EOL;
    $js .= <<<EOL
        /* Setup the quick filter */
        el('{$form_id}_filter_overlay').style.left = (el('{$form_id}_filter_overlay').offsetWidth + 10) + 'px';
        {$form_id}_last_search = '';

        /* Tell the browser to load/display the list */
        xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;

    // If they have selected to display the config, show it
    if ($form['displayconf']) {
        list($status, $rows, $config) = ona_get_config_record(array('id' => $form['displayconf']));
        $html .= <<<EOL
        <div style="margin: 10px 20px; float: left; width: 96%; background-color: {$color['bar_bg']}; border: 1px solid;">

            <table cellspacing="0" border="0" cellpadding="2" align="left" style="font-size:small;">
                <tr>
                    <td style="font-weight: bold;">Insert date:</td>
                    <td>{$config['ctime']}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Config type:</td>
                    <td>{$config['config_type_name']}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">MD5:</td>
                    <td>{$config['md5_checksum']}</td>
                </tr>
            </table>
        </div>
        <div style="margin: 0px 20px; float: left;">
            <pre style='font-family: monospace; font-size: large;'>{$config['config_body']}</pre>
        </div>
EOL;
    }







    // If they have selected to display the diff mode
    if ($form['old_id'] && $form['new_id']) {
    // Load the old config text record
    list($status, $rows, $old) = ona_get_config_record(array('id' => $form['old_id']));
    if (!$old['id']) {
        $html .= "<br><center><font color=\"red\"><b>Configuration text record doesn't exist!</b></font></center>";
    }

    // Load the new config text record
    list($status, $rows, $new) = ona_get_config_record(array('id' => $form['new_id']));
    if (!$new['id']) {
        $html .= "<br><center><font color=\"red\"><b>Configuration text record doesn't exist!</b></font></center>";
    }

    // Load the asscoiated old host record
    list($status, $rows, $old_host) = ona_find_host($old['host_id']);
    if (!$old_host['id']) {
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
    }

    // Load the asscoiated new host record
    list($status, $rows, $new_host) = ona_find_host($new['host_id']);
    if (!$new_host['id']) {
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
    }

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $history['title'] = "Config diff ({$form['old_id']} / {$form['new_id']})";
    array_push($_SESSION['ona']['work_space']['history'], $history);


    // Display the config text diff
    $html .= <<<EOL
        <div style="margin: 10px 20px;">

            <!-- Diff Headers -->
            <table width="100%" cellspacing="0" border="0" cellpadding="2" align="left" style="font-size:small;">
            <tr>
            <td align="left" style="background-color: {$color['bar_bg']};">
                <table cellspacing="0" border="0" cellpadding="2" align="left" style="font-size:small;">
                    <tr>
                        <td style="font-weight: bold;">Host:</td>
                        <td>
                            <a title="View host. ID: {$old_host['id']}"
                               class="nav"
                               onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$old_host['id']}\', \'display\')');"
                            >{$old_host['name']}</a
                            >.<a title="View domain. ID: {$old_host['domain_id']}"
                                 class="domain"
                                 onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$old_host['domain_id']}\', \'display\')');"
                            >{$old_host['domain_fqdn']}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Insert date:</td>
                        <td>{$old['ctime']}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Config type:</td>
                        <td>{$old['config_type_name']}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">MD5:</td>
                        <td>{$old['md5_checksum']}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Actions:</td>
                        <td>
                            <a title="View config"
                               class="nav"
                               onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'host_id=>{$old_host['id']},displayconf=>{$old['id']}\', \'display\')');"
                            ><img src="{$images}/silk/zoom.png" alt="View config" border="0"></a>&nbsp;

                            <a title="Download config" class="act" target="null" href="{$baseURL}/config_dnld.php?config_id={$old['id']}&download=1"
                            ><img src="{$images}/silk/disk.png" alt="Download config" border="0"></a>&nbsp;
                        </td>
                    </tr>
                </table>
            </td>
            <td align="left" style="background-color: {$color['bar_bg']};">
                <table cellspacing="0" border="0" cellpadding="2" align="left" style="font-size:small;">
                    <tr>
                        <td style="font-weight: bold;">Host:</td>
                        <td>
                            <a title="View host. ID: {$new_host['id']}"
                               class="nav"
                               onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$new_host['id']}\', \'display\')');"
                            >{$new_host['name']}</a
                            >.<a title="View domain. ID: {$new_host['domain_id']}"
                                 class="domain"
                                 onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$new_host['domain_id']}\', \'display\')');"
                            >{$new_host['domain_fqdn']}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Insert date:</td>
                        <td>{$new['ctime']}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Config type:</td>
                        <td>{$new['config_type_name']}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">MD5:</td>
                        <td>{$new['md5_checksum']}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Actions:</td>
                        <td>
                            <a title="View config"
                               class="nav"
                               onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'host_id=>{$new_host['id']},displayconf=>{$new['id']}\', \'display\')');"
                            ><img src="{$images}/silk/zoom.png" alt="View config" border="0"></a>&nbsp;

                            <a title="Download config" class="act" target="null" href="{$baseURL}/config_dnld.php?config_id={$new['id']}&download=1"
                            ><img src="{$images}/silk/disk.png" alt="Download config" border="0"></a>&nbsp;
                        </td>
                    </tr>
                </table>
            </td>
            </tr>
            <tr>
            <td colspan="2">
EOL;

    // Display the diff
    $html .= html_diff($old['config_body'], $new['config_body'], "Config A", "Config B", 0);

    $html .= <<<EOL
            </td>
            </tr>
            </table>
        </div>
EOL;

}



    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}




//MP: TODO this delete stuff should be in configuration.inc.php module!!!!!!


//////////////////////////////////////////////////////////////////////////////
// Function: ws_delete_config()
//
// Description:
//   Deletes a single config text record
//////////////////////////////////////////////////////////////////////////////
function ws_delete_config($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the config text record
    list($status, $rows, $config) = ona_get_config_record(array('id' => $form['config_id']));
    if (!$config['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Configuration text record doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Load the asscoiated host record
    list($status, $rows, $host) = ona_find_host($config['host_id']);
    if (!$host['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Check permissions
    if (! (auth('host_config_admin') and authlvl($host['lvl'])) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Delete the config text

    // FIXME, this should probably use a module, but there isn't one!

    list($status, $rows) = db_delete_records($onadb, 'configurations', array('id' => $config['id']));
    if ($status or !$rows) {
        $response = new xajaxResponse();
        $response->addScript("alert('Delete failed!');");
        return($response->getXML());
    }


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if ($form['js']) { $response->addScript($form['js']); }
    return($response->getXML());
}















//////////////////////////////////////////////////////////////////////////////
// Function: ws_delete_configs()
//
// Description:
//   Deletes all the config records for a particular host and type
//////////////////////////////////////////////////////////////////////////////
function ws_delete_configs($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the host record
    list($status, $rows, $host) = ona_find_host($form['host_id']);
    if (!$host['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Check permissions
    if (! (auth('host_config_admin') and authlvl($host['lvl'])) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Load the config type
    list($status, $rows, $type) = ona_get_config_type_record(array('id' => $form['type_id']));
    if ($status or !$rows) {
        $response = new xajaxResponse();
        $response->addScript("alert('ERROR => Invalid config type!');");
        return($response->getXML());
    }


    // Delete the config text records that match
    // FIXME, this should probably use a module, but there isn't one!
    list($status, $rows) = db_delete_records($onadb, 'configurations', array('host_id' => $host['id'], 'configuration_type_id' => $type['id']));
    if ($status or !$rows) {
        $response = new xajaxResponse();
        $response->addScript("alert('Delete failed!');");
        return($response->getXML());
    }

    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if ($form['js']) { $response->addScript($form['js']); }
    return($response->getXML());
}
















?>
