<?php

//
// This file is used in two ways:
//   1. It can be accessed from a web browser for testing/running modules
//   2. It is called by dcm.pl for running modules from the console
//   3. It may be included after setting a few variables to run a module from another web page.
//


/* -------------------- COMMON HEADER ---------------------- */

// Find the include directory
$base = dirname(__FILE__);
while ($base and (!is_dir($base . '/include')) ) {
    $base = preg_replace('+/[^/]*$+', '', $base);
}   $include = $base . '/include';
if (!is_dir($include)) {
    print "ERROR => Couldn't find include folder!\n"; exit;
}

require_once($base . '/config/config.inc.php');
require_once($conf['inc_functions']);

/* --------------------------------------------------------- */

// Start the session and make sure they're logged in
//securePage();


// Update page title
$conf['title'] .= ' - Module Run';

// Links for the Top Bar
$self['tbar_links'] .= htmlentities(' -> ') . '<a href="'.$baseURL.'/modules/">Datacom Modules</a>';


// These store the output to be displayed
$status = 0;
$output = "";


// If the user set the "nohtml" flag, use it.
$self['nohtml'] = 0;
if (isset($_REQUEST['nohtml'])) {
    if ($_REQUEST['nohtml'] != '' and $_REQUEST['nohtml'] != 'off' and $_REQUEST['nohtml'] != '0') {
        $self['nohtml'] = 1;
        printmsg("DEBUG => nohtml flag present", 3);
    }
}


// Display the current debug level if it's above 1
printmsg("DEBUG => debug level: {$conf['debug']}", 3);


// If the user specified an IPDB context, lets make sure it's valid
// and then use it.  We have to do this before we connect to the database.
if (isset($_REQUEST['ona_context']) and $_REQUEST['ona_context']) {
    if (isset($db_context['ona'][$_REQUEST['ona_context']])) {
        $conf['ona_context'] = $_REQUEST['ona_context'];
    }
    else {
        $output = "ERROR => Specified ona context is invalid\n";
        $status = 1;
        if (!$_REQUEST['nooutput']) {
            output($status, $output);
        }
    }
}


// Connect to the MySQL and IPDB databases
require_once($conf['inc_db']);


// Get a list of the valid "modules" and their descriptions.
if (!is_array($self['cache']['modules']) or !array_key_exists('get_module_list', $self['cache']['modules'])) {
    load_module('get_module_list');
    list($status, $self['cache']['modules']) = get_module_list('type=array');
}


/* ----------- RUN A MODULE IF NEEDED ------------ */
if (isset($_REQUEST['module']) and (isset($self['cache']['modules'][$_REQUEST['module']])) and ($status == 0) ) {
    // Update page title
    $conf['title'] .= " :: {$_REQUEST['module']}";

    // Run the module
    list($status, $output) = run_module($_REQUEST['module'], $_REQUEST['options']);
}


// Display the output .. unless $_REQUEST['nooutput'] == 1.. which would mean
// we're probably being included from another php file that will do something
// else with $output.
if (!$_REQUEST['nooutput']) {
    output($status, $output);
}




// This function prints the modules output as plain text or as a web page
// If we're doing a web page we print the nice form too.
function output($status=0, $output="") {

    global $conf;
    global $self;
    global $images;
    global $baseURL;
    global $db_context;

    // Print the result if no html, otherwise format it for display later in the page
    if ($self['nohtml'] == 1) {
        print $status . "\r\n";
        print $output;
        exit;
    }

    // Format the output for display later in the page
    if ($output) {
        $output = "<br><br><hr><font face=\"courier\">\n" .
              nl2br(str_replace(' ', '&nbsp;', htmlentities($output))) .
              "</font><hr>\n";
    }

    // Include HTML Header
    //include_once($conf['html_header']);



?>


    <form method="GET" enctype="application/x-www-form-urlencoded" target="_self">
    <div style="padding-left: 50px; padding-right: 50px;">

        <font size="+1">Execute a Module<br>
        </font><br>
        <font size="-1">

            <SELECT class="button" name="module">
                <OPTION value="" />--- Select One ---
<?
    // Print the list of valid types
    foreach (array_keys($self['cache']['modules']) as $value) {
        $selected = "";
        if (isset($_REQUEST['module']) and $_REQUEST['module'] == $value) {
            $selected = "SELECTED";
        }
        print "                <OPTION value=\"{$value}\" {$selected} />{$value} :: {$self['cache']['modules'][$value]}\n";
    }
?>
            </SELECT>

            &nbsp;&nbsp;

            Module Options:
            <input type="text" name="options" size="25" alt="Module Options" value="<? if (isset($_REQUEST['options'])) { echo htmlentities($_REQUEST['options']); } ?>">

            &nbsp;&nbsp;

            <input class="button" type="submit" name="go" value="Run Module" alt="go">

            <br>
            <br>

            Context:
            <SELECT class="button" name="ona_context">
<?
    // Print the list of valid contexts
    foreach (array_keys($db_context['ona']) as $value) {
        $selected = "";
        if (isset($_REQUEST['ona_context']) and $_REQUEST['ona_context'] == $value) {
            $selected = "SELECTED";
        }
        print "                <OPTION value=\"{$value}\" {$selected} />{$db_context['ona'][$value]['description']}\n";
    }
?>
            </SELECT>

            &nbsp;&nbsp;

            Debug Level:
            <SELECT class="button" name="debug">
<?
    // Print the list of valid types
    foreach (array(0, 1, 2, 3, 4, 5, 6) as $value) {
        $selected = "";
        if (isset($_REQUEST['debug']) and $_REQUEST['debug'] == $value) {
            $selected = "SELECTED";
        }
        print "                <OPTION value=\"{$value}\" {$selected} />{$value}\n";
    }
?>
            </SELECT>

            &nbsp;&nbsp;

            Disable HTML in output: <input type="checkbox" name="nohtml" alt="Disable HTML">

            &nbsp;&nbsp;

        </font>

<?
    // Print the output from a module if there is any.
    if ($output) {
        echo $output;
    }
?>

    </div>
    </form>

<?
    include_once($conf['html_footer']);
    exit;
}
?>
