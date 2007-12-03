<?

// Do some HTML headers before printing anything
header("Cache-control: private");

$year = date('Y');

// Set up a generic where clause
$where = 'id > 0';

// Start getting various record counts
list ($status, $host_count, $records)       = db_get_records($onadb, 'hosts', $where, "", 0);
list ($status, $dns_count, $records)        = db_get_records($onadb, 'dns', $where, "", 0);
list ($status, $interface_count, $records)  = db_get_records($onadb, 'interfaces', $where, "", 0);
list ($status, $domain_count, $records)     = db_get_records($onadb, 'domains', $where, "", 0);
list ($status, $subnet_count, $records)     = db_get_records($onadb, 'subnets', $where, "", 0);
list ($status, $pool_count, $records)       = db_get_records($onadb, 'dhcp_pools', $where, "", 0);
list ($status, $block_count, $records)      = db_get_records($onadb, 'blocks', $where, "", 0);


// The following checks with the opennetadmin server to see what the most current version is.
// It will do this each time the interface is opened so the traffic should be very minimal.
@ini_set('user_agent',$_SERVER['HTTP_USER_AGENT']);
//$onachkserver = @gethostbynamel('opennetadmin.com');
$onachkserver[0] = 'opennetadmin.com';
if ($onachkserver[0]) {
    $old = @ini_set('default_socket_timeout', 2);
    $file = @fopen("http://{$onachkserver[0]}/check_version.php", "r");
    @ini_set('default_socket_timeout', $old);
}
$onaver = "Unable to determine";
if ($file) {
    while (!feof ($file)) {
        $buffer = trim(fgets ($file, 4096));
        $onaver = $buffer;
    }
    fclose($file);
}
if ($conf['version'] == $onaver) {
    $versit = "<img src='{$images}/silk/accept.png'> You are on the most current version! ({$onaver})<br>";
}
else {
    $sty='fail';
    if ($onaver == "Unable to determine") $sty='_unknown';
    $versit = "<div class='version_check{$sty}'><img src='{$images}/silk/exclamation.png'> You are NOT on the most current version<br>Your version = {$conf['version']}<br>Latest version = {$onaver}</div>";
}

$motdfile = $base.'/motd.txt';
if (file_exists($motdfile)) {
    printmsg("I tried file: {$base}/motd.txt",0);
    $MOTD = file_get_contents($motdfile);
}

// Lets start building the page!
print <<<EOL
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<!-- This web site is copyrighted (c) {$year} -->
<html>
<head>
    <title>{$conf['title']}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="{$baseURL}/include/html_style_sheet.inc.php">
    <link rel="shortcut icon" type="image/ico" href="{$images}/favicon.ico">
    <script type="text/javascript" src="{$baseURL}/include/js/global.js" language="javascript"></script>
{$conf['html_headers']}


