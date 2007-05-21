<?

// Do some HTML headers before printing anything
header("Cache-control: private");

$year = date('Y');

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
<body bgcolor="{$color['bg']}" link="{$color['link']}" alink="{$color['alink']}" vlink="{$color['vlink']}">

    <!-- Top (Task) Bar -->
    <div class="bar" id="bar_top">

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
                           class="edit"
                           style="width: 150px;"
                           type="text"
                           value=""
                           name="q"
                           maxlength="100"
                           onFocus="el('qsearch_input_overlay').style.display = 'none';"
                           onMouseOver="wwTT(this, event, 'content', 'Quick Search...', 'lifetime', '3000');"
                    >
                    <div id="suggest_qsearch" class="suggest"></div>
                    <div id="qsearch_input_overlay"
                         style="position: absolute;
                                color: #CACACA;
                                cursor: text;
                                display: inline;"
                         onClick="this.style.display = 'none'; el('qsearch').focus();"
                    >Quick search...</div>
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
            <span class="topmenu-item" id="menu-window-list"></span>

        </div>

        <!-- Right Side -->
        <div class="bar-right">
            <span class="topmenu-item"
                  id="sys_alert"
                  style="visibility: hidden;padding: 0px;"
                  onMouseOver="wwTT(this, event,
                                            'id', 'tt_sys_alert',
                                            'type', 'static',
                                            'delay', 0,
                                            'styleClass', 'wwTT_qf',
                                            'direction', 'southwest',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>sys_alert,id=>tt_sys_alert\');'
                                           );"
            ><img src="{$images}/email_error_fade.gif" border="0" /></span>

            <span class="topmenu-item" style="cursor: pointer;" title="Display user info" onClick="toggle_window('app_user_info');">
                <img style="vertical-align: middle;" src="{$images}/silk/user_gray.png" border="0" />
            </span>
            <span class="topmenu-item" style="cursor: pointer;" title="Open online help" onClick="document.location = '{$_ENV['help_url']}'; /* FIXME: Open help in an iframe in a window */">
                <img style="vertical-align: middle;" title="Global help index" src="{$images}/silk/help.png" border="0" />
            </span>
            <span class="topmenu-item" style="cursor: pointer;" title="Logout" onClick="var doit=confirm('Logout?'); if (doit == true) document.location = 'logout.php';">
                <img style="vertical-align: middle;" title="Logout" src="{$images}/silk/door_out.png" border="0" />
            </span>

            &nbsp;
        </div>
    </div>

    <!-- Workspace div -->
    <div id="content_table" style="height: 90%;" class="theWholeBananna">
        <!-- Parent element for all "windows" -->
        <span id="window_container"></span>&nbsp;
    </div>

    <!-- Bottom Text -->
    <div id="bottombox_table" class="bottomBox" style="width: 100%; text-align: center;">
        &copy;{$year} <a href="http://www.opennetadmin.com">OpenNetAdmin</a> - {$conf['version']}<br>
        We recommend <a href="http://www.mozilla.com/firefox/" target="null">Firefox</a> &gt;= 1.5, but this site also works with <a href="http://konqueror.kde.org/" target="null">Konqueror</a> &gt;= 3.5 &amp; Internet Explorer &gt;= 5.5<br>
        This site was designed, written &amp; tested by <a href="mailto:hornet136@gmail.com">Matt Pascoe</a>, <a href="mailto:deacon@thedeacon.org">Paul Kreiner</a> &amp; <a href="mailto:caspian@dotconf.net">Brandon Zehm</a>.
    </div>

<!-- Javascript for the Task Bar -->
<script type="text/javascript"><!--
    /* Setup the quick search */
    suggest_setup('qsearch', 'suggest_qsearch');

    /* Position the quick serach overlay */
    var my_top  = calcOffset(el('qsearch'), 'offsetTop');
    var my_left  = calcOffset(el('qsearch'), 'offsetLeft');
    el('qsearch_input_overlay').style.top    = my_top  + 3 + 'px';
    el('qsearch_input_overlay').style.left   = my_left + 6 + 'px';

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

    setInterval('update_task_bar(el(\'window_container\'), el(\'menu-window-list\'));', 1000);

    /* Call the process_alerts function to look for alerts to display at a regular interval*/
    setInterval('xajax_window_submit(\'process_alerts\', \'\');', 300000);

    /* Go ahead and process_alerts on the initial load */
    xajax_window_submit('process_alerts', '');

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

--></script>

<!-- Set some preferences FIXME: This shouldn't be here! -->
<script type="text/javascript"><!--
    if (getcookie('pref_bg_repeat')) el('content_table').style.backgroundRepeat = getcookie('pref_bg_repeat');
    if (getcookie('pref_bg_url')) el('content_table').style.backgroundImage = 'url(\'' + getcookie('pref_bg_url') + '\')';
--></script>

</body>
</html>
EOL;
?>