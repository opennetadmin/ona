<?


// We need to build the html drop-down boxes for the
// "Classification", "Device model", "Device type", and "Device manufacturer" fields.
global $onadb;


// Build subnet type list
list($status, $rows, $records) = db_get_records($onadb, 'subnet_types', 'id >= 1', 'name');
$subnet_type_list = '<option value="">&nbsp;</option>\n';
$record['name'] = htmlentities($record['name']);
foreach ($records as $record) {
    $subnet_type_list .= "<option value=\"{$record['id']}\">{$record['name']}</option>\n";
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
    suggest_setup('unit_number', 'suggest_unit_number');
    suggest_setup('unit_number_subnet', 'suggest_unit_number_subnet');
    suggest_setup('subnet', 'suggest_subnet');
    el('host_search_form').onsubmit = function() { return false; };
    el('subnet_search_form').onsubmit = function() { return false; };
    
    /* Setup the Quick Find Unit icon */
    var _button = el('qf_unit_{$window_name}');
    _button.style.cursor = 'pointer';
    _button.onclick = 
        function(ev) {
            if (!ev) ev = event;
            /* Create the popup div */
            wwTT(this, ev, 
                 'id', 'tt_qf_unit_{$window_name}', 
                 'type', 'static',
                 'direction', 'south',
                 'delay', 0,
                 'styleClass', 'wwTT_qf',
                 'javascript', 
                 "xajax_window_submit('tooltips', '" + 
                     "tooltip=>qf_unit," + 
                     "id=>tt_qf_unit_{$window_name}," +
                     "input_id=>unit_number');"
            );
        };

    /* Setup the Quick Find Unit icon */
    var _button = el('qf_unit_subnet_{$window_name}');
    _button.style.cursor = 'pointer';
    _button.onclick = 
        function(ev) {
            if (!ev) ev = event;
            /* Create the popup div */
            wwTT(this, ev, 
                 'id', 'tt_qf_unit_{$window_name}', 
                 'type', 'static',
                 'direction', 'south',
                 'delay', 0,
                 'styleClass', 'wwTT_qf',
                 'javascript', 
                 "xajax_window_submit('tooltips', '" + 
                     "tooltip=>qf_unit," + 
                     "id=>tt_qf_unit_{$window_name}," +
                     "input_id=>unit_number_subnet');"
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
    <form id="host_search_form">
    <input type="hidden" name="search_form_id" value="host_search_form">
    <table style="background-color: {$color['window_content_bg']};" id="host_search" width="100%" cellspacing="0" border="0" cellpadding="0">
    
    <tr>
        <td align="right" class="asearch-line">
            <u>H</u>ostname
        </td>
        <td align="left" class="asearch-line">
            <input id="hostname" name="hostname" type="text" class="edit" size="17" accesskey="h" />
            <div id="suggest_hostname" class="suggest"></div>
        </td>
    </tr>
    
    <tr>
        <td align="right" class="asearch-line">
            Subdomain (<u>z</u>one)
        </td>
        <td align="left" class="asearch-line">
            <input id="domain" name="domain" type="text" class="edit" size="17" accesskey="z" />
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
            <u>U</u>nit number
        </td>
        <td align="left" class="asearch-line">
            <input id="unit_number" class="edit" type="text" name="unit" size="8" accesskey="u" />
            <span id="qf_unit_{$window_name}"><img src="{$images}/silk/find.png" border="0"/></span>
            <div id="suggest_unit_number" class="suggest"></div>
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
    <form id="subnet_search_form">
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
            <u>U</u>nit number
        </td>
        <td align="left" class="asearch-line">
            <input id="unit_number_subnet" class="edit" type="text" name="unit" size="8" accesskey="u" />
            <span id="qf_unit_subnet_{$window_name}"><img src="{$images}/silk/find.png" border="0"/></span>
            <div id="suggest_unit_number_subnet" class="suggest"></div>
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
    
    // Build classification list
    list($status, $rows, $records) = db_get_records($onadb, 'INFOBIT_TYPES_B', 'ID >= 1', 'NAME');
    $classification_list = '<option value="">&nbsp;</option>\n';
    foreach ($records as $record) {
        list($status, $rows, $infobits) = db_get_records($onadb, 'INFOBITS_B', array('INFOBIT_TYPE_ID' => $record['ID']), 'VALUE');
        $record['NAME'] = htmlentities($record['NAME']);
        $infobit['VALUE'] = htmlentities($infobit['VALUE']);
        foreach ($infobits as $infobit) {
            $classification_list .= "<option value=\"{$infobit['ID']}\">{$record['NAME']} ({$infobit['VALUE']})</option>\n";
        }
        unset($infobits, $infobit);
    }
    
    
    // Build device model list
    list($status, $rows, $records) = db_get_records($onadb, 'DEVICE_MODELS_B', 'ID >= 1');
    $models = array();
    foreach ($records as $record) {
        list($status, $rows, $manufacturer) = ipdb_get_manufacturer_record(array('ID' => $record['MANUFACTURER_ID']));
        list($status, $rows, $type) = ipdb_get_device_type_record(array('ID' => $record['DEVICE_TYPE_ID']));
        $models[$record['ID']] = "{$manufacturer['MANUFACTURER_NAME']} {$record['MODEL_DESCRIPTION']} ({$type['DEVICE_TYPE_DESCRIPTION']})";
    }
    asort($models);
    $device_model_list = '<option value="">&nbsp;</option>\n';
    foreach (array_keys($models) as $id) {
        $models[$id] = htmlentities($models[$id]);
        $device_model_list .= "<option value=\"{$id}\">{$models[$id]}</option>\n";
    }
    unset($models, $model);
    
    
    // Build device type list
    list($status, $rows, $records) = db_get_records($onadb, 'DEVICE_TYPES_B', 'ID >= 1', 'DEVICE_TYPE_DESCRIPTION');
    $device_type_list = '<option value="">&nbsp;</option>\n';
    $record['DEVICE_TYPE_DESCRIPTION'] = htmlentities($record['DEVICE_TYPE_DESCRIPTION']);
    foreach ($records as $record) {
        $device_type_list .= "<option value=\"{$record['ID']}\">{$record['DEVICE_TYPE_DESCRIPTION']}</option>\n";
    }
    
    
    // Build device manufacturer list
    list($status, $rows, $records) = db_get_records($onadb, 'MANUFACTURERS_B', 'ID >= 1', 'MANUFACTURER_NAME');
    $device_manufacturer_list = '<option value="">&nbsp;</option>\n';
    $record['MANUFACTURER_NAME'] = htmlentities($record['MANUFACTURER_NAME']);
    foreach ($records as $record) {
        $device_manufacturer_list .= "<option value=\"{$record['ID']}\">{$record['MANUFACTURER_NAME']}</option>\n";
    }
    
    
    // Build the new HTML
    $html = <<<EOL
    <table cellspacing="0" border="0" cellpadding="0">
    <tr>
        <td align="right" class="asearch-line">
            <u>C</u>lassification
        </td>
        <td align="left" class="asearch-line">
            <select id="classification" name="classification" class="edit" accesskey="c">
                {$classification_list}
            </select>
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
            Device <u>t</u>ype
        </td>
        <td align="left" class="asearch-line">
            <select id="type" name="type" class="edit" accesskey="t">
                {$device_type_list}
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
    $response->addAssign("more_host_options",  "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
    
}


?>
