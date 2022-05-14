<?php


// We need to build the html drop-down boxes for the
// "Custom Attribute", "Device model", "Device type", and "Device manufacturer" fields.
global $onadb;


// Build subnet type list
list($status, $rows, $records) = db_get_records($onadb, 'subnet_types', 'id >= 1', 'display_name');
$subnet_type_list = '<option value="">&nbsp;</option>\n';
$record['display_name'] = htmlentities($record['display_name']);
foreach ($records as $record) {
    $subnet_type_list .= "<option value=\"{$record['id']}\">{$record['display_name']}</option>\n";
}



// Set the window title:
$window['title'] = "Advanced Search";

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
    
    suggest_setup('hostname', 'suggest_hostname');
    suggest_setup('domain',     'suggest_domain');
    suggest_setup('mac',      'suggest_mac');
    suggest_setup('ip',       'suggest_ip');
    suggest_setup('ip_thru',  'suggest_ip_thru');
    suggest_setup('notes',    'suggest_notes');
    suggest_setup('ip_subnet', 'suggest_ip_subnet');
    suggest_setup('ip_subnet_thru',  'suggest_ip_subnet_thru');
    suggest_setup('subnet', 'suggest_subnet');
    suggest_setup('location', 'suggest_location');
    el('host_search_form').onsubmit = function() { return false; };
    el('subnet_search_form').onsubmit = function() { return false; };

        /* Setup the Quick Find location icon */
        var _button = el('qf_location_{$window_name}');
        _button.style.cursor = 'pointer';
        _button.onclick =
            function(ev) {
                if (!ev) ev = event;
                /* Create the popup div */
                wwTT(this, ev,
                     'id', 'tt_qf_location_{$window_name}',
                     'type', 'static',
                     'direction', 'south',
                     'delay', 0,
                     'styleClass', 'wwTT_qf',
                     'javascript',
                     "xajax_window_submit('tooltips', '" +
                         "tooltip=>qf_location," +
                         "id=>tt_qf_location_{$window_name}," +
                         "input_id=>qf_location_{$window_name}');"
                );
            }; 

EOL;

// Define the window's inner html
$window['html'] = <<<EOL
    
    <!-- Tab for selecting a subnet or host search -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" style="margin-top: 0.2em">
    <tr>
        <td id="host_search_tab" nowrap="true" class="padding" 
            style="cursor: pointer; {$style['borderT']}; {$style['borderB']}; {$style['borderR']}; background-color: {$color['window_tab_active_bg']};"
            onClick="el('subnet_search').style.display = 'none';  el('subnet_search_tab').style.backgroundColor = '{$color['window_tab_inactive_bg']}';
                     el('host_search').style.display = 'block';    el('host_search_tab').style.backgroundColor = '{$color['window_tab_active_bg']}';">
            Host search</td>
        
        <td id="subnet_search_tab" nowrap="true" class="padding" 
            style="cursor: pointer; {$style['borderT']}; {$style['borderB']}; {$style['borderR']};"
            onClick="el('host_search').style.display = 'none';     el('host_search_tab').style.backgroundColor = '{$color['window_tab_inactive_bg']}';
                     el('subnet_search').style.display = 'block'; el('subnet_search_tab').style.backgroundColor = '{$color['window_tab_active_bg']}';">
            Subnet search</td>
        
        <td width="80%" class="padding" style="{$style['borderB']};">&nbsp;</td>
    </tr>
    </table>
    
    
    <!-- Host Search Tab -->
    <form id="host_search_form" autocomplete="off">
    <input type="hidden" name="search_form_id" value="host_search_form">
    <table style="background-color: {$color['window_content_bg']};" id="host_search" width="100%" cellspacing="0" border="0" cellpadding="0">
    
    <tr>
        <td align="right" class="asearch-line">
            <u>H</u>ostname
        </td>
        <td align="left" class="asearch-line">
            <input id="hostname" name="hostname" type="text" class="edit" size="35" accesskey="h" />
            <div id="suggest_hostname" class="suggest"></div>
        </td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            Subdomain (<u>z</u>one)
        </td>
        <td align="left" class="asearch-line">
            <input id="domain" name="domain" type="text" class="edit" size="35" accesskey="z" />
            <div id="suggest_domain" class="suggest"></div>
        </td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            <u>M</u>AC
        </td>
        <td align="left" class="asearch-line">
            <input id="mac" name="mac" type="text" class="edit" size="17" accesskey="m" />
            <div id="suggest_mac" class="suggest"></div>
        </td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            <u>I</u>P Address
        </td>
        <td align="left" class="asearch-line" nowrap="true">
            <input id="ip" name="ip" type="text" class="edit" size="15" accesskey="i" />
            <div id="suggest_ip" class="suggest"></div>
            thru
            <input id="ip_thru" name="ip_thru" class="edit" type="text" size="15">
            <div id="suggest_ip_thru" class="suggest"></div>
        </td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            <u>N</u>otes
        </td>
        <td align="left" class="asearch-line">
            <input id="notes" name="notes" type="text" class="edit" size="17" accesskey="n" />
            <div id="suggest_notes" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            <u>L</u>ocation Ref
        </td>
        <td align="left" class="asearch-line">
            <input id="location" class="edit" type="text" name="location" size="8" accesskey="l" />
            <!--<span id="qf_location_{$window_name}"><img src="{$images}/silk/find.png" border="0"/></span>-->
            <div id="suggest_location" class="suggest"></div>
        </td>
    </tr>

    <tr id='more_options_link'>
        <td align="right" class="asearch-line">
            <a class="nav" onClick="xajax_window_submit('{$window_name}', 'show more', 'more_host_options');">More &gt;&gt;</a>
        </td>
        <td align="left" class="asearch-line">
            &nbsp;
        </td>
    </tr>
    
    <tr>
        <td align="left" colspan="2" id="more_host_options"></td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            &nbsp;
        </td>
        <td align="right" class="asearch-line">
            <input class="button" type="reset" name="reset" value="Clear">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="toggle_window('{$window_name}'); xajax_window_submit('search_results', xajax.getFormValues('host_search_form'));">
        </td>
    </tr>
    
    </table>
    </form>
    
    
    
    
    
    <!-- subnet Search Tab -->
    <form id="subnet_search_form" autocomplete="off">
    <input type="hidden" name="search_form_id" value="subnet_search_form">
    <table id="subnet_search" style="display: none; background-color: {$color['window_content_bg']};" width="100%" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td align="right" class="asearch-line">
            <u>V</u>lan
        </td>
        <td align="left" class="asearch-line">
            <input id="vlan" name="vlandesc" type="text" class="edit" size="32" accesskey="v" />
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            Subnet <u>T</u>ype
        </td>
        <td align="left" class="asearch-line">
            <select id="nettype" name="nettype" class="edit" accesskey="u" accesskey="t" >
                {$subnet_type_list}
            </select>
        </td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            Subnet <u>N</u>ame
        </td>
        <td align="left" class="asearch-line">
            <input id="subnet" name="subnetname" type="text" class="edit" size="32" accesskey="n" />
            <div id="suggest_subnet" class="suggest"></div>
        </td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            <u>I</u>P Address
        </td>
        <td align="left" class="asearch-line" nowrap="true">
            <input id="ip_subnet" name="ip_subnet" class="edit" type="text" size="15" accesskey="i" />
            <div id="suggest_ip_subnet" class="suggest"></div>
            thru
            <input id="ip_subnet_thru" name="ip_subnet_thru" class="edit" type="text" size="15">
            <div id="suggest_ip_subnet_thru" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            &nbsp;
        </td>
        <td align="right" class="asearch-line">
            <input class="button" type="reset" name="reset" value="Clear">
            <input class="button" type="submit" name="search" value="Search" accesskey="s" onClick="toggle_window('{$window_name}'); xajax_window_submit('search_results', xajax.getFormValues('subnet_search_form'));">
        </td>
    </tr>

    </table>
    </form>

