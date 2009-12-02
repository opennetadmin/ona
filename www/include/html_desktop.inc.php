<?php

// Do some HTML headers before printing anything
header("Cache-control: private");

$year = date('Y');

// If there is a message of the day file, display it.
$motdfile = $base.'/local/config/motd.txt';
if (file_exists($motdfile)) {
    printmsg("INFO => Displaying MOTD: {$motdfile}",1);
    $MOTD = file_get_contents($motdfile);
}

// Build a select option list for the context names
foreach (array_keys($ona_contexts) as $entry) {
    $selected = "";
    // If this entry matches the record you are editing, set it to selected
    if ($entry == $self['context_name']) { $selected = "SELECTED=\"yes\""; }
    if ($entry) {$context_list .= "<option {$selected} value=\"{$entry}\">{$entry}</option>\n";}
}

// Lets start building the page!
print <<<EOL
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<!-- This web site is copyrighted (c) {$year} -->
<html>
<head>
    <title>{$conf['title']}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="{$baseURL}/include/html_style_sheet.inc.php">
    <link rel="shortcut icon" type="image/ico" href="{$images}/favicon.ico">
    <script type="text/javascript" src="{$baseURL}/include/js/global.js" language="javascript"></script>
    {$conf['html_headers']}
</head>
<body style="overflow: hidden;" bgcolor="{$color['bg']}" link="{$color['link']}" alink="{$color['alink']}" vlink="{$color['vlink']}">

    <!-- Top (Task) Bar -->
    <div class="menubar" id="bar_topmenu" style="background-color: {$self['context_color']}">
        <!-- Button to open the "Start Menu" (Application Links) -->
        <div id="menu-apps-item" class="main_menu_button" onmouseover="xajax_window_submit('menu_control', ' ');">Menu</div>
    </div>

    <div class="bar" id="bar_top" onmouseover="ona_menu_closedown();" style="background-color: {$self['context_color']}">
        <!-- Left Side -->
        <div class="bar-left">
            <!-- Button to open the "search dialog" -->
            <span class="topmenu-item" title="Advanced search" id="search-item" onClick="xajax_window_submit('search_results', 'search_form_id=>subnet_search_form'); return false;">
                <a id="search-button"
                   class="button"
                ><img style="vertical-align: middle;" src="{$images}/silk/application_form_magnify.png" border="0" />&nbsp;Search&nbsp;</a>
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


            <span id="login_userid" class="topmenu-item"
                    title="Current logged in user, click to change"
                    onclick="var button_left   = calcOffset(el('login_userid'), 'offsetLeft');
                             wwTT(this, event,
                                        'id', 'tt_loginform',
                                        'type', 'static',
                                        'x', button_left - 75,
                                        'y', 1,
                                        'delay', 0,
                                        'styleClass', 'wwTT_login',
                                        'direction', 'south',
                                        'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>loginform,id=>tt_loginform\');'
                                        );"
            ><a class="button" style="font-weight:bold;"><img style="vertical-align: middle;" src="{$images}/silk/user_go.png" border="0" /> <span id="loggedin_user">{$_SESSION['ona']['auth']['user']['username']}</span> <span style="font-weight: normal;font-size: xx-small;">[Change]</span> </a>
            </span>

            <span id="loggedin_info" class="topmenu-item" style="cursor: pointer;" title="Click to display user info." onClick="toggle_window('app_user_info');">
                <img style="vertical-align: middle;" src="{$images}/silk/user_gray.png" border="0" />
            </span>

            <span id="logoutbutton" class="topmenu-item" style="cursor: pointer;padding-right: 5px;" title="Logout" onClick="var doit=confirm('Are you sure you want to logout?'); if (doit == true) document.location = 'logout.php';">
                <img style="vertical-align: middle;" title="Switch to Guest user (Logout)" src="{$images}/silk/door_out.png" border="0" />
            </span>
        </div>
    </div>

    <div id="menu_bar_top" style="display: none; float: left;width: 100%; font-size: smaller; background-color: #AABBFF;white-space: nowrap;font-weight: bold;border-left: 1px solid #555555;border-right: 1px solid #555555;border-bottom: 1px solid #555555;"></div>

    <div id="trace_history" style="font-size: smaller; border-color: #555555;border-style: solid; border-width: 0px 1px 1px 1px; background-color: #EDEEFF;white-space: nowrap;">&nbsp;Trace:</div>
