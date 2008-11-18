<?php

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
list ($status, $vlan_campus_count, $records) = db_get_records($onadb, 'vlan_campuses', $where, "", 0);
list ($status, $config_archive_count, $records) = db_get_records($onadb, 'configurations', $where, "", 0);


// The following checks with the opennetadmin server to see what the most current version is.
// It will do this each time the interface is opened so the traffic should be very minimal.
// Dont perform a version check if the user has requested not to
if (!$conf['skip_version_check']) {
    @ini_set('user_agent',$_SERVER['HTTP_USER_AGENT']."-----".$conf['version']);
    //$onachkserver = @gethostbynamel('opennetadmin.com');
    $onachkserver[0] = 'opennetadmin.com';
    if ($onachkserver[0]) {
        // use fsockopen to test that the connection works, if it does, open using fopen
        // for some reason the default_socket_timeout was not working properly.
        $fsock = @fsockopen("tcp://{$onachkserver[0]}", 80, $errNo, $errString, 2);
        if ($fsock) {
            $old = @ini_set('default_socket_timeout', 2);
            $file = @fopen("http://{$onachkserver[0]}/check_version.php", "r");
            @ini_set('default_socket_timeout', $old);
        }
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
        $versit = "<img src='{$images}/silk/accept.png'> You are on the official stable version! ({$onaver})<br/><br/>";
    }
    else {
        $sty='fail';
        if ($onaver == "Unable to determine") $sty='_unknown';
        $versit = "<div class='version_check{$sty}'><img src='{$images}/silk/exclamation.png'> You are NOT on the official stable version<br>Your version = {$conf['version']}<br>Official version = {$onaver}</div><br/>";
    }
}

