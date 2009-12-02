<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display Edit Form
//
// Description:
//     Displays a form for creating/editing views.
//     If a type id is found in $form it is used to display an existing
//     type for editing.  When "Save" is pressed the save()
//     function is called.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'DNS View Editor',
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
EOL;

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // If we got a view, load it for display
    if (is_numeric($form['id'])) {
        list($status, $rows, $record) = db_get_record($onadb,
                                            'dns_views',
                                            array('id' => $form['id']));
    }


    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) {$record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']);}

    // Load some html into $window['html']
    $window['html'] .= <<<EOL

    <!-- Simple class types Edit Form -->
    <form id="dns_view_edit_form" onSubmit="return false;">
    <input name="id" type="hidden" value="{$record['id']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">
        <tr>
            <td class="input_required" align="right">
                Name
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="dns_view_name"
                    alt="DNS View Name"
                    value="{$record['name']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="64"
                >
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right">
                Description
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    name="dns_view_description"
                    alt="DNS View Description"
                    value="{$record['description']}"
                    class="edit"
                    type="text"
                    size="30" maxlength="64"
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
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('dns_view_edit_form'), 'save');"
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
//     Creates/updates a view with the info from the submitted form.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $conf, $self, $onadb;

    // Check permissions
    if (!auth('advanced')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';


    // Strip whitespace
    // FIXME: (PK) What about SQL injection attacks?  This is a user-entered string...
    // Sanitize "name" option
    // We require view names to be in upper case and spaces are converted to -'s.
    $form['dns_view_name'] = strtoupper(trim($form['dns_view_name']));
    $form['dns_view_name'] = preg_replace('/\s+/', '-', $form['dns_view_name']);

    $form['dns_view_description'] = trim($form['dns_view_description']);

    // Don't insert a string of all white space!
    if(trim($form['dns_view_name']) == "") {
        $self['error'] = "ERROR => Blank names not allowed.";
        printmsg($self['error'], 1);
        $response->addScript("alert('{$self['error']}');");
        return($response->getXML());
    }



    // If you get a numeric in $form, update the record
    if (is_numeric($form['id'])) {
        // Get the record before updating (logging)
        list($status, $rows, $original_type) = ona_get_record(array('id' => $form['id']), 'dns_views');

        $SET = array();
        if (strtoupper($form['dns_view_name']) != $original_type['name']) {
            // check for an existing entry like this
            list($status, $rows, $test) = ona_get_record(array('name' => $form['dns_view_name']), 'dns_views');
            if ($rows) {
                $self['error'] = "ERROR => The name you are trying to use already exists.";
                printmsg($self['error'], 1);
                $response->addScript("alert('{$self['error']}');");
                return($response->getXML());
            }
            $SET['name'] = strtoupper($form['dns_view_name']);
        }
        if ($form['dns_view_description'] != $original_type['description']) $SET['description'] = $form['dns_view_description'];

        list($status, $rows) = db_update_record($onadb, 'dns_views', array('id' => $form['id']), $SET );
        if ($status or !$rows) {
            $self['error'] = "ERROR => dns_view_edit update ws_save() failed: " . $self['error'];
            printmsg($self['error'], 1);
            $response->addScript("alert('{$self['error']}');");
        }
        else {
            // Get the record after updating (logging)
            list($status, $rows, $new_type) = ona_get_record(array('id' => $form['id']), 'dns_views');

            // Return the success notice
            $self['error'] = "INFO => DNS view UPDATED:{$new_type['id']}: {$new_type['name']}";
            printmsg($self['error'], 0);
            $log_msg = "INFO => DNS view UPDATED:{$new_type['id']}: name[{$original_type['name']}=>{$new_type['name']}]";
            printmsg($log_msg, 0);
        }

    }
    // If you get nothing in $form, create a new record
    else {

        // check for an existing entry like this
        list($status, $rows, $test) = ona_get_record(array('name' => $form['dns_view_name']), 'dns_views');
        if ($rows) {
            $self['error'] = "ERROR => The name you are trying to use already exists.";
            printmsg($self['error'], 1);
            $response->addScript("alert('{$self['error']}');");
            return($response->getXML());
        }

        $id = ona_get_next_id('dns_views');

        if (!$id) {
            $self['error'] = "ERROR => The ona_get_next_id() call failed!";
            printmsg($self['error'], 1);
        }
        else {
            printmsg("DEBUG => id for new dns view record: $id", 3);
            list($status, $rows) = db_insert_record($onadb,
                                        "dns_views",
                                        array('id' => $id,
                                        'name' => strtoupper(trim($form['dns_view_name'])),
                                        'description' => $form['dns_view_description']));

            if ($status or !$rows) {
                $self['error'] = "ERROR => dns_view_edit add ws_save() failed: " . $self['error'];
                printmsg($self['error'], 1);
            }
            else {
                $self['error'] = "INFO => DNS view ADDED: {$form['dns_view_name']} ";
                printmsg($self['error'], 0);
            }
        }
    }

    // If the module returned an error code display a popup warning
    if ($status or !$rows) {
        $js .= "alert(\"Save failed. ". trim($self['error']) . " (Hint: Does the name you're trying to insert already exist?)\");";
    }
    else {
        $js .= "removeElement('{$window_name}');";
        $js .= "xajax_window_submit('app_dns_view_list', xajax.getFormValues('app_dns_view_list_filter_form'), 'display_list');";
    }

    // Return some javascript to the browser
    $response->addScript($js);
    return($response->getXML());
}



?>