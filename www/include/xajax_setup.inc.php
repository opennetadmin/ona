<?php
//////////////////////////////////////////////////////////////////////////////
// 
// Xajax Setup
// This file is a site specific file that loads Xajax and several "modules"
// that we use.  We are currently loading at least xajax, 
// 
//////////////////////////////////////////////////////////////////////////////

// Load Xajax and create an xajax object
require_once("{$include}/xajax/xajax.inc.php");
$xajax = new xajax();



// Load various modules 
// (registering several functions with xajax and loading additional js into the page)
require_once("{$include}/xajax_drag/drag.inc.php");
$conf['html_headers'] .= '<script type="text/javascript" src="'.$baseURL.'/include/xajax_drag/drag.js"></script>' . "\n";

require_once("{$include}/xajax_suggest/suggest.inc.php");
$conf['html_headers'] .= '<script type="text/javascript" src="'.$baseURL.'/include/xajax_suggest/suggest.js"></script>' . "\n";
$conf['html_headers'] .= '<link rel="stylesheet" type="text/css" href="'.$baseURL.'/include/xajax_suggest/suggest.css" />' . "\n";

require_once("{$include}/xajax_webwin/webwin.inc.php");
$conf['html_headers'] .= '<script type="text/javascript" src="'.$baseURL.'/include/xajax_webwin/webwin.js"></script>' . "\n";
$conf['html_headers'] .= '<script type="text/javascript" src="'.$baseURL.'/include/xajax_webwin/webwinTT.js"></script>' . "\n";
$conf['html_headers'] .= '<link rel="stylesheet" type="text/css" href="'.$baseURL.'/include/xajax_webwin/webwin.css" />' . "\n";


// These aren't AJAX, but it's part of our "Advanced Development Kit" ;-)

// NIFTY CORNERS
// $conf['html_headers'] .= '<script type="text/javascript" src="'.$baseURL.'/include/js/nifty_corners/nifty.js"></script>' . "\n";
// $conf['html_headers'] .= '<link rel="stylesheet" type="text/css" href="'.$baseURL.'/include/js/nifty_corners/nifty_styles.css" />' . "\n";
// $conf['html_headers'] .= '<link rel="stylesheet" type="text/css" href="'.$baseURL.'/include/js/nifty_corners/nifty_print.css" media="print"/>' . "\n";

// DomTT
// $conf['html_headers'] .= '<script type="text/javascript" src="'.$baseURL.'/include/js/domTT/domLib.js"></script>' . "\n";
// $conf['html_headers'] .= '<script type="text/javascript" src="'.$baseURL.'/include/js/domTT/domTT.js"></script>' . "\n";



// Process xajax requests 
$xajax->processRequests();


// Include the xajax javascript in our html headers
$conf['html_headers'] .= $xajax->getJavascript($baseURL . '/include/xajax');










//////////////////////////////////////////////////////////////////////////////
// Function: parse_options_string($string)
// Returns an array from a string of comma separated key => value pairs.
// If $string doesn't look like an array the original string is returned.
// I.E. "id => 14, name=>this is a test" becomes:
//      array('id' => 14, 'name' => 'this is a test');
//////////////////////////////////////////////////////////////////////////////
function parse_options_string($input) {
    $array = array();

    // If the user supplied an array in a string, build the array and store it in $array
    if (is_string($input) and preg_match('/=>/', $input)) {
        $parts = split(',', $input);
        foreach ($parts as $part) {
            $part = split('=>', $part, 2);
            $array[trim($part[0])] = trim($part[1]);
        }
        return($array);
    }
    return($input);
}





