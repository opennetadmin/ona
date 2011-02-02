<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     _submit ($window_name, $form_id)
//
// Description:
//     Builds a new search results window and sends the browser instructions
//     to make an xajax callback to the proper display_list() function to
//     actually do the search and display results.  $form should be an
//     array of key=>value pairs from the web form.
//////////////////////////////////////////////////////////////////////////////
function ws_search_results_submit($window_name, $form='') {
    global $conf, $self, $onadb;
    global $font_family, $color, $style, $images;

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // We're building the window in $window and will use window_open() to create the window
    $window = array(
        'title' => "Search Results",
        'html'  => "",
        'js'    => "",
    );
    $max_img = "{$images}/silk/bullet_arrow_down.png";
    $min_img = "{$images}/silk/bullet_arrow_up.png";

    // Build subnet type list
    list($status, $rows, $records) = db_get_records($onadb, 'subnet_types', 'id >= 1', 'display_name');
    $subnet_type_list = '<option value="">&nbsp;</option>\n';
    foreach ($records as $record) {
        $record['display_name'] = htmlentities($record['display_name']);
        $subnet_type_list .= "<option value=\"{$record['id']}\">{$record['display_name']}</option>\n";
    }

    // Build subnet type list
    if ($conf['dns_views']) {
        list($status, $rows, $records) = db_get_records($onadb, 'dns_views', 'id >= 0', 'name');
        $dns_view_list = '<option value="">&nbsp;</option>\n';
        foreach ($records as $record) {
            $record['name'] = htmlentities($record['name']);
            $dns_view_list .= "<option value=\"{$record['name']}\">{$record['name']}</option>\n";
        }
    }

    // keep wildcard checkbox value
    if ($form['nowildcard']) $wildchecked = 'checked="yes"';

    // Load some html into $window['html']
    $form_id = "{$window_name}_filter_form";
    $content_id = $_SESSION['ona'][$form_id]['content_id'] = "{$window_name}_list";
    $window['html'] .= <<<EOL

    <!-- Tabs & Quick Filter -->
    <table id="{$form_id}_table" width="100%" cellspacing="0" border="0" cellpadding="0" style="{$style['borderT']} {$style['borderB']}">
        <tr>
            <td id="{$form_id}_blocks_tab" class="table-tab-inactive" onClick="xajax_window_submit('{$window_name}', 'form_id=>{$form_id},tab=>blocks', 'change_tab');">
                Blocks <span id="{$form_id}_blocks_count"></span>
            </td>

            <td id="{$form_id}_vlan_campus_tab" class="table-tab-inactive" onClick="xajax_window_submit('{$window_name}', 'form_id=>{$form_id},tab=>vlan_campus', 'change_tab');">
                Vlan campuses <span id="{$form_id}_vlan_campus_count"></span>
            </td>

            <td id="{$form_id}_subnets_tab" class="table-tab-inactive" onClick="xajax_window_submit('{$window_name}', 'form_id=>{$form_id},tab=>subnets', 'change_tab');">
                Subnets <span id="{$form_id}_subnets_count"></span>
            </td>

            <td id="{$form_id}_hosts_tab" class="table-tab-inactive" onClick="xajax_window_submit('{$window_name}', 'form_id=>{$form_id},tab=>hosts', 'change_tab');">
                Hosts <span id="{$form_id}_hosts_count"></span>
            </td>

            <td id="{$form_id}_records_tab" class="table-tab-inactive" onClick="xajax_window_submit('{$window_name}', 'form_id=>{$form_id},tab=>records', 'change_tab');">
                DNS records <span id="{$form_id}_records_count"></span>
            </td>

            <td style="vertical-align: middle;" class="padding" nowrap="true">
                <img id="adv_search_img" src="{$min_img}" />
                <span id="adv_search_div_toggle"
                    style="text-align: right;"
                    title="Min/Max"
                    onclick="if (el('adv_search_div').style.display=='none') {
                                el('adv_search_div').style.display='';
                                el('toggle_text').innerHTML='Hide search form';
                                el('adv_search_img').src='{$min_img}';
                            } else {
                                el('adv_search_div').style.display='none';
                                el('toggle_text').innerHTML='Show search form';
                                el('adv_search_img').src='{$max_img}';}" >
                <span id="toggle_text" style="font-size: xx-small;">Hide search form</span>
                </span>
            </td>

            <td id="{$form_id}_quick_filter" class="padding" align="right" width="100%">
                <form id="{$form_id}" onSubmit="return false;">
                <input id="{$form_id}_page" name="page" value="1" type="hidden">
                <input id="{$form_id}_tab" name="tab" value="" type="hidden">
                <input name="content_id" value="{$content_id}" type="hidden">
                <input name="form_id" value="{$form_id}" type="hidden">
                    <div id="{$form_id}_filter_overlay"
                         style="position: relative;
                                display: inline;
                                color: #CACACA;
                                cursor: text;"
                         onClick="this.style.display = 'none'; el('{$form_id}_filter').focus();"
                    >Filter</div>
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
                               '    el(\'{$form_id}_page\').value = 1;' +
                               '    xajax_window_submit(\'list_\' + el(\'{$form_id}_tab\').value, xajax.getFormValues(\'{$form_id}\'), \'display_list\');' +
                               '}';
                        timer = setTimeout(code, 700);"
                >
                </form>
            </td>

        </tr>
    </table>

    <div id="adv_search_div" style="background-color: {$color['window_content_bg']};padding-top: 5px;">
    <!-- Block Search Tab -->
    <form id="block_search_form">
    <input type="hidden" name="search_form_id" value="block_search_form">
    <table id="blocks_search" style="display: none;" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td align="right" class="asearch-line">
            <u>B</u>lock name
        </td>
        <td align="left" class="asearch-line">
            <input id="blocks_field1" name="blockname" type="text" class="edit" size="35" accesskey="b" value="{$form['blockname']}" />
            <div id="suggest_hostname" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            &nbsp;
        </td>
        <td align="right" class="asearch-line">
            <input class="button" type="button" name="clear" value="Clear" onClick="clearElements('block_search_form');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results', xajax.getFormValues('block_search_form'));">
        </td>
    </tr>

    </table>
    </form>


    <!-- Vlan Campus Search Tab -->
    <form id="vlan_campus_search_form">
    <input type="hidden" name="search_form_id" value="vlan_campus_search_form">
    <table id="vlan_campus_search" style="display: none;" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td align="right" class="asearch-line">
            <u>C</u>ampus name
        </td>
        <td align="left" class="asearch-line">
            <input id="vlan_campus_field1" name="campusname" type="text" class="edit" size="35" accesskey="c" value="{$form['campusname']}" />
            <div id="suggest_hostname" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            &nbsp;
        </td>
        <td align="right" class="asearch-line">
            <input class="button" type="button" name="clear" value="Clear" onClick="clearElements('vlan_campus_search_form');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results', xajax.getFormValues('vlan_campus_search_form'));">
        </td>
    </tr>

    </table>
    </form>



    <!-- Host Search Tab -->
    <form id="host_search_form">
    <input type="hidden" name="search_form_id" value="host_search_form">
    <table id="hosts_search" style="display: none;" cellspacing="0" border="0" cellpadding="0">

    <tr>
     <td>
      <table cellspacing="0" border="0" cellpadding="0">
        <tr>
            <td align="right" class="asearch-line">
                <u>H</u>ostname
            </td>
            <td align="left" class="asearch-line">
                <input id="hosts_field1" name="hostname" type="text" class="edit" size="35" accesskey="h" value="{$form['hostname']}" />
                <div id="suggest_hostname" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                Subdomain (<u>z</u>one)
            </td>
            <td align="left" class="asearch-line">
                <input id="domain" name="domain" type="text" class="edit" size="35" accesskey="z" value="{$form['domain']}" />
                <div id="suggest_domain" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                <u>M</u>AC
            </td>
            <td align="left" class="asearch-line">
                <input id="mac" name="mac" type="text" class="edit" size="17" accesskey="m" value="{$form['mac']}" />
                <div id="suggest_mac" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                <u>I</u>P Address
            </td>
            <td align="left" class="asearch-line" nowrap="true">
                <input id="ip" name="ip" type="text" class="edit" size="15" accesskey="i" value="{$form['ip']}" />
                <div id="suggest_ip" class="suggest"></div>
                thru
                <input id="ip_thru" name="ip_thru" class="edit" type="text" size="15" value="{$form['ip_thru']}">
                <div id="suggest_ip_thru" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                <u>N</u>otes
            </td>
            <td align="left" class="asearch-line">
                <input id="notes" name="notes" type="text" class="edit" size="17" accesskey="n" value="{$form['notes']}" />
                <div id="suggest_notes" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                <u>L</u>ocation Ref
            </td>
            <td align="left" class="asearch-line">
                <input id="location" class="edit" type="text" name="location" size="8" accesskey="l" value="{$form['location']}" />
                <span id="qf_location_{$window_name}"><img src="{$images}/silk/find.png" border="0"/></span>
                <div id="suggest_location" class="suggest"></div>
            </td>
        </tr>

      </table>

    </td>
    <td>

      <table cellspacing="0" border="0" cellpadding="0">

        <tr>
            <td align="right" class="asearch-line">
                <u>C</u>ustom attribute
            </td>
            <td align="left" class="asearch-line">
                <input id="custom_attribute_type" type="text" name="custom_attribute_type" size="20" class="edit" accesskey="c" value="{$form['custom_attribute_type']}">
                <div id="suggest_custom_attribute_type" class="suggest"></div>
                <u>V</u>alue
                <input id="ca_value" name="ca_value" type="text" class="edit" size="15" accesskey="v" value="{$form['ca_value']}"/>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                Device mode<u>l</u>
            </td>
            <td align="left" class="asearch-line">
                <input id="model" type="text" name="model" class="edit" size="20" accesskey="l" value="{$form['model']}">
                <div id="suggest_model" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                Device <u>t</u>ype Role
            </td>
            <td align="left" class="asearch-line">
                <input id="role" type="text" name="role" class="edit" size="20" accesskey="t" value="{$form['role']}">
                <div id="suggest_role" class="suggest"></div>
            </td>
        </tr>

        <tr>
            <td align="right" class="asearch-line">
                Device man<u>u</u>facturer
            </td>
            <td align="left" class="asearch-line">
                <input id="manufacturer" type="text" name="manufacturer" class="edit" size="20" accesskey="u" value="{$form['manufacturer']}">
                <div id="suggest_manufacturer" class="suggest"></div>
            </td>
        </tr>

      </table>

    </td>
    </tr>

    <tr>
        <td colspan=4 align="right" class="asearch-line">
            Disable Wildcards: <input class="button" type="checkbox" name="nowildcard" {$wildchecked} title="Disable usage of SQL wildcards in queries, you must supply your own in the search form as needed.">
            <input class="button" type="button" name="reset" value="Clear" onClick="clearElements('host_search_form');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results', xajax.getFormValues('host_search_form'));">
        </td>
    </tr>

    </table>
    </form>




    <!-- DNS record Search Tab -->
    <form id="dns_record_search_form">
    <input type="hidden" name="search_form_id" value="dns_record_search_form">
    <table id="records_search" style="display: none;" cellspacing="0" border="0" cellpadding="0">

