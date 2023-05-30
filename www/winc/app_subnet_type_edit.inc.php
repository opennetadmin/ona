<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing subnet types.
//     If a subnet type id is found in $form it is used to display an existing
//     subnet type for editing.  When "Save" is pressed the save()
//     function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'Subnet Type Editor',
        'html'  => '',
        'js'    => '',
    );

    $window['js'] .= <<<EOL
        /* Put a minimize icon in the title bar */
        el('{$window_name}_title_r').innerHTML = 
            '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;

        /* Put a help icon in the title bar */
        el('{$window_name}_title_r').innerHTML = 
            '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
            el('{$window_name}_title_r').innerHTML;
        el('subnet_type_name').focus();
EOL;

    // If we got a subnet type, load it for display
    if (is_numeric($form)) {
        list($status, $rows, $record) = db_get_record($onadb, 'subnet_types', array('id' => $form));
    }

    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple subnet types Edit Form -->
    <form id="{$window_name}_form" onSubmit="return false;" autocomplete="off">
    <input name="id" type="hidden" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td nowrap="yes" align="right">
                Display Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="subnet_type_name"
                    name="display_name"
                    alt="Subnet Type Description"
                    value="{$record['display_name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="63"
                >
            </td>

        </tr><tr>

            <td nowrap="yes" align="right">
                Short Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="short_name"
                    alt="Short name for use on console"
                    value="{$record['short_name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="31"
                >
            </td>

        </tr><tr>

            <td nowrap="yes" align="right">
                Notes
            </td>
            <td class="padding" align="left" width="100%">
                <input 
                    name="notes"
                    alt="Notes"
                    value="{$record['notes']}"
                    class="edit" 
                    type="text" 
                    size="30" maxlength="127" 
                >
            </td>
        </tr>

        <tr>
            <td align="right" valign="top">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <input class="edit" type="button"
                    name="submit"
                    value="Save"
                    accesskey=" "
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_form'), 'save');"
                >
            </td>
        </tr>

    </table>
    </form>

EOL;


    // Lets build a window and display the results
    return(window_open($window_name, $window));

}







//////////////////////////////////////////////////////////////////////////////
// Function:
//     Save Form
// 
// Description:
//     Creates/updates an subnet type with the info from the submitted form.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $conf, $self, $onadb;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->script("alert('Permission denied!');");
        return $response;
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate Input
    if ($form['short_name'] == '' or
        $form['display_name'] == ''
       ) {
        $response->script("alert('Please complete all fields to continue!');");
        return $response;
    }

    // BUSINESS RULE: Force short_name to be console friendly (a-z,-, & _ only)
    $form['short_name'] = strtolower($form['short_name']);
    if (!preg_match('/^[\w-_]+$/', $form['short_name'])) {
        $response->script("alert('Invalid short name! Please use only script-friendly characters: a-z - _ (no spaces)');");
        return $response;
    }

    // If you get a numeric in $form, update the record
    if (is_numeric($form['id'])) {
    list($status, $rows) = db_update_record(
                                     $onadb,
                                     'subnet_types',
                                     array('id' => $form['id']),
                                     array(
                                        'short_name' => $form['short_name'],
                                        'display_name' => $form['display_name'],
                                        'notes' => $form['notes'],
                                     )
                                 );
    } 
    // If you get nothing in $form, create a new record
    else {
        $id = ona_get_next_id('subnet_types');
        list($status, $rows) = db_insert_record($onadb, 'subnet_types', array('id' => $id, 'display_name' => $form['display_name'], 'short_name' => $form['short_name'], 'notes' => $form['notes']));
    }

    // If the module returned an error code display a popup warning
    if ($status) {
        $js .= "alert('Save failed. ". trim($self['error']) . " (Hint: All fields are required!)');";
    }
    else {
        $js .= "removeElement('{$window_name}');";
        $js .= "xajax_window_submit('app_subnet_type_list', xajax.getFormValues('app_subnet_type_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->script($js);
    return $response;
}



?>
