<?



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
    global $conf, $self;
    global $font_family, $color, $style, $images;

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // We're building the window in $window and will use window_open() to create the window
    $window = array(
        'title' => "Search Results",
        'html'  => "",
        'js'    => "",
    );

 $js .= "alert('Where: " . str_replace("'", '"', $form['subnet_id']) . "');";

    // Load some html into $window['html']
    $form_id = "{$window_name}_filter_form";
    $content_id = $_SESSION['ona'][$form_id]['content_id'] = "{$window_name}_list";
    $window['html'] .= <<<EOL

    <!-- Tabs & Quick Filter -->
    <table id="{$form_id}_table" width="100%" cellspacing="0" border="0" cellpadding="0" style="margin-top: 0.2em; {$style['borderT']}">
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

    <!-- Item List -->
    <div id="{$content_id}">{$conf['loading_icon']}</div>
EOL;


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

        case 'qsearch_form':
           // If the quick search begins with a "/" it's a command
           if (strpos($form['q'], '/') === 0) {
               $form['q'] = substr($form['q'], 1);
               return(qsearch_command($form['q']));
           }
           // We have to (unfortunatly) have to do a few sql queries to see what kind of
           // search this is and what "tab" we'll be displaying data on.
           list($tab, $form) = quick_search($form['q']);
//$window['js'] .= "alert('Where: " . str_replace("'", '"', $form_id) . "');";
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
//     approperiate display_list() function what to display.
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

    // See if $q identifies a subnet record (by IP, ID, or Description)
    list($status, $rows, $record) = ona_find_subnet($q);
    // If it was, display the associated subnet record
    if ($rows) {
        return( array('subnets', array('subnet_id' => $record['id']) ) );
    }

    // See if $q identifies an interface record (by IP, MAC, etc)
    list($status, $rows, $record) = ona_find_interface($q);
    // If it was, display the associated host record
    if ($rows) {
        return( array('hosts', array('host_id' => $record['id']) ) );
    }


    // Well, I guess we'll assume $q is a hostname/alias search
    return( array('hosts', array('hostname' => $q) ) );
}











//////////////////////////////////////////////////////////////////////////////
// Function:
//     qsearch_command (string $q)
//
// QSearch Command Intrepreter
//////////////////////////////////////////////////////////////////////////////
function qsearch_command($q) {
    global $conf, $self, $images;
    $js = "";

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();

    // Set list row length
    if (strpos($q, 'rows ') === 0) {
        $q = str_replace('rows ', '', $q);
        if (is_numeric($q)) {
            $conf['search_results_per_page'] = $q;
            $_SESSION['search_results_per_page'] = $q;
            $js .= "alert('Lists will now display {$q} rows.');";
        }
    }

    // The typical cloud background
    if ($q == 'clouds') {
        $js .= "el('content_table').style.backgroundRepeat = 'repeat';";
        $js .= "el('content_table').style.backgroundImage = 'url(\'{$images}/skytile.jpg\')';";
        setcookie("pref_bg_url", "{$images}/skytile.jpg", time()+63072000);
        setcookie("pref_bg_repeat", 'repeat', time()+63072000);
    }

    // Disable the clouds background
    if ($q == 'noclouds') {
        $js .= "el('content_table').style.backgroundRepeat = 'no-repeat';";
        $js .= "el('content_table').style.backgroundImage = 'none';";
        setcookie("pref_bg_url", "", time()+63072000);
        setcookie("pref_bg_repeat", 'no-repeat', time()+63072000);
    }

    // Custom backgrounds
    if (strpos($q, 'bg ') === 0) {
        $q = str_replace('bg ', '', $q);
        $js .= "el('content_table').style.backgroundRepeat = 'no-repeat';";
        $js .= "el('content_table').style.backgroundImage = 'url(\'{$q}\')';";
        setcookie("pref_bg_url", $q, time()+63072000);
        setcookie("pref_bg_repeat", 'no-repeat', time()+63072000);
    }

    // Reverse text flow
    if ($q == 'textrtl') $js .= "document.body.style.direction = 'rtl';";

    // Normal text flow
    if ($q == 'textltr') $js .= "document.body.style.direction = 'ltr';";

    // Umm...
    if ($q == 'the cracked eggs')
        $js .= "alert('You found it!\\nThis site was toiled over by Matt Pascoe and Brandon Zehm\\nduring the year of 2006.');";


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

    // Make the old tab look inactive
    $js .= "_el = el('{$form_id}_{$old_tab}_tab'); if (_el) _el.className = 'table-tab-inactive';";

    // Make the new tab look active
    $js .= "el('{$form_id}_{$tab}_tab').className = 'table-tab-active';";

    // Set the "filter" to the correct value
    $js .= "el('{$form_id}_filter').value = '{$_SESSION['ona'][$form_id][$tab]['filter']}';";

    // Set the "page" and "tab" to the correct value
    $js .= "el('{$form_id}_page').value = '{$_SESSION['ona'][$form_id][$tab]['page']}';";
    $js .= "el('{$form_id}_tab').value = '{$tab}';";

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