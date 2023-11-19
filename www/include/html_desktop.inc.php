<?php

// Do some HTML headers before printing anything
header("Cache-control: private");

global $year;
$ws_plugin_js='';

// If there is a message of the day file, display it.
$motdfile = $base.'/local/config/motd.txt';
$MOTD = '';
if (file_exists($motdfile)) {
    printmsg("INFO => Displaying MOTD: {$motdfile}",1);
    $MOTD = file_get_contents($motdfile);
}

// Build a select option list for the context names
foreach (array_keys($ona_contexts) as $entry) {
    $selected = "";
    // If this entry matches the record you are editing, set it to selected
    if ($entry == $self['context_name']) { $selected = "SELECTED=\"yes\""; }
    if (isset($entry)) {$context_list = "<option {$selected} value=\"{$entry}\">{$entry}</option>\n";}
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
    <script type="text/javascript" src="{$baseURL}/include/js/mousetrap.min.js" language="javascript"></script>
    <script type="text/javascript" src="{$baseURL}/include/js/mousetrap_mappings.js" language="javascript"></script>
    {$conf['html_headers']}
</head>
<body style="overflow: hidden;" bgcolor="{$color['bg']}" link="{$color['link']}" alink="{$color['alink']}" vlink="{$color['vlink']}">

    <!-- Top (Task) Bar -->
    <div class="menubar" id="bar_topmenu" style="background-color: {$self['context_color']}">
        <!-- Button to open the "Start Menu" (Application Links), javascript passes in the workspace name for menu operations -->
        <div id="menu-apps-item" class="main_menu_button" onmouseover="var wsname='FALSE';if (el('work_space')) {var wsname=el('work_space').getAttribute('wsname'); } xajax_window_submit('menu_control', wsname);"><i class="nf nf-md-menu"></i></div>
    </div>

    <div class="bar" id="bar_top" style="background-color: {$self['context_color']}">
        <!-- Left Side -->
        <div class="bar-left">
            <!-- Button to open the "search dialog" -->
            <span class="topmenu-item" title="Advanced search" id="search-item" onClick="xajax_window_submit('search_results', 'search_form_id=>subnet_search_form'); return false;">
                <a id="search-button"
                   class="button ona-rounded"
                >&nbsp;<i class="nf nf-seti-search ona-icon-mainsearch"></i>&nbsp;Search&nbsp;</a>
            </span>

            <!-- Quick Search -->
            <span class="topmenu-item" id='menu-qsearch-item' onmouseover="ona_menu_closedown();">
                <form id="qsearch_form" onSubmit="xajax_window_submit('search_results', xajax.getFormValues('qsearch_form')); return false;">
                    <input type="hidden" name="search_form_id" value="qsearch_form">
                    <input id="qsearch"
                           accesskey="q"
                           class="edit ona-rounded"
                           style="width: 150px;"
                           type="text"
                           title="Quick Search for IP, MAC, DNS"
                           placeholder="Quick Search..."
                           name="q"
                           maxlength="100"
                           onFocus="this.value='';"
                    >
                    <div id="suggest_qsearch" class="suggest"></div>
                    <button type="submit" style="background: none; border: none;">
                      <i class="nf nf-md-arrow_right_circle_outline ona-icon-qsgo" title="Search"></i>
                    </button>
                </form>
            </span>

            <!-- Task Bar (i.e. Window List) -->
            <span class="topmenu-item" style="border-right: 1px solid {$color['border']};">&nbsp;</span>
            <span class="topmenu-item" id="menu-window-list" onmouseover="ona_menu_closedown();">&nbsp;</span>

        </div>

        <!-- Right Side -->
        <div class="bar-right" onmouseover="ona_menu_closedown();">
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
            ><a class="button ona-rounded" style="font-weight:bold;"><i class="nf nf-md-login" style="vertical-align: middle;"></i> <span id="loggedin_user">{$_SESSION['ona']['auth']['user']['username']}</span> <span style="font-weight: normal;font-size: xx-small;"></span> </a>
            </span>

            <span id="loggedin_info" class="topmenu-item" style="cursor: pointer;" title="Click to display user info." onClick="toggle_window('app_user_info');">
                &nbsp;<i class="nf nf-fa-user_circle_o ona-icon-userinfo"></i>
            </span>

            <span id="logoutbutton" class="topmenu-item" style="cursor: pointer;padding-right: 5px;" title="Logout" onClick="var doit=confirm('Are you sure you want to logout?'); if (doit == true) document.location = 'logout.php';">
                <i class="nf nf-md-logout ona-icon-logout"></i>
            </span>
        </div>
    </div>

    <div id="menu_bar_top" style="display: none; width: 100%; height: 16px; font-size: smaller; background-color: #AABBFF;white-space: nowrap;font-weight: bold;border-left: 1px solid #555555;border-right: 1px solid #555555;border-bottom: 1px solid #555555;"></div>

    <div id="trace_history" style="font-size: smaller;height: 16px; border-color: #555555;border-style: solid; border-width: 0px 1px 1px 1px; background-color: #EDEEFF;white-space: nowrap;">&nbsp;Trace:</div>
EOL;

// If we have more than one context defined, lets create a context selector
if (count($ona_contexts) > 1) {
print <<<EOL
    <div style="position: fixed;width: 88%;z-index: 4;">
    <center><div>
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
        <tr>
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

        icon_active   = 'nf-md-credit_card_outline';
        icon_inactive = 'nf-md-credit_card_off_outline'

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
                html += '<a class="button ona-rounded" ' +
                        '   id="' + nodes[i].id + '_taskbar" ' +
                        '   title="' + _title + '" ' +
                        '   onClick="toggle_window(\'' + nodes[i].id + '\');" ' +
                        '><i class="nf ' + icon + '"></i>&nbsp;' + _title + '&nbsp;</a>&nbsp;';
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
<div nowrap style="position: absolute;top: 90px;right: 1px;z-index: 10;background: #E3E3F0;-moz-border-radius-topleft:4px;-moz-border-radius-bottomleft:4px;-webkit-border-top-left-radius:4px;-webkit-border-bottom-left-radius:4px;border-top-left-radius:4px;border-bottom-left-radius:4px;">
    <div style="float:left;padding: 5px 2px;" >
      <i class="nf nf-md-calculator ona-icon-calculator" title="BASIC IP calculator" onclick="toggleBox('ipcalc_content'); el('calc_ip').focus();"></i>
      <div id="ipcalc_content" style="visibility: hidden;display:none;background: #E3E3F0;padding: 5px;-moz-border-radius-topleft:4px;-moz-border-radius-bottomleft:4px;-webkit-border-top-left-radius:4px;-webkit-border-bottom-left-radius:4px;border-top-left-radius:4px;border-bottom-left-radius:4px;">
        <form id="ipcalc_form" onsubmit="return false;">
            IP: <input id="calc_ip" type="text" name="ip" />
            Mask: <input type="text" name="mask" /> <button type="submit" onClick="xajax_window_submit('ipcalcgui', xajax.getFormValues('ipcalc_form'));" >Go</button>
        </form>
        <span style="font-family: monospace;font-size: medium;" id="ipcalc_data"></span>
      </div>
    </div>
</div>

EOL;


// Open the work_space that was requested
if (isset($work_space) or isset($ws)) {
    if (isset($ws)) $work_space = $ws;
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
if (isset($search) or isset($q)) {
    if (isset($q)) $search = $q;
    print <<<EOL
<script type="text/javascript"><!--
    el('qsearch').value = '{$search}';
    xajax_window_submit('search_results', xajax.getFormValues('qsearch_form'));
--></script>
EOL;
}


print <<<EOL

<script>
    var desktop_height = document.body.clientHeight - el('bar_top').clientHeight - el('trace_history').clientHeight;
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