EOL;

    // Display view dropdown
    if ($conf['dns_views']) {
    $window['html'] .= <<<EOL
    <tr>
        <td align="right" class="asearch-line">
            DNS <u>V</u>iew
        </td>
        <td align="left" class="asearch-line">
            <select id="dns_view" name="dns_view" class="edit" accesskey="v" >
                {$dns_view_list}
            </select>
        </td>
    </tr>
EOL;
    }

    $window['html'] .= <<<EOL
    <tr>
        <td align="right" class="asearch-line">
            <u>H</u>ostname
        </td>
        <td align="left" class="asearch-line">
            <input id="records_field1" name="hostname" type="text" class="edit" size="35" accesskey="h" value="{$form['hostname']}" />
            <div id="suggest_hostname" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            Subdomain (<u>z</u>one)
        </td>
        <td align="left" class="asearch-line">
            <input id="dns_domain" name="domain" type="text" class="edit" size="35" accesskey="z" value="{$form['domain']}" />
            <div id="suggest_dns_domain" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            Type
        </td>
        <td align="left" class="asearch-line">
            <select id="dnstype" name="dnstype" class="edit" accesskey="u" >
                <option></option>
                <option>A</option>
                <option>CNAME</option>
                <option>NS</option>
                <option>PTR</option>
                <option>MX</option>
                <option>SRV</option>
                <option>TXT</option>
            </select>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            <u>I</u>P Address
        </td>
        <td align="left" class="asearch-line" nowrap="true">
            <input id="dns_ip" name="ip" type="text" class="edit" size="15" accesskey="i" value="{$form['ip']}" />
            <div id="suggest_dns_ip" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            <u>N</u>otes
        </td>
        <td align="left" class="asearch-line">
            <input id="notes" name="notes" type="text" class="edit" size="17" accesskey="n" value="{$form['notes']}" />
            <div id="suggest_notes" class="suggest"></div>
        </td>
    </tr>


    <tr>
        <td align="right" class="asearch-line">
            &nbsp;
        </td>
        <td align="right" class="asearch-line">
            Disable Wildcards: <input class="button" type="checkbox" name="nowildcard" {$wildchecked} title="Disable usage of SQL wildcards in queries, you must supply your own in the search form as needed.">
            <input class="button" type="button" name="clear" value="Clear" onClick="clearElements('dns_record_search_form');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results', xajax.getFormValues('dns_record_search_form'));">
        </td>
    </tr>

    </table>
    </form>

    <!-- END DNS record Search Tab -->



    <!-- subnet Search Tab -->
    <form id="subnet_search_form">
    <input type="hidden" name="search_form_id" value="subnet_search_form">
    <table id="subnets_search" style="display: none;" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td align="right" class="asearch-line">
            <u>V</u>lan
        </td>
        <td align="left" class="asearch-line">
            <input id="subnets_field1" name="vlandesc" type="text" class="edit" size="32" accesskey="v" value="{$form['vlandesc']}" />
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
            <input id="subnet" name="subnetname" type="text" class="edit" size="32" accesskey="n" value="{$form['subnetname']}" />
            <div id="suggest_subnet" class="suggest"></div>
        </td>
    </tr>

    <tr>
        <td align="right" class="asearch-line">
            <u>I</u>P Address
        </td>
        <td align="left" class="asearch-line" nowrap="true">
            <input id="ip_subnet" name="ip_subnet" class="edit" type="text" size="15" accesskey="i" value="{$form['ip_subnet']}" />
            <div id="suggest_ip_subnet" class="suggest"></div>
            thru
            <input id="ip_subnet_thru" name="ip_subnet_thru" class="edit" type="text" size="15" value="{$form['ip_subnet_thru']}" />
            <div id="suggest_ip_subnet_thru" class="suggest"></div>
        </td>
    </tr>

        <tr>
            <td align="right" class="asearch-line">
                <u>C</u>ustom attribute
            </td>
            <td align="left" class="asearch-line">
                <input id="custom_attribute_type_net" type="text" name="custom_attribute_type_net" size="20" class="edit" value="{$form['custom_attribute_type_net']}">
                <div id="suggest_custom_attribute_type_net" class="suggest"></div>
                <u>V</u>alue
                <input id="ca_value_net" name="ca_value_net" type="text" class="edit" size="15" value="{$form['ca_value_net']}"/>
            </td>
        </tr>

    <tr>
        <td align="right" class="asearch-line">
            &nbsp;
        </td>
        <td align="right" class="asearch-line">
            Disable Wildcards: <input class="button" type="checkbox" name="nowildcard" {$wildchecked} title="Disable usage of SQL wildcards in queries, you must supply your own in the search form as needed.">
            <input class="button" type="button" name="reset" value="Clear" onClick="clearElements('subnet_search_form');">
            <input class="button" type="button" name="search" value="Search" accesskey="s" onClick="xajax_window_submit('search_results', xajax.getFormValues('subnet_search_form'));">
        </td>
    </tr>

    </table>
    </form>
    <center><span style="font-weight: bold;color: green; font-size: small;" id="search_results_msg"></span></center>
    </div>

    <!-- Item List -->
    <div id="{$content_id}">{$conf['loading_icon']}</div>