//////////////////////////////////////////////////////////////////////////////
// Function: get_page_links()
// Returns html links for paging lists of data.
// When a link is clicked it sends an xajax callback to 
//   window_submit($window_name, $page_num, 'change_page');
// That means in your "winc/{$window_name}.inc.php" you need to have a
// function called 'change_page()' defined!
//////////////////////////////////////////////////////////////////////////////
function get_page_links($page=1, $per_page=1, $total=1, $window_name='', $form_id='') {
    $html = '';
    
    // Build some variables
    $max_page_links = 10; // MUST be divisible by 2
    
    // Number of total pages available
    $total_pages = ceil($total/$per_page);
    if ($total_pages == 1) { return(''); }
    
    // Find the first page link to display
    $first_link = 1;
    if ($page > ($max_page_links/2)) {
        $first_link = (($page+1) - ($max_page_links/2));
    }
    
    $html .= <<<EOL
    
    <!-- Page Links -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 0.2em;">
        <tr>
            <td id="page_links" style="font-weight: bold; " class="padding" align="center">
EOL;

// Previous Page
if ($page > 1) {
    $tmp = $page - 1;
    $html .= <<<EOL
        <a title="Previous page"
           style="cursor: pointer;"
           onClick="xajax_window_submit('{$window_name}', 'page=>{$tmp},form_id=>{$form_id}', 'change_page');"
        >Back</a>&nbsp;&nbsp;
EOL;
}

// Page Links
$i = $first_link;
while ($i <= $total_pages and ($i <= $max_page_links or $i <= ($page + ($max_page_links/2))) ) {
    if ($page == $i) {
        $html .= "\n<font color=\"#FF6F5F\">{$i}</font>&nbsp;";
    }
    else {
    $html .= <<<EOL
        <a title="Page {$i}"
           style="cursor: pointer;"
           onClick="xajax_window_submit('{$window_name}', 'page=>{$i},form_id=>{$form_id}', 'change_page');"
        >{$i}</a>&nbsp;
EOL;
    }
    $i++;
}
$i--;
$html .= "&nbsp;";

// Next Page Link
if ($i > $page) {
    $tmp = $page + 1;
    $html .= <<<EOL
        <a title="Next page"
           style="cursor: pointer;"
           onClick="xajax_window_submit('{$window_name}', 'page=>{$tmp},form_id=>{$form_id}', 'change_page');"
        >Next</a>
EOL;
}


$html .= <<<EOL

            </td>
        </tr>
    </table>
    
EOL;
    
    return($html);
}





//////////////////////////////////////////////////////////////////////////////
// Function:
//     change_page (string $window_name, int $page)
// 
// Description:
//     This function changes the "page" a person is viewing by setting the
//     new page value in a hidden input field and then instructing the
//     browser to do an xajax callback to the display_list() function.
//     
//     $form NEEDS form_id => id && page => page number
//////////////////////////////////////////////////////////////////////////////
function ws_change_page($window_name, $form) {
    global $conf, $self;
    
    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';
    
    // Basically we're going to update the value of the input field called "page" 
    // in the "filter" form. Then we just have the browser do an xajax callback to 
    // update the list being displayed.
    $js .= "el('{$form['form_id']}_page').value = '{$form['page']}';";
    $js .= "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_list');";
    
    // Send an XML response to the window
    $response->addScript($js);
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// Queries the database and returns an array of suggestions
// based on the criteria provided.  Note that $q should usually
// have a % on the beginning or end of the string.
//////////////////////////////////////////////////////////////////////////////
function get_text_suggestions($q="", $table="", $field="", $max_results=10) {
    global $conf, $self, $onadb;
    $results = array();
    
    // Don't return anything if we didn't get anything
    if (!$q or ($max_results < 1) or !$table or !$field) { return($results); }
    
    $where  = "{$field} LIKE " . $onadb->qstr($q);
    $order  = "{$field} ASC";

    
    // Search the db for results
    list ($status, $rows, $records) = db_get_records(
                                        $onadb,
                                        $table,
                                        $where,
                                        $order,
                                        $max_results
                                      );
    
    // If the query didn't work return the error message
    if ($status) { $results[] = "Internal Error: {$self['error']}"; }
    
    foreach ($records as $record) {
        $results[] = $record[$field];
    }
    
    // Return the records
    return($results);
}



function get_username_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q.'%', 'users', 'username', $max_results));
}




//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_username($q, $el_input, $el_suggest) {
    global $conf;
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";
    
    // Search the DB
    $results = get_username_suggestions($q);
    $results = array_merge($results, get_username_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);
    
    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";
    
    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_tse_username($q, $el_input, $el_suggest) {
    return(suggest_username($q, $el_input, $el_suggest));
}



?>