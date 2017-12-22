<?php



//////////////////////////////////////////////////////////////////////////////
// Function: ws_editor()
//
// Description:
//     Displays a form for creating/editing a location record.
//     If $form is a valid ID, it is used to display an existing
//     record for editing.  "Save" button calls the ws_save() function.
//////////////////////////////////////////////////////////////////////////////
function ws_editor($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;
    $window = array();

    // Check permissions
    if (!auth('location_add')) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    if (isset($form['id']))
        list($status, $rows, $record) = ona_get_location_record(array('id' => $form['id']));

    // Escape data for display in html
    foreach(array_keys((array)$record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }



    // Set the window title:
    $window['title'] = "Add Location";
    if ($record['id'])
        $window['title'] = "Edit Location";

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

        el('{$window_name}_edit_form').onsubmit = function() { return false; };
        el('ref').focus();

EOL;

    // MP: TODO:  Add a quick find for city and state that looks up existing records.

    // Define the window's inner html
    $window['html'] = <<<EOL

    <!-- Location Edit Form -->
    <form id="{$window_name}_edit_form" onSubmit="return false;">
    <input type="hidden" name="location_id" value="{$record['id']}">
    <input type="hidden" name="js" value="{$form['js']}">
    <table cellspacing="0" border="0" cellpadding="0" style="background-color: {$color['window_content_bg']}; padding-left: 20px; padding-right: 20px; padding-top: 5px; padding-bottom: 5px;">

        <!-- Location RECORD -->
        <tr>
            <td align="left" nowrap="true"><b><u>Location Record</u></b>&nbsp;</td>
            <td class="padding" align="left" width="100%">&nbsp;</td>
        </tr>
EOL;

        $window['html'] .= <<<EOL
        <tr>
            <td class="input_required" align="right">
                <u>R</u>eference
            </td>
            <td align="left" class="qf-search-line">
                <input id="ref" name="reference" type="text" class="edit" size="32" accesskey="r" value="{$record['reference']}"/>
            </td>
        </tr>

        <tr>
            <td class="input_required" align="right">
                <u>N</u>ame
            </td>
            <td align="left" class="qf-search-line">
                <input name="name" type="text" class="edit" size="32" accesskey="n" value="{$record['name']}"/>
            </td>
        </tr>

        <tr>
            <td align="right">
                <u>A</u>ddress
            </td>
            <td align="left" class="qf-search-line">
                <input name="address" type="text" class="edit" size="32" accesskey="a" value="{$record['address']}"/>
            </td>
        </tr>

        <tr>
            <td align="right">
                <u>C</u>ity
            </td>
            <td align="left" class="qf-search-line" nowrap="true">
                <input name="city" class="edit" type="text" size="20" accesskey="c" value="{$record['city']}"/>&nbsp;
                <u>S</u>tate <input name="state" class="edit" type="text" size="2" maxlength="2" value="{$record['state']}"/>
            </td>
        </tr>

        <tr>
            <td align="right">
                <u>Z</u>ip
            </td>
            <td align="left" class="qf-search-line" nowrap="true">
                <input name="zip_code" class="edit" type="text" size="10" maxlength="10" accesskey="z" value="{$record['zip_code']}"/>
            </td>
        </tr>

        <tr>
            <td align="right">
                <u>L</u>atitude
            </td>
            <td align="left" class="qf-search-line" nowrap="true">
                <input name="latitude" class="edit" type="text" size="10" maxlength="10" accesskey="l" value="{$record['latitude']}"/>
            </td>
        </tr>

        <tr>
            <td align="right">
                L<u>o</u>ngitude
            </td>
            <td align="left" class="qf-search-line" nowrap="true">
                <input name="longitude" class="edit" type="text" size="10" maxlength="10" accesskey="o" value="{$record['longitude']}"/>
            </td>
        </tr>

        <tr>
            <td align="right">
                <u>M</u>isc info
            </td>
            <td align="left" class="qf-search-line">
                <textarea name="misc" class="edit" rows="3" cols="20" size="256" accesskey="m">{$record['misc']}</textarea>
            </td>
        </tr>



        <tr>
            <td align="right" valign="top" nowrap="true">
                &nbsp;
            </td>
            <td class="padding" align="right" width="100%">
                <input type="hidden" name="overwrite" value="{$overwrite}">
                <input class="edit" type="button" name="cancel" value="Cancel" onClick="removeElement('{$window_name}');">
                <input class="edit" type="button"
                    name="submit"
                    value="Save"
                    accesskey=" "
                    onClick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_edit_form'), 'save');"
                >
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
//     Creates/updates a location record.
//////////////////////////////////////////////////////////////////////////////
function ws_save($window_name, $form='') {
    global $base, $include, $conf, $self, $onadb;

    // Check permissions
    if (! (auth('location_add')) ) {
        $response = new xajaxResponse();
        $response->addScript("alert('Permission denied!');");
        return($response->getXML());
    }

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Validate input
    if (!$form['reference'] or !$form['name']) {
        $response->addScript("alert('Please complete all fields to continue!');");
        return($response->getXML());
    }


    // Decide if we're editing or adding
    $module = 'location_add';
    if ($form['location_id']) {
        $module = 'location_modify';
        $form['set_name'] = $form['name'];              unset($form['name']);
        $form['set_address'] = $form['address'];        unset($form['address']);
        $form['set_reference'] = $form['reference'];
        $form['set_city'] = $form['city'];              unset($form['city']);
        $form['set_state'] = $form['state'];            unset($form['state']);
        $form['set_zip_code'] = $form['zip_code'];      unset($form['zip_code']);
        $form['set_longitude'] = $form['longitude'];    unset($form['longitude']);
        $form['set_latitude'] = $form['latitude'];      unset($form['latitude']);
        $form['set_misc'] = $form['misc'];              unset($form['misc']);
        $form['reference'] = $form['location_id'];
    }

    // If there's no "refresh" javascript, add a command to view the new record
    if (!preg_match('/\w/', $form['js']))
        $form['js'] = "xajax_window_submit('app_location_list', xajax.getFormValues('app_location_list_filter_form'), 'display_list');";

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
    $response->addScript($js);
    return($response->getXML());
}








?>
