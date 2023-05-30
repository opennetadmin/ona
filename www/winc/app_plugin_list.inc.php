<?php

//
// So, the basic flow of this script is like this:
//   * When the window is initially opened we define the normal window
//     parameters for building an almost empty window.  After that new
//     empty window is created it's instructed to run an xajax callback
//     to the display_list() function.  display_list() builds an
//     html list of groups and pushes it into the empty window.
//   * If a search is entered into the "quick filter" another xajax
//     call is made to display_list(), this time passing a search
//     query.  display_list() grabs the refined list of groups
//     and pushes them to the window just like the first time.
//
//
//


// Check permissions
if (!auth('advanced')) {
    $window['js'] = "alert('Permission denied!'); removeElement('{$window_name}');";
    return;
}


// Set the window title:
$window['title'] = "Plugin Management";

// Load some html into $window['html']
$form_id = "{$window_name}_filter_form";
$tab = 'plugins';
$submit_window = $window_name;
$content_id = "{$window_name}_list";
$window['html'] .= <<<EOL
    <!-- Tabs & Quick Filter -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" >
        <tr>
            <td id="{$form_id}_{$tab}_tab" nowrap="true" class="table-tab-active">
                Plugins <span id="{$form_id}_{$tab}_count"></span>
            </td>

            <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                <form id="{$form_id}" onSubmit="return false;" autocomplete="off">
                <input id="{$form_id}_page" name="page" value="1" type="hidden">
                <input name="content_id" value="{$content_id}" type="hidden">
                <input name="form_id" value="{$form_id}" type="hidden">
                <input
                    id="{$form_id}_filter"
                    name="filter"
                    class="filter"
                    type="text"
                    value=""
                    size="10"
                    maxlength="20"
                    alt="Quick Filter"
                    placeholder="Name"
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

    <!-- Item List -->
    <div id='{$content_id}'>
        {$conf['loading_icon']}
    </div>
EOL;







// Define javascript to run after the window is created
$window['js'] = <<<EOL
    /* Put a minimize icon in the title bar */
    el('{$window_name}_title_r').innerHTML =
        '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;

    /* Put a help icon in the title bar */
    el('{$window_name}_title_r').innerHTML =
        '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;

    /* Setup the quick filter */
    {$form_id}_last_search = '';

    /* Tell the browser to load/display the list */
    xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;












