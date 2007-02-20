<?





//////////////////////////////////////////////////////////////////////////////
// Function:
//     Display FAQ
// 
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $mysql;
    global $color, $style, $images;
    
    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);
    
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    
    // Set a few parameters for the "results" window we're about to create
    $window = array(
        'title' => 'FAQ Viewer',
        'html'  => '',
        'js'    => '',
    );
    
    // If we got a record ID, load it for display
    if (is_numeric($form)) $form['id'] = $form;
    if (is_numeric($form['id'])) {
        list($status, $rows, $faq) = db_get_record($mysql, 'faqs', array('id' => $form['id']));
        if (!$faq['id']) {
            printmsg("NOTICE => Invalid FAQ ID {$form['id']} requested!", 0);
            $response->addScript("alert('Error loading FAQ!  Please try again in a few minutes.');");
            return($response->getXML());
        }
    }
    
    printmsg("INFO => Displaying FAQ: {$faq['q']}", 0);
    
    $window['html'] .= <<<EOL
        <!-- Simple FAQ Viewer -->
        <div style="background-color: {$color['window_content_bg']}; padding: 5px 20px;">
            <div style="font-size: larger; font-weight: bold;">Q: <span style="font-size: smaller;">{$faq['q']}</span></div>
            <div style="font-size: larger; font-weight: bold;">A:</div>
            <div style="padding-left: 10px; width: 500px;">{$faq['a']}</div>
        </div>
EOL;
    
    // Lets build a window and display the results
    return(window_open($window_name, $window));
    
}








?>