</head>
<body style="overflow: hidden;" bgcolor="{$color['bg']}" link="{$color['link']}" alink="{$color['alink']}" vlink="{$color['vlink']}">

    <!-- Top (Task) Bar -->
    <div class="bar" id="bar_top">
        <div style="position: absolute; font-size: 8px; top: 1px; z-index: 2; right: 5px;">&copy; {$year} OpenNetAdmin - {$conf['version']}</div>
        <!-- Left Side -->
        <div class="bar-left">

            <!-- Button to open the "Start Menu" (Application Links) -->
            <span class="topmenu-item" id="menu-apps-item">
                <a id="menu-apps-button"
                   class="button"
                ><img style="vertical-align: middle;" src="{$images}/silk/house.png" border="0" />&nbsp;Start&nbsp;</a>
            </span>

            <!-- Quick Search -->
            <span class="topmenu-item" id='menu-qsearch-item'>
                <form id="qsearch_form" onSubmit="xajax_window_submit('search_results', xajax.getFormValues('qsearch_form')); return false;">
                    <input type="hidden" name="search_form_id" value="qsearch_form">
                    <input id="qsearch"
                           accesskey="q"
                           class="edit"
                           style="width: 150px;"
                           type="text"
                           title="Quick Search for IP, MAC, DNS"
                           value="Quick Search..."
                           name="q"
                           maxlength="100"
                           onFocus="this.value='';"
                    >
                    <div id="suggest_qsearch" class="suggest"></div>
                    <input type="image"
                           src="{$images}/silk/bullet_go.png"
                           title="Search"
                           class="act"
                           style="vertical-align: middle;"
                    >

                    <!-- Advanced Search Link -->
                    <a title="Advanced search" class="act"
                       onClick="toggle_window('app_advanced_search');"
                    ><img style="vertical-align: middle;" src="{$images}/silk/application_form_magnify.png" border="0" /></a>
                </form>
            </span>

            <!-- Task Bar (i.e. Window List) -->
            <span class="topmenu-item" style="border-right: 1px solid {$color['border']};">&nbsp;</span>
            <!-- I set fixed position here so it would not wrap to the next line -->
            <span class="topmenu-item" style="position: fixed;" id="menu-window-list">&nbsp;</span>

        </div>

        <!-- Right Side -->
        <div class="bar-right">
            <span class="topmenu-item"
                  title="Display system messages"
                  id="sys_alert"
                  style="visibility: hidden;padding: 0px;"
                  onClick="wwTT(this, event,
                                            'id', 'tt_sys_alert',
                                            'type', 'static',
                                            'delay', 0,
                                            'styleClass', 'wwTT_qf',
                                            'direction', 'southwest',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>sys_alert,id=>tt_sys_alert\');'
                                           );"
            ><img src="{$images}/silk/comment.png" border="0" /></span>

            <span class="topmenu-item" style="cursor: pointer;" title="Display user info" onClick="toggle_window('app_user_info');">
                <img style="vertical-align: middle;" src="{$images}/silk/user_gray.png" border="0" />
            </span>
            <input id="login_userid"
                    title="Current logged in user, click to change"
                    class="edit"
                    type="text"
                    value="{$_SESSION['ona']['auth']['user']['username']}"
                    name="login_userid"
                    size="12"
                    onkeypress="if (event.keyCode == 9 || event.keyCode == 13) { el('getpass').focus(); }"
                    onclick="wwTT(this, event,
                                        'id', 'tt_loginform',
                                        'type', 'static',
                                        'delay', 0,
                                        'styleClass', 'wwTT_qf',
                                        'direction', 'south',
                                        'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>loginform,id=>tt_loginform\');'
                                        );"
            >


            <span class="topmenu-item" style="cursor: pointer;" title="Open online help" onClick="document.location = '{$_ENV['help_url']}'; /* FIXME: Open help in an iframe in a window */">
                <img style="vertical-align: middle;" title="Global help index" src="{$images}/silk/help.png" border="0" />
            </span>
            <span class="topmenu-item" style="cursor: pointer;" title="Logout" onClick="var doit=confirm('Are you sure you want to logout?'); if (doit == true) document.location = 'logout.php';">
                <img style="vertical-align: middle;" title="Logout" src="{$images}/silk/door_out.png" border="0" />
            </span>

            &nbsp;
        </div>
    </div>

    <div id="trace_history" style="font-size: smaller; border-style: solid; border-width: 0px 1px 1px 1px; background-color: #EDEEFF;white-space: nowrap;">&nbsp;Trace:</div>

    <!-- Workspace div -->
    <div id="content_table" height="100%" class="theWholeBananna">

        <!-- Parent element for all "windows" -->
        <span id="window_container"></span>&nbsp;

        <!-- FORMATTING TABLE -->
        <div valign="center" align="center" style="{$style['content_box']};padding-left: 8px;">
        <table cellspacing="0" border="0" cellpadding="0"><tr>

            <!-- START OF FIRST COLUMN OF SMALL BOXES -->
            <td nowrap="true" valign="top" style="padding: 15px;">

                <b>Record Counts</b>
                <table border=1 style="border-collapse: collapse;border-color: #999999;">
                    <tr><td>Subnets</td><td>{$subnet_count}</td>
                    <tr><td>Hosts</td><td>{$host_count}</td>
                    <tr><td>Interfaces</td><td>{$interface_count}</td>
                    <tr><td>DNS Records</td><td>{$dns_count}</td>
                    <tr><td>DNS Domains</td><td>{$domain_count}</td>
                    <tr><td>DHCP Pools</td><td>{$pool_count}</td>
                    <tr><td>Blocks</td><td>{$block_count}</td>
                </table>

            <!-- END OF FIRST COLUMN OF SMALL BOXES -->
            </td>

            <!-- START OF SECOND COLUMN OF SMALL BOXES -->
            <td valign="top" style="padding: 15px; border-right: 1px solid #777777; border-left: 1px solid #777777;">

                If you are wondering where to start,<br>
                try one of these tasks:<br>
                <a title="Add DNS domain"
                class="act"
                onClick="xajax_window_submit('edit_domain', ' ', 'editor');"
                ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
                <a title="Add DNS domain"
                class="act"
                onClick="xajax_window_submit('edit_domain', ' ', 'editor');"
                >Add a DNS domain</a>&nbsp;
                <br>
                <a title="Add subnet"
                class="act"
                onClick="xajax_window_submit('edit_subnet', ' ', 'editor');"
                ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
                <a title="Add subnet"
                class="act"
                onClick="xajax_window_submit('edit_subnet', ' ', 'editor');"
                >Add a new subnet</a>&nbsp;
                <br>
                <a title="Add host"
                class="act"
                onClick="xajax_window_submit('edit_host', ' ', 'editor');"
                ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
                <a title="Add host"
                class="act"
                onClick="xajax_window_submit('edit_host', ' ', 'editor');"
                >Add a new host</a>&nbsp;
                <br>
                <a title="Advanced search" class="act"
                       onClick="toggle_window('app_advanced_search');"
                    ><img style="vertical-align: middle;" src="{$images}/silk/application_form_magnify.png" border="0" /></a>&nbsp;
                <a title="Advanced search"
                class="act"
                onClick="toggle_window('app_advanced_search');"
                >Perform a search</a>&nbsp;

            <!-- END OF SECOND COLUMN OF SMALL BOXES -->
            </td>

            <!-- START OF THIRD COLUMN OF SMALL BOXES -->
            <td valign="top" style="padding: 15px;">
                {$versit}
                <br>
                <ul>
                <li>If you need further assistance, look for the <img src='{$images}/silk/help.png'> icon<br>
                in the title bar of windows.<br></li>
                <li>You can also try the main help index located <a href='{$_ENV['help_url']}'>here</a><br></li>
                </ul>
            </td>
            <!-- END OF THIRD COLUMN OF SMALL BOXES -->
        </tr>
        </table>

        <div>{$MOTD}</div>

        </div>
        <!-- END OF TOP SECTION -->
    </div>


