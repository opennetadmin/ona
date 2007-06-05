<?

// If we get called directly (via launch_app.php), call work_space, and tell it to call us back.
// Yes, it's more of Brandon's black magic ;)
$window['js'] = <<<EOL
    removeElement('{$window_name}');
    xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'host_id=>{$_REQUEST['host_id']}\', \'display_list\')');
EOL;




//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays a list of config text records in the work_space div.
//   A host_id and config_type_id must be supplied.
//////////////////////////////////////////////////////////////////////////////
function ws_display_list($window_name, $form='') {
    global $conf, $self, $onadb, $baseURL;
    global $images, $color, $style;
    $html = '';
    $js = '';

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

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = "Configs: {$host['name']}";
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // Check permissions
//    if (! (auth('host_config_admin') and authlvl($host['lvl'])) ) {
 //       $response = new xajaxResponse();
//        $response->addScript("alert('Permission denied!');");
//        return($response->getXML());
//    }

    // Load the config type
    list($status, $rows, $type) = ona_get_config_type_record(array('id' => $form['type_id']));
    if ($status or !$rows) {
        $response = new xajaxResponse();
        $response->addScript("alert('ERROR => Config type was empty!');");
        return($response->getXML());
    }

    // Display the host's archived configs

    // Generate a SQL query to list configs to display
    $q = "SELECT configurations.id,
                 configuration_types.name,
                 configurations.md5_checksum,
                 configurations.ctime
            FROM configurations, configuration_types
           WHERE configurations.configuration_type_id = configuration_types.id
             AND configurations.host_id = " . $onadb->qstr($host['id']) . "
             AND configurations.configuration_type_id = " . $onadb->qstr($type['id']) . "
        ORDER BY configuration_types.name DESC,
                 configurations.ctime DESC";

//                 to_char(configurations.ctime, 'MM/DD/YYYY HH:MI AM') as ctime,


    // Execute the SQL
    $rs = $onadb->Execute($q);

    // Die if it didn't work
    if ($rs === false) {
        $response->addScript("alert('ERROR => SQL failed: {$self['error']}');");
        return($response->getXML());
    }

    // Escape data for display in html
    foreach(array_keys($host) as $key) { $host[$key] = htmlentities($host[$key], ENT_QUOTES); }

    // List the available configs in a pseudo table
    $html .= <<<EOL
        <div style="margin: 10px 20px;">
            <table width="100%" cellspacing="0" border="0" cellpadding="2" align="left">

            <tr>
                <td colspan="7" class="list-header" style="padding: 2px 4px; {$style['borderT']}; {$style['borderL']}; {$style['borderR']}; background-color: #FFFFFF;">
                    <b>Configuration archives for:</b>
                    <a title="View host. ID: {$host['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$host['id']}\', \'display\')');"
                    >{$host['name']}</a
                    >.<a title="View domain. ID: {$host['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$host['domain_id']}\', \'display\')');"
                    >{$host['domain_fqdn']}</a>
                </td>
            </tr>

            <tr>
                <td class="list-header" style="padding-right: 4px; width: 20px; {$style['borderL']}; ">Old </td>
                <td class="list-header" style="padding-right: 4px; width: 20px;">New </td>
                <td class="list-header" style="padding-right: 8px;">Date </td>
                <td class="list-header" style="padding-right: 8px;">Type </td>
                <td class="list-header" style="padding-right: 8px;">MD5 </td>
                <td class="list-header" style="padding-right: 8px;">Size (chars) </td>
                <td class="list-header" style="padding-right: 8px; text-align: right; {$style['borderR']}; ">Action</td>
            </tr>
EOL;
    $id = 0;
    while (!$rs->EOF) {
        $id++; // Counter used in javascript
        // Color different types of configs differently
        // FIXME (MP) there will be more config types.. do some sort of dynamic coloring based on anything in the type table
        $color = "#FFFFFF";
        if ($rs->fields['name'] == "IOS_CONFIG")
            $color = "#DEE7EC";
        else if ($rs->fields['name'] == "IOS_VERSION")
            $color = "#ECDDDD";

        // Escape data for display in html
        foreach(array_keys($rs->fields) as $key) { $rs->fields[$key] = htmlentities($rs->fields[$key], ENT_QUOTES); }

        // Get the length of the configuration
        // FIXME (MP) figure out why objects are not returning for these properly.. fix this formatting.
        $confsize = strlen($rs->fields['config_body']);
        $timeformat = date('m/d/Y h:i A',$rs->fields['ctime']);

        $html .= <<<EOL
            <tr>
                <td bgcolor="{$color}" class="borderBL" style="{$style['borderL']}; padding-right: 4px; width: 20px;"
                  ><input id="old{$id}" name="old" type="radio" value="{$rs->fields['id']}"
                    onClick="
                        var tmp = 1; var obj = el('new' + tmp);
                        while (obj) {
                            obj.style.visibility = (tmp <= {$id}) ? 'visible' : 'hidden';
                            if (tmp > {$id}) obj.checked = false;
                            obj = el('new' + tmp++);
                        }"
                ></td>
                <td bgcolor="{$color}" class="borderB"  style="padding-right: 4px; width: 20px;"><input id="new{$id}" style="visibility: hidden;" name="new" type="radio" value="{$rs->fields['id']}"></td>
                <td bgcolor="{$color}" class="borderB"  style="padding-right: 6px;">{$rs->fields['ctime']}</td>
                <td bgcolor="{$color}" class="borderB"  style="padding-right: 6px;">{$rs->fields['name']}</td>
                <td bgcolor="{$color}" class="borderB"  style="padding-right: 6px;">{$rs->fields['md5_checksum']}</td>
                <td bgcolor="{$color}" class="borderB"  style="padding-right: 6px;">??</td>
                <td bgcolor="{$color}" class="borderBR" style="{$style['borderR']};text-align: right; padding-right: 4px;">
                    <a title="View config: {$rs->fields['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'config_id=>{$rs->fields['id']}\', \'display_config\')');"
                    ><img src="{$images}/silk/zoom.png" alt="View config" border="0"></a>&nbsp;

                    <a title="Download config" class="act" target="null" href="{$baseURL}/apps/config_diff.php?text_id={$rs->fields['id']}&download=1"
                    ><img src="{$images}/silk/disk.png" alt="Download config" border="0"></a>&nbsp;

                    <form id="{$window_name}_list_{$rs->fields['id']}"
                        ><input type="hidden" name="config_id" value="{$rs->fields['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>

                    <a title="Delete config"
                       class="nav"
                       onClick="var doit=confirm('Are you sure you want to delete this config record?');
                                if (doit == true)
                                    xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_list_{$rs->fields['id']}'), 'delete_config');"
                    ><img src="{$images}/silk/delete.png" alt="Delete config" border="0"></a>&nbsp;
                </td>
            </tr>
EOL;
        $rs->MoveNext();
    }
    $html .= <<<EOL

            <tr>
            <td colspan="3" class="list-header" style="{$style['borderL']};">
            <input type="button"
                name="compare"
                value="Compare"
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
                      xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'old_id=>' + OLD.value + ',new_id=>' + NEW.value + '\', \'display_diff\')');
            ">
            </td>
            <td colspan="4" align="right" class="list-header" style="{$style['borderR']};">
                <form id="{$window_name}_list_{$host['id']}"
                    ><input type="hidden" name="type_id" value="{$type['id']}"
                    ><input type="hidden" name="host_id" value="{$host['id']}"
                    ><input type="hidden" name="js" value="{$refresh}"
                ></form>

                <input type="button"
                    name="delete"
                    value="Delete All"
                    class="button"
                    onClick="var doit=confirm('Are you sure you want to delete ALL of these config records?');
                                if (doit == true)
                                    xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_list_{$host['id']}'), 'delete_configs');">
            </td>
            </tr>

            </table>
        </div>
