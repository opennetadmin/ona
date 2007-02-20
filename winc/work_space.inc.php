<?



//////////////////////////////////////////////////////////////////////////////
// Function:
//     _submit ($window_name, $javascript)
// 
// Description:
//     Builds a new "work space" window, and sends the javascript passed in
//     back to the browser to execute after the window is built.
//////////////////////////////////////////////////////////////////////////////
function ws_work_space_submit($window_name, $javascript='') {
    global $conf, $self, $color, $style, $images;
    
    /****     UPDATE THIS WINDOW'S "HISTORY"     ****/
    if (!is_array($_SESSION['ona'][$window_name]['history'])) {
        $_SESSION['ona'][$window_name]['history'] = array();
    }
    
    // Get the "URL" and it's "Title"
    $title = '';
    if (preg_match("/xajax_window_submit\('([^']+)'/", $javascript, $matches)) { $title = $matches[1]; }
    
    // Remove old history items that are duplicates of the current "URL"
    $new_history = array();
    foreach ($_SESSION['ona'][$window_name]['history'] as $history) {
        if ($history['url'] != $javascript) {
            array_push($new_history, $history);
        }
    }
    $_SESSION['ona'][$window_name]['history'] = $new_history;
    
    // Add the current "URL"
    array_push($_SESSION['ona'][$window_name]['history'], array('title' => $title, 'type' => $title, 'url' => $javascript));
    
    
    // If there are to many url's in the history, trim some.
    while (count($_SESSION['ona'][$window_name]['history']) > 7) {
        array_shift($_SESSION['ona'][$window_name]['history']);
    }
    
    
    
    // We're building the window in $window and will use window_open() to create the window
    $window = array(
        'title' => "Search Results",
        'html'  => "",
        'js'    => "",
    );
    
    
    // Set the window title:
    $window['title'] = "Work Space";


// Define javascript to run after the window is created
$window['js'] .= <<<EOL
    /* Hide the "Search Results" box if it's visible */
    var _el = el('search_results');
    if (_el && (_el.style.visibility == "visible" || _el.style.display == "block")) {
        _el.style.visibility = "hidden";
        _el.style.display = "none";
    }
    
    var _el = el('{$window_name}');
    
    /* Get the size of the box we live "in" */
    var content_top    = calcOffset(el('content_table'), 'offsetTop');
    var content_left   = calcOffset(el('content_table'), 'offsetLeft');
    var content_width  = el('content_table').offsetWidth;
    var content_height = el('content_table').offsetHeight;
    
    /* Now calculate where we will sit .. the -4 and -2 are for borders */
    var my_top  = content_top;
    var my_left  = content_left;
    var my_width  = content_width + (content_left - my_left) - 4;
    var my_height = content_height + (content_top - my_top) - 2;
    if (browser.isIE) {
        my_width  += 4;
        my_height += 2;
    }
    
    /* Finally reposition/resize the window, hide any overflow, and bring it up behind other windows. */
    _el.style.top    = my_top    + 'px';
    _el.style.left   = my_left   + 'px';
    _el.style.width  = my_width  + 'px';
    _el.style.height = my_height + 'px';
    _el.style.zIndex = 1;
    _el.style.overflow = 'hidden';
    
    /* Now disable the drag library from moving this "window" */
    el('{$window_name}').onclick = function() { return true; };
    el('{$window_name}_title').onmousedown = function() { return true; };
    el('{$window_name}_title').style.cursor = 'default';
    
    /* Make sure the title bar goes all the way across */
    el('{$window_name}_title_table').style.width = my_width  + 'px';
    
    /* Gray the title bar */
    el('{$window_name}_title_table').style.backgroundColor = '#A6A6A6';
    
    /* Make the content peice scroll */
    _el = el('{$window_name}_content');
    _el.style.width  = my_width  + 'px';
    _el.style.height = (my_height - el('{$window_name}_title').offsetHeight - el('{$window_name}_history').offsetHeight) + 'px';
    _el.style.overflow = 'auto';
    
    {$javascript}
EOL;


    // Define the window's inner html - 
    // start with a "history" bar at the top.
    $window['html'] .= <<<EOL
    
    <div id="{$window_name}_history" style="font-size: smaller; background-color: #EDEEFF;">

EOL;
    $window['html'] .= ws_rewrite_history($window_name, ' ', 1);
    $window['html'] .= <<<EOL
    
    </div>
    
    <div id="{$window_name}_content">
        <br><br><br><br><center><img src="{$images}/loading.gif" /></center>
    </div>
    
EOL;
    
    
    
    // Lets build a window and display the results
    return(window_open($window_name, $window));

}










//////////////////////////////////////////////////////////////////////////////
// Function:
//     rewrite_history ($window_name, $null, $return_html)
// 
// Description:
//     Rewrites the hitory div in the work_space window.
//     If $return_html == 1 the raw html is returned rather than returning
//     and XML response to update it.
//     This also updates the work space window's title.
//////////////////////////////////////////////////////////////////////////////
function ws_rewrite_history($window_name, $null='', $return_html=0) {
    global $conf, $self, $color, $style, $images;
    
    $html = $js = '';
    
    $html .= "Trace: ";
    $and = '';
    foreach($_SESSION['ona'][$window_name]['history'] as $history) {
        $history['title'] = htmlentities($history['title'], ENT_QUOTES);
        $history['type'] = htmlentities($history['type'], ENT_QUOTES);
        $history['url'] = str_replace(array("'", '"'), array("\\'", '\\"'), $history['url']);
        $history['url'] = htmlentities($history['url'], ENT_QUOTES);
        $html .= <<<EOL
{$and}<a title="{$history['type']}: {$history['title']}" onClick="xajax_window_submit('work_space', '{$history['url']}');">{$history['title']}</a>&nbsp;
EOL;
        $and = '&nbsp;&gt;&gt;&nbsp;';
    }
    
    if ($return_html) {
        return($html);
    }
    
    // Update the work_space window's title
    $history = end($_SESSION['ona'][$window_name]['history']);
    $new_title = "Work Space: {$history['type']}: {$history['title']}";
    
    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_title", "innerHTML", $new_title);
    $response->addAssign("work_space_history", "innerHTML", $html);
    return($response->getXML());
}




?>