EOL;

//TODO: this is a test I did on having a click focus bring up the full list of entries.. kinda like a dropdown dialog.. 
//onfocus="suggest_display('role', 'suggest_role');searchKeyDown('37', el('role'), 'suggest_role');"

    // Before we can let the browser call "display_list"
    // we need to make sure we know what type of list we'll be displaying
    switch ($form['search_form_id']) {
        case 'host_search_form':
           $tab = 'hosts';
           break;

        case 'subnet_search_form':
           $tab = 'subnets';
           break;

        case 'block_search_form':
           $tab = 'blocks';
           break;

        case 'vlan_campus_search_form':
           $tab = 'vlan_campus';
           break;

        case 'dns_record_search_form':
           $tab = 'records';
           break;

        case 'qsearch_form':
           // If the quick search begins with a "/" it's a command
           if (strpos($form['q'], '/') === 0) {
               $form['q'] = substr($form['q'], 1);
               return(qsearch_command($form['q']));
           }
           // We (unfortunately) have to do a few sql queries to see what kind of
           // search this is and what "tab" we'll be displaying data on.
           list($tab, $form) = quick_search($form['q']);
           break;
    }

    // Save a few things in the session (the search query, page, and tab)
    $_SESSION['ona'][$form_id]['tab'] = $tab;
    $_SESSION['ona'][$form_id][$tab]['q'] = $form;


    // A little javascript for the browser to run once we've created the window
    $window['js'] .= <<<EOL
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

        /* Save the new tab and make it look active */
        el('{$form_id}_tab').value = '{$tab}';
        el('{$form_id}_{$tab}_tab').className = 'table-tab-active';
        el('{$tab}_search').style.display = 'block';

        /* make the first field have focus */
        el('{$tab}_field1').focus();

        suggest_setup('hosts_field1', 'suggest_hostname');
        suggest_setup('domain',     'suggest_domain');
        suggest_setup('dns_domain', 'suggest_dns_domain');
        suggest_setup('mac',      'suggest_mac');
        suggest_setup('ip',       'suggest_ip');
        suggest_setup('dns_ip',   'suggest_dns_ip');
        suggest_setup('ip_thru',  'suggest_ip_thru');
        suggest_setup('notes',    'suggest_notes');
        suggest_setup('ip_subnet', 'suggest_ip_subnet');
        suggest_setup('ip_subnet_thru',  'suggest_ip_subnet_thru');
        suggest_setup('subnet', 'suggest_subnet');
        suggest_setup('location', 'suggest_location');
        suggest_setup('custom_attribute_type', 'suggest_custom_attribute_type');
        suggest_setup('custom_attribute_type_net', 'suggest_custom_attribute_type_net');
        suggest_setup('role', 'suggest_role');
        suggest_setup('model', 'suggest_model');
        suggest_setup('manufacturer', 'suggest_manufacturer');


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

        /* Display the list of results */
        xajax_window_submit('list_' + el('{$form_id}_tab').value, xajax.getFormValues('{$form_id}'), 'display_list');



