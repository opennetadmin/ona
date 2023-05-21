<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing an block record.
//     If $form is a valid BLOCK_ID, it is used to display an existing
//     record for editing.  "Save" button calls the ws_save() function.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $window = array();

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Load an existing host record (and associated info) if $form is a host_id
    if (isset($form['block_id'])) {
        list($status, $rows, $block) = ona_get_block_record(array('id' => $form['block_id']));
        $block['ip_addr_start'] = ip_mangle($block['ip_addr_start'], 'dotted');
        $block['ip_addr_end'] = ip_mangle($block['ip_addr_end'], 'dotted');
    }


    // Escape data for display in html
    foreach(array_keys((array)$block) as $key) { $block[$key] = htmlentities($block[$key], ENT_QUOTES, $conf['php_charset']); }


    // Set the window title:
    $window['title'] = "Add Block";
    if ($block['id'])
        $window['title'] = "Edit Block";

    // Javascript to run after the window is built
    $window['js'] = <<<EOL
        /* Put a minimize icon in the title bar */
        el('{$window_name}_title_r').innerHTML =
            '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;

        /* Put a help icon in the title bar */
        el('{$window_name}_title_r').innerHTML =
            '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;

    el('name').focus();
EOL;

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Block Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;" autocomplete="off">
    <input type="hidden" name="block_id" value="{$block['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- BLOCK RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Block Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                Block Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="name"
                    name="name"
                    alt="Block name"
                    value="{$block['name']}"
                    class="edit"
                    type="text"
                    size="27" maxlength="255"
                >
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                IP Block Start
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="start"
                    alt="IP block start"
                    value="{$block['ip_addr_start']}"
                    class="edit"
                    type="text"
                    size="20" maxlength="40"
                >
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right" nowrap="true">
                IP Block End
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="end"
                    alt="IP block end"
                    value="{$block['ip_addr_end']}"
                    class="edit"
                    type="text"
                    size="20" maxlength="40"
                >
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true">
                Notes
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="notes"
                    alt="Notes"
                    value="{$block['notes']}"
                    class="edit"
                    type="text"
                    size="17" maxlength="255"
                >
            </td>
        </tr>



        <tr>
            <td align="right" valign="top" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%">
                <input type="hidden" name="overwrite" value="{$overwrite}">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <button type="submit"
                    name="submit"
                    accesskey=" "
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_edit_form'), 'save');"
                >Save</button>
            </td>
        </tr>

    </table>
    </form>
EOL;


    return(window_open($window_name, $window));
}




//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
//
// Description:
//     Creates/updates a block record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('advanced')) ) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if (!$form['name'] or !$form['start'] or !$form['end']) {
        $response->script("alert('Please complete all required fields to continue!');");
        return $response;
    }

    // Decide if we're editing or adding
    $module = 'block_add';
    if ($form['block_id']) {
        $module = 'block_modify';
        $form['set_name'] = $form['name'];
        $form['set_start'] = $form['start'];
        $form['set_end'] = $form['end'];
        $form['set_notes'] = $form['notes'];
        $form['block'] = $form['block_id'];
    }

    // If there's no "refresh" javascript, add a command to view the new block
    if (!preg_match('/\w/', $form['js']))
        $form['js'] = "xajax_window_submit('search_results', 'search_form_id=>block_search_form,all_flag=>1');";


    // Run the module
    list($status, $output) = run_module($module, $form);

    // If the module returned an error code display a popup warning
    if ($status)
        $js .= "alert('Save failed. ". preg_replace('/[\s\']+/', ' ', $self['error']) . "');";
    else {
        $js .= "removeElement('{$window_name}');";
        if ($form['js']) $js .= $form['js'];
    }

    // Insert the new table into the window
    $response->script($js);
    return $response;
}




//////////////////////////////////////////////////////////////////////////////
// Function:
//     Delete Form
//
// Description:
//     Deletes an block record.  $form should be an array with an 'block_id'
//     field.
//////////////////////////////////////////////////////////////////////////////
function ws_delete($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

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
    $js = '';

    // Run the module
    list($status, $output) = run_module('block_del', array('block' => $form['block_id'], 'commit' => 'Y'));

    // If the module returned an error code display a popup warning
    if ($status) { $js .= "alert('Delete failed. " . preg_replace('/[\s\']+/', ' ', $self['error']) . "');"; }
    else {
        // If there's (refresh) js, send it to the browser
        if ($form['js']) { $js .= $form['js']; }
    }

    // Return an XML response
    $response->script($js);
    return $response;
}




?>
