<?php
/**
* Run a report from the cli
*
* @param array $options all module options
* @return
*/
function report_run($options="") {
    global $conf, $self, $onadb, $base;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => report_run({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(($options['list'] and !$options['name']) or (!$options['list'] and $options['name']))) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

report_run-v{$version}
Run a report

  Synopsis: report_run [KEY=VALUE] ...

  Required:
    name=NAME                 The name of the report to run
     OR
    list                      List the available report names

  Optional:
    rpt_usage                 Print a usage message for the specified report
    format=FORMAT             The name of the ouput format of the report (default: text)

EXAMPLE:
  dcm.pl -r report_run name=nmap_scan subnet=10.1.40.0 format=text <- run with text output.
  dcm.pl -r report_run name=nmap_scan rpt_usage <- Print usage info for nmap_scan report.

EOM
        ));
    }

    $status=0;

    // Generate a list of reports available
    $reportdir = dirname($base)."/www/local/reports";
    if ($options['list'] == 'Y') {
        $text .= sprintf("\n%-25s\n",'Report Name');
        $text .= sprintf("%'-80s\n",'');
        $files = array();
        $reportdirs = array(dirname($base)."/www/reports",dirname($base)."/www/local/reports");
        // Get a list of the files
        foreach($reportdirs as $reportdir) {
            if ($handle = opendir($reportdir)) {
                while (false !== ($file = readdir($handle))) {
                    if (strpos($file, ".inc.php") && $file != "listentries") {
                        // Build an array of filenames
                        array_push($files, $file);
                    }
                }
                closedir($handle);
            }
        }

        // sort the file names
        asort($files);

        // Loop through and display info about the files
        foreach($files as $file) {
            // Print the info
            $text .= str_replace(".inc.php", '', $file)."\n";
        }

        return(array(0, $text));
    }



    // default format will be text for the cli stuff
    if (!$options['format']) $options['format'] = 'text';

    // Load the report include file
    if (get_report_include($options['name'])) {
        // Run the report and return the output
        list($status, $report_output) = rpt_run($options, $options['format']);
    }

    $text = $report_output;

    // Return the success notice
    return(array($status , $text."\n"));



}


?>