// If there is a message of the day file, display it.
$motdfile = $base.'/local/config/motd.txt';
if (file_exists($motdfile)) {
    printmsg("INFO => Displaying MOTD: {$motdfile}",1);
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
        
        <!-- Left Side -->
        <div class="bar-left">

            <!-- Button to open the "Start Menu" (Application Links) -->
            <span class="topmenu-item" id="menu-apps-item">
                <a id="menu-apps-button"
                   class="button"
                ><img style="vertical-align: middle;" src="{$images}/silk/house.png" border="0" />&nbsp;Tools&nbsp;</a>
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
            <span class="topmenu-item" id="menu-window-list">&nbsp;</span>

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

            <span class="topmenu-item" style="cursor: pointer;" title="Current user: {$_SESSION['ona']['auth']['user']['username']}, Click to display user info." onClick="toggle_window('app_user_info');">
                <img style="vertical-align: middle;" src="{$images}/silk/user_gray.png" border="0" />
            </span>
            <input id="login_userid"
                    title="Current logged in user, click to change"
                    class="edit"
                    type="text"
                    value="{$_SESSION['ona']['auth']['user']['username']}"
                    name="login_userid"
                    size="12"
                    onkeypress="if ((event.keyCode|event.which) == 9|(event.keyCode|event.which) == 13) { setTimeout('el(\'getpass\').focus()',10) }"
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

        <div style="font-size: xx-small;text-align:center;">&copy; {$year} <a href="http://opennetadmin.com">OpenNetAdmin</a> - {$conf['version']}</div>

        <!-- FORMATTING TABLE -->
        <div valign="center" align="center" style="{$style['content_box']};padding-left: 8px;">
        <table cellspacing="0" border="0" cellpadding="0"><tr>

            <!-- START OF FIRST COLUMN OF SMALL BOXES -->
            <td nowrap="true" valign="top" style="padding: 15px;"><br/><canvas id="record_counts_pie" width="150" height="150"></canvas></td>
            <td nowrap="true" valign="top" style="padding: 15px;">

<script type="text/javascript">
  function record_counts_pie(rownum) {
    // Function modified from code posted on http://www.phpied.com/canvas-pie/
    //

    // source data table and canvas tag
    var data_table = document.getElementById('record_counts');
    var td_index = 1; // which TD contains the data
    var canvas = document.getElementById('record_counts_pie');

    // exit if canvas is not supported
    if (typeof canvas.getContext === 'undefined') {
        return;
    }

    // define some colors
    var color = [];
    color[0] = "#bbaaff";
    color[1] = "#ffaaaa";
    color[2] = "#8899ff";
    color[3] = "#ddffaa";
    color[4] = "#aaffee";
    color[5] = "#66ddcc";
    color[6] = "#dd6677";
    color[7] = "#55DD88";

    // get the data[] from the table
    var tds, data = [], value = 0, total = 0, bump = [];
    var trs = data_table.getElementsByTagName('tr'); // all TRs
    for (var i = 0; i < trs.length; i++) {
        tds = trs[i].getElementsByTagName('td'); // all TDs

        if (tds.value === 0) continue; //  no TDs here, move on

        bump[i] = 0;
        if (i == rownum) bump[i] = 10;

        // get the value, update total
        value  = parseFloat(tds[td_index].innerHTML);
        data[i] = value;
        total += value;
    }

    // get canvas context, determine radius and center
    var ctx = canvas.getContext('2d');
    var canvas_size = [canvas.width, canvas.height];
    var radius = Math.min((canvas_size[0]-20), (canvas_size[1]-20)) / 2;
    var center = [canvas_size[0]/2, canvas_size[1]/2];

    var sofar = 0; // keep track of progress

    // clear out the current contents
    ctx.fillStyle = "rgb(255,255,255)";
    ctx.fillRect(0,0,canvas.width,canvas.height);

    // loop through each table row
    for (var piece = 0; piece < trs.length; piece++) {

        var thisvalue = data[piece] / total;

        ctx.beginPath();
        ctx.moveTo(center[0], center[1]); // center of the pie

        ctx.arc(  // draw next arc
            center[0],
            center[1],
            (radius + bump[piece]),
            Math.PI * (- 0.5 + 2 * sofar), // -0.5 sets set the start to be top
            Math.PI * (- 0.5 + 2 * (sofar + thisvalue)),
            false
        );

        ctx.lineTo(center[0], center[1]); // line back to the center
        ctx.closePath();
        ctx.fillStyle = color[piece];
        ctx.fill();

        sofar += thisvalue; // increment progress tracker
    }
}
</script>

                <b>Record Counts</b>
                <table onmouseout="record_counts_pie(99)" id="record_counts" border=1 style="border-collapse: collapse;border-color: #999999;"s>
                    <tr onmouseover="record_counts_pie(0)"><td><a title="List Subnets" onClick="xajax_window_submit('search_results', 'search_form_id=>subnet_search_form');">Subnets</a></td><td>{$subnet_count}</td>
                    <tr onmouseover="record_counts_pie(1)"><td><a title="List Hosts" onClick="xajax_window_submit('search_results', 'search_form_id=>host_search_form');">Hosts</a></td><td>{$host_count}</td>
                    <tr onmouseover="record_counts_pie(2)"><td>Interfaces</td><td>{$interface_count}</td>
                    <tr onmouseover="record_counts_pie(3)"><td>DNS Records</td><td>{$dns_count}</td>
                    <tr onmouseover="record_counts_pie(4)"><td><a title="List DNS Domains" onClick="toggle_window('app_domain_list');">DNS Domains</a></td><td>{$domain_count}</td>
                    <tr onmouseover="record_counts_pie(5)"><td>DHCP Pools</td><td>{$pool_count}</td>
                    <tr onmouseover="record_counts_pie(6)"><td><a title="List Blocks" onClick="xajax_window_submit('search_results', 'search_form_id=>block_search_form');"> Blocks</a></td><td>{$block_count}</td>
                    <tr onmouseover="record_counts_pie(7)"><td><a title="List VLAN Campuses" onClick="xajax_window_submit('search_results', 'search_form_id=>vlan_campus_search_form');">VLAN Campuses</a></td><td>{$vlan_campus_count}</td>
                    <tr onmouseover="record_counts_pie(8)"><td>Config Archives</td><td>{$config_archive_count}</td>
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
                <br>
                <a title="List Hosts"
                class="act"
                onClick="xajax_window_submit('search_results', 'search_form_id=>qsearch_form'); return false;"
                ><img src="{$images}/silk/application_view_detail.png" border="0"></a>&nbsp;
                <a title="List Hosts"
                class="act"
                onClick="xajax_window_submit('search_results', 'search_form_id=>qsearch_form');"
                >List Hosts</a>&nbsp;


            <!-- END OF SECOND COLUMN OF SMALL BOXES -->
            </td>

            <!-- START OF THIRD COLUMN OF SMALL BOXES -->
            <td valign="top" style="padding: 15px;">
                {$versit}
                <ul>
                <li>If you need further assistance, look for the <img src='{$images}/silk/help.png'> icon<br>
                in the title bar of windows.<br></li>
                <li>You can also try the main help index located <a href='{$_ENV['help_url']}'>here</a><br></li>
                </ul>
            </td>
            <!-- END OF THIRD COLUMN OF SMALL BOXES -->
        </tr>
        </table>

        <!-- Print the MOTD info if any -->
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
    setInterval('xajax_window_submit(\'process_alerts\', \'sys_alert=>yes\');', 300000);

    /* Go ahead and process_alerts on the initial load */
    xajax_window_submit('process_alerts', 'sys_alert=>yes');

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

    // Print the nice pie chart!
    record_counts_pie(99);

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
        <span style="font-family: monospace;font-size: medium;" id="ipcalc_data"></span>
    </div>
</div>

EOL;


// Open the work_space that was requested
if ($work_space) {
    // Take the query from the URL and process it for use in the window_submit
    $ws_qry = str_replace('&',',',$_SERVER['QUERY_STRING']);
    $ws_qry = str_replace('=','=>',$ws_qry);
    print <<<EOL
<script type="text/javascript"><!--
    xajax_window_submit('work_space', 'xajax_window_submit(\'{$work_space}\', \'{$ws_qry}\', \'display\')');
--></script>
EOL;
}

// Process any search that was passed
if ($search) {
    print <<<EOL
<script type="text/javascript"><!--
    el('qsearch').value = '{$search}';
    xajax_window_submit('search_results', xajax.getFormValues('qsearch_form'));
--></script>
EOL;
}


print <<<EOL
</body>
</html>
EOL;


?>