<!-- Javascript for the Task Bar -->
<script type="text/javascript"><!--
    /* Setup the quick search */
    suggest_setup('qsearch', 'suggest_qsearch');

    /* Code to auto-populate the "Task Bar" once a second */
    function update_task_bar(_parent, _bar) {
        var nodes, html, icon_active, icon_inactive, update;

        icon_active   = '{$images}/silk/application_lightning.png';
        icon_inactive = '{$images}/silk/application.png'

        /* Loop through each child node and display a "button" for it */
        update = 0;
        html = '';
        nodes = _parent.childNodes;
        for (var i=0; i<nodes.length; i++) {
            var _title = (el(nodes[i].id + '_title').innerHTML).trim();
            if (_title.indexOf('Work Space') != 0) {
                /* Choose the icon to use based on the windows visibility */
                var icon = icon_inactive;
                if (nodes[i].style.visibility == 'visible')
                    icon = icon_active;

                /* Force a bar redraw if the icon has changed */
                var _el = el(nodes[i].id + '_taskbar');
                if (_el && !_el.innerHTML.match(icon))
                    update = 1;

                /* Add a button for the window */
                html += '<a class="button" ' +
                        '   id="' + nodes[i].id + '_taskbar" ' +
                        '   title="' + _title + '" ' +
                        '   onClick="toggle_window(\'' + nodes[i].id + '\');" ' +
                        '><img style="vertical-align: middle;" src="' + icon + '" border="0" />&nbsp;' + _title + '&nbsp;</a>&nbsp;';
            }
        }

        /* Update the bar if it's changed (beware, the dark arts of the sith were summoned to write the following code) */
        if ( (update == 1) || (html.replace(/(<([^>]+)>)/g,"") != (_bar.innerHTML).replace(/(<([^>]+)>)/g,"")) ) {
            _bar.innerHTML = html;
        }
    }

    /* This checks to make sure that the work_space fits within the window poperly.  it will adjust the content size as you adjust the window size. */
    setInterval('if (el(\'work_space_table\')) { ' +
        'el(\'work_space_table\').style.width = \'100%\';' +
        'var my_height = document.body.clientHeight - el(\'bar_top\').clientHeight - el(\'trace_history\').clientHeight;' +
        'el(\'work_space\').style.height = my_height + \'px\';' +
        'el(\'work_space_content\').style.height = (my_height - el(\'work_space_title\').offsetHeight - 4 ) + \'px\'; }', 500);

    /* Keep the taskbar items up to date */
    setInterval('update_task_bar(el(\'window_container\'), el(\'menu-window-list\'));', 1000);

    /* Call the process_alerts function to look for alerts to display at a regular interval*/
    setInterval('xajax_window_submit(\'process_alerts\', \'fake=>junk\');', 300000);

    /* Go ahead and process_alerts on the initial load */
    xajax_window_submit('process_alerts', 'fake=>junk');

    /* Setup mouse handlers for the "Start" button */
    var _button = el('menu-apps-button');
    _button.onclick =
        function(ev) {
            if (!ev) ev = event;
            /* Get info about the button */
            var button_top    = calcOffset(el('menu-apps-button'), 'offsetTop');
            var button_left   = calcOffset(el('menu-apps-button'), 'offsetLeft');
            var button_height = el('menu-apps-button').offsetHeight;
            /* Create the tool-tip menu */
            wwTT(this, ev,
                 'id', 'start_menu',
                 'type', 'velcro',
                 'x', button_left,
                 'y', button_top + button_height,
                 'width', 200,
                 'delay', 0,
                 'lifetime', 1000,
                 'styleClass', 'wwTT_ona_menu',
                 'javascript', 'el(\'start_menu\').style.visibility = \'hidden\'; xajax_window_submit(\'tooltips\', \'tooltip=>start_menu,id=>start_menu\');'
            );
        };

    // Populate the trace_history with anything that might already be in the session
    el('trace_history').innerHTML=xajax_window_submit('work_space', 'return_html=>1', 'rewrite_history');