EOL;

// If we have more than one context defined, lets create a context selector
if (count($ona_contexts) > 1) {
print <<<EOL
    <div style="position: fixed;width: 100%;z-index: 10;">
    <center><div style="max-height: 1px;">
    <table class="context_select_table" cellspacing="0" border="0" cellpadding="0" style="background-color: {$self['context_color']};">
        <tr id="current_context" title="Click to change context" onclick="this.style.display='none'; el('change_context').style.display='';">
            <td onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='';">Context: {$self['context_name']}</td>
        </tr>
        <tr id="change_context" style="display: none;">
            <td>
                <img title="Cancel context change"
                    src="{$images}/silk/bullet_delete.png"
                    border="0"
                    onclick="el('change_context').style.display='none'; el('current_context').style.display='';"
                /> Select Context:
                <form id="context_select_form">
                    <select id="context_select"
                            class="edit"
                            name="context_select"
                            onchange="el('change_context').style.display='none'; el('current_context').style.display='';xajax_window_submit('tooltips', xajax.getFormValues('context_select_form'), 'switch_context');">
                            {$context_list}
                    </select>
                </form>
            </td>
        </tr>
    </table>
    </div></center>
    </div>
EOL;
}

print <<<EOL
    <!-- Workspace div -->
    <div id="content_table" class="theWholeBananna">

        <!-- Parent element for all "windows" -->
        <span id="window_container"></span>

        <div id="appbanner" style="font-size: xx-small;text-align:center;padding-top:1px;"></div>

        <!-- FORMATTING TABLE -->
        <div id="desktopmodules" valign="center" align="center" style="padding-left: 8px;overflow-x: auto;">
        <table cellspacing="0" border="0" cellpadding="0" width="100%" style="margin-top: 7px;"><tr>

            <!-- START OF FIRST COLUMN OF SMALL BOXES -->
            <td nowrap="true" valign="top" style="padding: 15px;">
EOL;

    $extravars['window_name'] = 'html_desktop';
    list($wspl, $wsjs) = workspace_plugin_loader('desktop_versioncheck',$record,$extravars);
    print($wspl);
    list($wspl, $wsjs) = workspace_plugin_loader('desktop_counts',$record,$extravars);
    print($wspl);
    list($wspl, $wsjs) = workspace_plugin_loader('desktop_firsttasks',$record,$extravars);
    print($wspl);




print <<<EOL



            <!-- END OF FIRST COLUMN OF SMALL BOXES -->
            </td>


        </tr>

            <td nowrap="true" valign="top" style="padding: 15px;">
EOL;

    // Get all the plugin based worspace items
    $wspl_list = plugin_list('wspl_item');

    // Load all the dynamic plugins
    foreach ($wspl_list as $p) {
        list($wspl, $wsjs) = workspace_plugin_loader($p['path'],$record,$extravars);
        print($wspl);
        $ws_plugin_js .= $wsjs;
    }

print <<<EOL
            </td>
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

<script>
    var desktop_height = document.body.clientHeight - el('bar_top').clientHeight - el('trace_history').clientHeight - el('appbanner').clientHeight;
    if (browser.isIE) {
        desktop_height -= 20;
    }

    /* Finally reposition/resize the window, hide any overflow, and bring it up behind other windows. */
    el('desktopmodules').style.height = desktop_height + 'px';

    // process any workspace plugin javascript
    {$ws_plugin_js}
</script>
</body>
</html>
EOL;


?>