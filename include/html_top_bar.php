<?
// FIXME: Matt was trying to put the trace bar into this file, might be a good idea someday

// FIXME: hard coded for now
$_SESSION['ona']['auth']['user']['username'] = "guest";
$_SESSION['ona']['auth']['user']['level'] = "0";

print <<<EOL
    
    <!-- Top (start) Bar -->
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
                           type="text" 
                           value=""
                           name="q" 
                           size="15" 
                           maxlength="100" 
                           onFocus="el('qsearch_input_overlay').style.display = 'none';"
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
            <span class="topmenu-item" style="cursor: pointer;" title="Display user info" onClick="toggle_window('app_user_info');">
                <img style="vertical-align: middle;" src="{$images}/silk/user_gray.png" border="0" /> 
            </span>
            
            <form id="login_form" onSubmit="xajax_window_submit('login_password', xajax.getFormValues('login_form')); return false;">
                <input id="login_userid"
                        class="edit"
                        type="text"
                        value="{$_SESSION['ona']['auth']['user']['username']}"
                        name="login_userid"
                        size="12"
                >
            </form>
            
            <img style="vertical-align: middle;" title="Logout" src="{$images}/silk/door_out.png" border="0" onClick="document.location = 'logout.php';" />
            <img style="vertical-align: middle;" title="Global help index" src="{$images}/silk/help.png" border="0" onClick="document.location = '{$_ENV['help_url']}';" />
            
            &nbsp;
        </div>
    </div>
    
EOL;







// Some Javascript
print <<<EOL
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
        
        /* Update the bar if it's changed */
        if ( (update == 1) || (html.replace(/(<([^>]+)>)/g,"") != (_bar.innerHTML).replace(/(<([^>]+)>)/g,"")) ) {
            _bar.innerHTML = html;
        }
    }
    
    setInterval('update_task_bar(el(\'window_container\'), el(\'menu-window-list\'));', 1000);
    
    /* Setup mouse handlers for the "Start" button */
    var _button = el('menu-apps-button');
    _button.onclick = 
        function(ev) {
            if (!ev) ev = event;
            /* Get info about the button */
            var button_top    = calcOffset(el('menu-apps-button'), 'offsetTop');
            var button_left   = calcOffset(el('menu-apps-button'), 'offsetLeft');
            var button_height = el('menu-apps-button').offsetHeight;
            /* Create the fake tool-tip */
            wwTT(this, ev, 
                 'id', 'start_menu', 
                 'type', 'velcro',
                 'x', button_left,
                 'y', button_top + button_height,
                 'width', 200,
                 'delay', 0,
                 'styleClass', 'wwTT_ona_menu',
                 'javascript', 'el(\'start_menu\').style.visibility = \'hidden\'; xajax_window_submit(\'tooltips\', \'tooltip=>start_menu,id=>start_menu\');'
            );
        };
    _button.onmouseover = _button.onclick;
    
--></script>
EOL;

?>