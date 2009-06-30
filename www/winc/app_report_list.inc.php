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
if (!auth('user_admin')) {
    $window['js'] = "alert('Permission denied!'); removeElement('{$window_name}');";
    return;
}


// Set the window title:
$window['title'] = "Report list";

// Load some html into $window['html']
$form_id = "{$window_name}_filter_form";
$tab = 'reports';
$submit_window = $window_name;
$content_id = "{$window_name}_list";
$window['html'] .= <<<EOL
    <!-- Tabs & Quick Filter -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" >
        <tr>
            <td id="{$form_id}_{$tab}_tab" nowrap="true" class="table-tab-active">
                Reports <span id="{$form_id}_{$tab}_count"></span>
            </td>

            <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                <form id="{$form_id}" onSubmit="return false;">
                <input id="{$form_id}_page" name="page" value="1" type="hidden">
                <input name="content_id" value="{$content_id}" type="hidden">
                <input name="form_id" value="{$form_id}" type="hidden">
                <div id="{$form_id}_filter_overlay"
                     style="position: relative;
                            display: inline;
                            color: #CACACA;
                            cursor: text;"
                     onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                >Name</div>
                <input
                    id="{$form_id}_filter"
                    name="filter"
                    class="filter"
                    type="text"
                    value=""
                    size="10"
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
    el('{$form_id}_filter_overlay').style.left = (el('{$form_id}_filter_overlay').offsetWidth + 10) + 'px';
    {$form_id}_last_search = '';

    /* Tell the browser to load/display the list */
    xajax_window_submit('{$submit_window}', xajax.getFormValues('{$form_id}'), 'display_list');
EOL;












// This function displays a list (all?) groups in the
function ws_display_list($window_name, $form) {
    global $conf, $self, $onadb, $base;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('user_admin')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
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
            <td class="list-header" align="center" style="{$style['borderR']};">Description</td>
            <td class="list-header" align="center">&nbsp;</td>
        </tr>

EOL;


    // Generate a list of reports available
    $records = array();


    // Check the usual directories, now inlucdes the local reports as well.
    // local plugins should override the builtin stuff if they are named the same.
    $directories = array($base.'/reports/listentries/',
                         $base.'/local/reports/listentries/',
                        );

    // Scan the directories to find the report include file
    foreach ($directories as $directory) {
      if (is_dir($directory)) {
        $d = dir($directory);
        while (false!== ($filename = $d->read())) {
            if (substr($filename, -8) == '.inc.php') {
                 //include "$directory$filename";
                if (is_array($form) and $form['filter']) {
                    if (preg_match("/{$form['filter']}/i", str_replace('.inc.php', '', $filename)))
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
    sort($records);

    // split the array into chunks of result size
    $records = array_chunk($records, $conf['search_results_per_page']);

    if (!$records[0]) {
        $html .= <<<EOL
<tr><td colspan=4><center>There are currently no reports installed.  Please<br>visit <a href="http://opennetadmin.com">OpenNetAdmin.com</a> to download new reports.</center></td></tr>
EOL;
    }

    // Loop through and display the groups
    foreach ($records[$page-1] as $entry) {

        $report_description = '';
        $record['name'] = basename($entry);
        $record['shortname'] = str_replace('.inc.php', '', $record['name']);
        include_once $entry;
        $record['desc'] = $report_description;

        // Escape data for display in html
        foreach(array_keys($record) as $key) {
            $record[$key] = htmlentities($record[$key], ENT_QUOTES);
        }

        $html .= <<<EOL
        <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

            <td class="list-row">
                <a title="Run report: {$record['shortname']}"
                    class="act"
                    onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_report\', \'report=>{$record['shortname']}\', \'display\')');toggle_window('app_report_list');"
                >{$record['shortname']}</a>&nbsp;
            </td>

            <td class="list-row">
                {$record['desc']}&nbsp;
            </td>

            <td align="right" class="list-row" nowrap="true">
                <a title="Run report: {$record['shortname']}"
                    class="act"
                    onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_report\', \'report=>{$record['shortname']}\', \'display\')');toggle_window('app_report_list');"
                ><img src="{$images}/silk/application.png" border="0"></a>&nbsp;
            </td>

        </tr>
EOL;
    }

    $html .= <<<EOL
    </table>

EOL;


    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);


    // Insert the new table into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_reports_count",  "innerHTML", "({$count})");
    $response->addAssign("{$form['content_id']}", "innerHTML", $html);
    // $response->addScript($js);
    return($response->getXML());
}





?>