EOL;


    // Lets build a window and display the results
    return(window_open($window_name, $window));
}












//////////////////////////////////////////////////////////////////////////////
// Function:
//     quick_search (string $q)
//
// Description:
//     If a quick-search is being performed, find out if we'll be displaying
//     subnets or hosts, and return a $tab and $form that will tell the
//     appropriate display_list() function what to display.
//////////////////////////////////////////////////////////////////////////////
function quick_search($q) {
    global $conf, $self;

    //
    // *** Quick Search ***
    //
    //     If it's an IP or MAC address (string or numeric):
    //         Look for an interface and display associated host record
    //         Look for the subnet that IP is on and display a single subnet record
    //     If it's a string:
    //         Look for a hostname
    //         Look for an alias name (and display associated hosts)
    //         Look for a subnet name

    printmsg("DEBUG => quick_search({$q}) called", 3);

    // Check to see if it is a MAC.. do it here instead of in the next interface section
    // so that we can properly find multiple hosts with the same mac
    $mac = mac_mangle($q, 1);
    if ($mac != -1) {
        printmsg("DEBUG => quick_search() Looks like a MAC, Returning mac = {$q}" ,3);
        return( array('hosts', array('mac' => $q) ) );
    }

    // See if $q identifies an interface record (by IP, MAC, etc)
    list($status, $rows, $record) = ona_find_interface($q);
    // If it was, display the associated host record
    if ($rows) {
        printmsg("DEBUG => quick_search() returning host match (ID={$record['host_id']})", 3);
        return( array('hosts', array('host_id' => $record['host_id']) ) );
    }

    // See if $q identifies a subnet record (by IP, ID, or Description)
    list($status, $rows, $record) = ona_find_subnet($q);
    // If it was, display the associated subnet record
    if ($rows) {
        printmsg("DEBUG => quick_search() returning subnet match (ID={$record['id']})", 3);
        return( array('subnets', array('subnet_id' => $record['id']) ) );
    }

    // Well, I guess we'll assume $q is a hostname/alias search
    printmsg("DEBUG => quick_search() found no subnet or host match. Returning hostname = {$q}" ,3);
    return( array('hosts', array('hostname' => $q) ) );
}