EOL;




//////////////////////////////////////////////////////////////////////////////
// Function: ws_more_host_options()
//
// Description:
//   Displays additional drop-downs in the advanced search form.
//////////////////////////////////////////////////////////////////////////////
function ws_more_host_options($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';

    // Build custom attribute list
    list($status, $rows, $records) = db_get_records($onadb, 'custom_attribute_types', 'id >= 1', '');
    $custom_attribute_type_list = '<option value="">&nbsp;</option>\n';
    foreach ($records as $record) {
        $custom_attribute_type_list .= "<option value=\"{$record['id']}\">{$record['name']}</option>\n";
        unset($records, $ca);
    }


    // Build device model list
    list($status, $rows, $records) = db_get_records($onadb, 'models', 'id >= 1');
    $models = array();
    foreach ($records as $record) {
        list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $record['manufacturer_id']));
        $models[$record['id']] = "{$manufacturer['name']}, {$record['name']}";
    }
    asort($models);
    $device_model_list = '<option value="">&nbsp;</option>\n';
    foreach (array_keys($models) as $id) {
        $models[$id] = htmlentities($models[$id]);
        $device_model_list .= "<option value=\"{$id}\">{$models[$id]}</option>\n";
    }
    unset($models, $model);


    // Build device type list
    list($status, $rows, $records) = db_get_records($onadb, 'roles', 'id >= 1', 'name');
    $device_role_list = '<option value="">&nbsp;</option>\n';
    $record['name'] = htmlentities($record['name']);
    foreach ($records as $record) {
        $device_role_list .= "<option value=\"{$record['id']}\">{$record['name']}</option>\n";
    }


    // Build device manufacturer list
    list($status, $rows, $records) = db_get_records($onadb, 'manufacturers', 'ID >= 1', 'name');
    $device_manufacturer_list = '<option value="">&nbsp;</option>\n';
    $record['name'] = htmlentities($record['name']);
    foreach ($records as $record) {
        $device_manufacturer_list .= "<option value=\"{$record['id']}\">{$record['name']}</option>\n";
    }


    // Build the new HTML
    $html = <<<EOL
    <table cellspacing="0" border="0" cellpadding="0">
    <tr>
        <td align="right" class="asearch-line">
            <u>C</u>ustom attribute
        </td>
        <td align="left" class="asearch-line">
            <select id="custom_attribute_type" name="custom_attribute_type" class="edit" accesskey="c">
                {$custom_attribute_type_list}
            </select>
            <u>V</u>alue
            <input id="ca_value" name="ca_value" type="text" class="edit" size="15" accesskey="v" />
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            Device mode<u>l</u>
        </td>
        <td align="left" class="asearch-line">
            <select id="model" name="model" class="edit" accesskey="l">
                {$device_model_list}
            </select>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            Device <u>t</u>ype Role
        </td>
        <td align="left" class="asearch-line">
            <select id="role" name="role" class="edit" accesskey="t">
                {$device_role_list}
            </select>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            Device man<u>u</u>facturer
        </td>
        <td align="left" class="asearch-line">
            <select id="manufacturer" name="manufacturer" class="edit" accesskey="u">
                {$device_manufacturer_list}
            </select>
        </td>
    </tr>
    </table>
EOL;

    $js = "el('more_options_link').style.display = 'none';";

    // Insert the new html
    $response = new xajaxResponse();
    $response->assign("more_host_options",  "innerHTML", $html);
    if ($js) { $response->script($js); }
    return $response;

}


?>
