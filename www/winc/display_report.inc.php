<?php


//////////////////////////////////////////////////////////////////////////////
// Function: ws_display()
//
// Description:
//   Displays a report in the work_space div.
//////////////////////////////////////////////////////////////////////////////
function ws_display($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Load the report include file
    if (!get_report_include($form['report'])) {
        array_pop($_SESSION['ona']['work_space']['history']);
        $html .= "<br><center><font color=\"red\"><b>The report {$form['name']} doesn't exist!</b></font></center>";
        $response = new xajaxResponse();
        $response->addAssign("work_space_content", "innerHTML", $html);
        return($response->getXML());
    }

    // Update History Title
    $history = array_pop($_SESSION['ona']['work_space']['history']);
    $js .= "xajax_window_submit('work_space', ' ', 'rewrite_history');";
    if ($history['title'] == $window_name) {
        $history['title'] = $form['report'];
        array_push($_SESSION['ona']['work_space']['history'], $history);
    }

    // Get the html form for this report
    list($status, $rpt_html_form, $rpt_js) = rpt_html_form($form['report'],$form);

    $html .= <<<EOL
    <!-- FORMATTING TABLE -->
    <div class="content_box">
        {$rpt_html_form}
    </div>
    <!-- END OF TOP SECTION -->

    <!-- REPORT CONTENT -->

    <div id='report_content'>
        {$conf['loading_icon']}
    </div>


EOL;

    // Now tell the window to call the actual code to run the report and replace the loading_icon
    $js .= "xajax_window_submit('display_report', xajax.getFormValues('{$form['report']}_report_form'), 'run_report');";

    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("work_space_content", "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    if ($rpt_js) { $response->addScript($rpt_js); }
    return($response->getXML());
}



//////////////////////////////////////////////////////////////////////////////
// Function: ws_run_report()
//
// Description:
//   Executes the report and replaces the report_content div with the output
//////////////////////////////////////////////////////////////////////////////
function ws_run_report($window_name, $form='') {

    // Load the report include file (again!)
    if (get_report_include($form['report'])) {
        // Run the report and put it in the report_content box
        list($status, $report_output) = rpt_run($form, 'html');
        if($status)
            $report_output = "ERROR => There was a problem running this report!";
    }

    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("report_content", "innerHTML", $report_output);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}













?>