//////////////////////////////////////////////////////////////////////////////
// Function:
//     qsearch_command (string $q)
//
// QSearch Command Intrepreter
//////////////////////////////////////////////////////////////////////////////
function qsearch_command($q) {
    global $conf, $self, $images, $baseURL;
    $js = "";

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();

    // bz: FIXME!! Most of these should be moved to a user preferences application

    // Set list row length
    // Note that when you remove this, you must also remove a few lines in the ona_functions include file
    if (strpos($q, 'rows ') === 0) {
        $q = str_replace('rows ', '', $q);
        if (is_numeric($q)) {
            $conf['search_results_per_page'] = $q;
            $_SESSION['search_results_per_page'] = $q;
            $js .= "alert('Lists will now display {$q} rows.');";
        }
    }

    if (strpos($q, 'context ') === 0) {
        $q = str_replace('context ', '', $q);
        if ($q) {
            setcookie("ona_context_name", $q);
            $js .= "alert('Database context is now: {$q}.');";
        }
    }

    // Reverse text flow
    if ($q == 'textrtl') $js .= "document.body.style.direction = 'rtl';";

    // Normal text flow
    if ($q == 'textltr') $js .= "document.body.style.direction = 'ltr';";


    if ($js) {
        $js .= "el('qsearch').value = ''; el('suggest_qsearch').style.display = 'none';";
        $response->addScript($js);
        return($response->getXML());
    }
    else {
        $response->addScript("alert('Invalid command!');");
        return($response->getXML());
    }
}