// This function displays a list (all?) groups in the
function ws_display_list($window_name, $form) {
    global $conf, $self, $onadb, $base;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // If the group supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Find out what page we're on
    $page = 1;
    if ($form['page'] and is_numeric($form['page'])) { $page = $form['page']; }


    $html = <<<EOL

    <!-- Results Table -->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" class="list-box">

        <!-- Table Header -->
        <tr>
            <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Version</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Description</td>
            <td class="list-header" align="center">&nbsp;</td>
        </tr>

EOL;


    // Generate a list of reports available
    $records = array();


    // Check the usual directories, now includes the local reports as well.
    // local plugins should override the builtin stuff if they are named the same.
    $directories = array($base.'/local/plugins/',$base.'/plugins/');

    // Scan the directories to find the report include file
    foreach ($directories as $directory) {
      if (is_dir($directory)) {
        $d = dir($directory);
        while (false!== ($filename = $d->read())) {
            if ($filename != '.' and $filename != '..' and $filename != 'README' and $filename != '.svn' and substr($filename, -7) != '.tar.gz') {
                 //include "$directory$filename";
                if (is_array($form) and $form['filter']) {
                    if (preg_match("/{$form['filter']}/i", $filename))
                        array_push($records, $directory.$filename);
                } else {
                    array_push($records, $directory.$filename);
                }
            }
        }
        $d->close();
        }
    }

    $count = count($records);
    @sort($records);

    // split the array into chunks of result size
    $records = @array_chunk($records, $conf['search_results_per_page']);

    if (!$records[0]) {
        $html .= <<<EOL
<tr><td colspan=4><center>There are currently no plugins installed OR <br> the search returned no results.  Please<br>visit <a href="http://opennetadmin.com">OpenNetAdmin.com</a> to download new plugins.</center></td></tr>
EOL;
    } else {

    // Loop through and display the groups
    foreach ($records[$page-1] as $entry) {

        $plugin_description = '';
        $plugin_version = '';
        $plugin_help_url = '';
        $record['name'] = basename($entry);

        @include_once $entry.'/plugin_info.php';
        $record['desc'] = $plugin_description;
        $record['version'] = ($plugin_version) ? $plugin_version : 'Unknown';
        $record['help_url'] = $plugin_help_url;

        $record['disabled'] = (file_exists($entry.'/plugin_disabled')) ? true : false;
        $record['installed'] = (file_exists($entry.'/install.php')) ? true : false;

        // Escape data for display in html
        foreach(array_keys($record) as $key) {
            $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);
        }

        $html .= <<<EOL
        <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

            <td class="list-row">
                {$record['name']}&nbsp;
            </td>

            <td class="list-row">
                {$record['version']}&nbsp;
            </td>

            <td class="list-row">
                {$record['desc']}&nbsp;
            </td>

            <td align="right" class="list-row" nowrap="true">
EOL;

        if ($record['installed']) {
            $html .= <<<EOL
                <a title="Execute install steps: {$record['name']}"
                    class="act"
                    onClick="toggle_window('{$record['name']}');"
                ><img src="{$images}/silk/plugin_error.png" border="0"></a>&nbsp;
EOL;
        }

        if ($record['help_url']) {
            $html .= <<<EOL
                <a title="Plugin help and info URL"
                    class="act"
                    target="_blank"
                    href="{$record['help_url']}"
                ><img src="{$images}/silk/help.png" border="0"></a>&nbsp;
EOL;
        }

        if ($record['disabled']) {
            $html .= <<<EOL
                <a title="Enable plugin: {$record['name']}"
                    class="act"
                    onClick="xajax_window_submit('app_plugin_list', 'plugin=>{$record['name']},state=>enable,path=>{$entry}', 'toggleenable');"
                ><img src="{$images}/silk/plugin_disabled.png" border="0"></a>&nbsp;
EOL;

        } else {

            $html .= <<<EOL
                <a title="Disable plugin: {$record['name']}"
                    class="act"
                    onClick="xajax_window_submit('app_plugin_list', 'plugin=>{$record['name']},state=>disable,path=>{$entry}', 'toggleenable');"
                ><img src="{$images}/silk/plugin.png" border="0"></a>&nbsp;
EOL;

        }

//             $html .= <<<EOL
//                 <a title="Uninstall plugin: {$record['name']}"
//                     class="act"
//                     onClick="xajax_window_submit('app_plugin_list', 'plugin=>{$record['name']},state=>disable', 'uninstall')');"
//                 ><img src="{$images}/silk/plugin_delete.png" border="0"></a>&nbsp;
// EOL;




            $html .= <<<EOL
            </td>

        </tr>
EOL;
    }

    $html .= <<<EOL
    </table>

EOL;
}

    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);


    // Insert the new table into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->assign("{$form['form_id']}_plugins_count",  "innerHTML", "({$count})");
    $response->assign("{$form['content_id']}", "innerHTML", $html);
    $response->script($js);
    return $response;
}










//////////////////////////////////////////////////////////////////////////////
// Function:
//     Toggle plugin enable Form
//
// Description:
// toggles a plugins state by touching or removing the plugin_disabled file

//////////////////////////////////////////////////////////////////////////////
function ws_toggleenable($window_name, $form='') {
    global $include, $conf, $self, $onadb;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = "xajax_window_submit('app_plugin_list', xajax.getFormValues('app_plugin_list_filter_form'), 'display_list');";

    if (is_writable($form['path'])) {
        if ($form['state'] == 'disable') {
            touch($form['path'].'/plugin_disabled');
        }
        if ($form['state'] == 'enable') {
            unlink($form['path'].'/plugin_disabled');
        }
    } else {
        $js .= "alert('Plugin path {$form['path']} is not writeable by the web server!');";
    }

    if ($form['task'] == 'install') {
        return(window_open('', $window));
    }

    if ($form['js'])
        $js .= $form['js'];  // usually js will refresh the window we got called from

    // Return an XML response
    $response->script($js);
    return $response;
}







?>