EOL;


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}
















//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_config()
//
// Description:
//   Displays a config text record in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display_config($window_name, $form='') {
    global $conf, $self, $onadb, $baseURL;
    global $images, $color, $style;
    $html = '';
    $js = '';

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

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = "Config text ({$host['name']})";
       array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // Check permissions
//    if (! (auth('host_config_admin') and authlvl($host['LVL'])) ) {
//        $response = new xajaxResponse();
//        $response->addScript("alert('Permission denied!');");
//        return($response->getXML());
//    }

    // Display the config text

    // Escape data for display in html
    foreach(array_keys($host) as $key) { $host[$key] = htmlentities($host[$key], ENT_QUOTES); }
    foreach(array_keys($config) as $key) { $config[$key] = htmlentities($config[$key], ENT_QUOTES); }


    // Build html to display the config
    $html .= <<<EOL
        <div style="margin: 10px 20px; float: left; width: 90%; background-color: {$color['bar_bg']}; border: 1px solid">
            <table cellspacing="0" border="0" cellpadding="2" align="left">
                <tr>
                    <td style="font-weight: bold;">Host:</td>
                    <td>
                        <a title="View host. ID: {$host['id']}"
                           class="nav"
                           onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$host['id']}\', \'display\')');"
                        >{$host['name']}</a
                        >.<a title="View domain. ID: {$host['domain_id']}"
                             class="domain"
                             onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$host['domain_id']}\', \'display\')');"
                        >{$host['domain_fqdn']}</a>
                    </td>
                </tr>
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
                <tr>
                    <td style="font-weight: bold;">Actions:</td>
                    <td>
                        <a title="Download config" class="act" target="null" href="{$baseURL}/apps/config_diff.php?text_id={$config['id']}&download=1"
                        ><img src="{$images}/silk/disk.png" alt="Download config" border="0"></a>&nbsp;

                        <form id="{$window_name}_display_{$config['id']}"
                            ><input type="hidden" name="config_id" value="{$config['id']}"
                            ><input type="hidden" name="js" value="{$refresh}"
                        ></form>

                        <a title="Delete config"
                           class="nav"
                           onClick="var doit=confirm('Are you sure you want to delete this config record?');
                                    if (doit == true)
                                        xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_display_{$config['id']}'), 'delete_config');"
                        ><img src="{$images}/silk/delete.png" alt="Delete config" border="0"></a>&nbsp;
                    </td>
                </tr>
            </table>
        </div>
        <div style="margin: 10px 20px; float: left; width: 90%;">
            <pre>{$config['config_body']}</pre>
        </div>