//////////////////////////////////////////////////////////////////////////////
// Function:
//     change_tab (string $window_name, string $form_id, string $tab)
//
// Description:
//     This function changes the "tab" a person is viewing by setting the
//     new tab value in the _SESSION and then instructing the browser to do
//     an xajax callback to the display_list() function.
//////////////////////////////////////////////////////////////////////////////
function ws_change_tab($window_name, $form, $display_list=1, $return_text=0) {
    global $conf, $self;

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);
    $form_id = $form['form_id'];
    $tab = $form['tab'];

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Save the new tab in the session
    $old_tab = $_SESSION['ona'][$form_id]['tab'];
    $_SESSION['ona'][$form_id]['tab'] = $tab;

    // remove any messages
    $js .= "el('search_results_msg').innerHTML = '';";

    // Make the old tab look inactive
    $js .= "_el = el('{$form_id}_{$old_tab}_tab'); if (_el) _el.className = 'table-tab-inactive'; el('{$old_tab}_search').style.display = 'none';";

    // Make the new tab look active
    $js .= "el('{$form_id}_{$tab}_tab').className = 'table-tab-active'; el('{$tab}_search').style.display = 'block';";

    // Set the "filter" to the correct value
    $js .= "el('{$form_id}_filter').value = '{$_SESSION['ona'][$form_id][$tab]['filter']}';";

    // Set the "page" and "tab" to the correct value
    $js .= "el('{$form_id}_page').value = '{$_SESSION['ona'][$form_id][$tab]['page']}';";
    $js .= "el('{$form_id}_tab').value = '{$tab}';";

    // Put the cursor in the first field
    $js .= "_el = el('{$tab}_field1'); if (_el) el('{$tab}_field1').focus();";

    // Hide/show the filter overlay
    $js .= "el('{$form_id}_filter_overlay').style.display = (el('{$form_id}_filter').value == '') ? 'inline' : 'none';";

    // Tell the browser to ask for a new list of data
    if ($display_list) {
        $js .= "xajax_window_submit('list_{$tab}', xajax.getFormValues('{$form_id}'), 'display_list');";
    }

    // If they asked us to return text, we return the javascript text..
    if ($return_text) {
        return($js);
    }

    // Send an XML response to the window
    $response->addAssign($_SESSION['ona'][$form_id]['content_id'], 'innerHTML', $conf['loading_icon']);
    $response->addScript($js);
    return($response->getXML());
}














?>