--></script>

<!-- Set some preferences FIXME: This shouldn't be here! -->
<script type="text/javascript"><!--
    if (getcookie('pref_bg_repeat')) el('content_table').style.backgroundRepeat = getcookie('pref_bg_repeat');
    if (getcookie('pref_bg_url')) el('content_table').style.backgroundImage = 'url(\'' + getcookie('pref_bg_url') + '\')';
--></script>

<!-- Side toolbar -->
<div nowrap style="position: absolute;top: 90px;right: 1px;z-index: 10;background: #E3E3F0;-moz-border-radius-topleft:4px;-moz-border-radius-bottomleft:4px;">
    <div style="float:left;padding: 5px 2px;" onclick="toggleBox('ipcalc_content');">
    <img src="{$images}/silk/calculator.png" title="BASIC IP calculator" />
    </div>
    <div id="ipcalc_content" style="visibility: hidden;display:none;background: #E3E3F0;padding: 5px;-moz-border-radius-topleft:4px;-moz-border-radius-bottomleft:4px;">
        <form id="ipcalc_form" onsubmit="return false;">
            IP: <input type="text" name="ip" />
            Mask: <input type="text" name="mask" />
                <input class="edit" type="button"
                    name="submit"
                    value="Go"
                    onClick="xajax_window_submit('ipcalcgui', xajax.getFormValues('ipcalc_form'));"
                >
        </form>
        <span><pre style="font-family: monospace;font-size: medium;" id="ipcalc_data"></pre></span>
    </div>
</div>




</body>
</html>
EOL;

// Process any search that was passed
if ($search) {
    print <<<EOL
<script type="text/javascript"><!--
    el('qsearch').value = '{$search}';
    xajax_window_submit('search_results', xajax.getFormValues('qsearch_form'));
--></script>
EOL;
}

?>