EOL;


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}















//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_diff()
//
// Description:
//   Displays a config text record in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display_diff($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style, $baseURL;
    $html = '';
    $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the old config text record
    list($status, $rows, $old) = ona_get_config_record(array('id' => $form['old_id']));
    if (!$old['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Configuration text record doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Load the new config text record
    list($status, $rows, $new) = ona_get_config_record(array('id' => $form['new_id']));
    if (!$new['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Configuration text record doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Load the asscoiated old host record
    list($status, $rows, $old_host) = ona_find_host($old['host_id']);
    if (!$old_host['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Load the asscoiated new host record
    list($status, $rows, $new_host) = ona_find_host($new['host_id']);
    if (!$new_host['id']) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>Host doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = "Config diff ({$form['old_id']} / {$form['new_id']})";
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Create some javascript to refresh the current page
    $refresh = htmlentities(str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']), ENT_QUOTES);
    $refresh = "xajax_window_submit('work_space', '{$refresh}');";

    // Check permissions
//    if (! (auth('host_config_admin') and authlvl($old_host['LVL']) and authlvl($new_host['LVL'])) ) {
//        $response = new xajaxResponse();
//        $response->addScript("alert('Permission denied!');");
//        return($response->getXML());
//    }

    // Display the config text diff

    $html .= <<<EOL
        <div style="margin: 10px 20px;">

            <!-- Diff Headers -->
            <table width="100%" cellspacing="0" border="0" cellpadding="2" align="left">
            <tr>
            <td align="left" style="background-color: {$color['bar_bg']};">
                <table cellspacing="0" border="0" cellpadding="2" align="left">
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
                               onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'config_id=>{$old['id']}\', \'display_config\')');"
                            ><img src="{$images}/silk/zoom.png" alt="View config" border="0"></a>&nbsp;

                            <a title="Download config" class="act" target="null" href="{$baseURL}/apps/config_diff.php?text_id={$old['id']}&download=1"
                            ><img src="{$images}/silk/disk.png" alt="Download config" border="0"></a>&nbsp;
                        </td>
                    </tr>
                </table>
            </td>
            <td align="left" style="background-color: {$color['bar_bg']};">
                <table cellspacing="0" border="0" cellpadding="2" align="left">
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
                               onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'{$window_name}\', \'config_id=>{$new['id']}\', \'display_config\')');"
                            ><img src="{$images}/silk/zoom.png" alt="View config" border="0"></a>&nbsp;

                            <a title="Download config" class="act" target="null" href="{$baseURL}/apps/config_diff.php?text_id={$new['id']}&download=1"
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
    $html .= html_diff($old['config_body'], $new['config_body'], "Older text", "Newer text", 0);

    $html .= <<<EOL
            </td>
            </tr>
            </table>
        </div>
EOL;

    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}














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
