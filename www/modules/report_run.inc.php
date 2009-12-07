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
    $version = '1.01';

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
    if ($options['list'] == 'Y') {
        $text .= sprintf("\n%-25s\n",'Report Name');
        $text .= sprintf("%'-80s\n",'');
        $files = array();

        // Get the description info for the report items
        $reportlist = plugin_list('report_item');

        // sort the file names
        asort($reportlist);

        // Loop through the list of reports and show its name and description
        foreach ($reportlist as $entry) {
            $report_description = '';
            $record['name'] = $entry['name'];
            $record['shortname'] = str_replace('.inc.php', '', $record['name']);
            include_once $entry['path'];
            $record['desc'] = $report_description;

            $text .= sprintf("%-35s %s\n",$record['shortname'], $record['desc']);
         }

        return(array(0, $text));
    }

    // default format will be text for the cli stuff
    if (!$options['format']) $options['format'] = 'text';

    // Get the actual report code list
    $reports = plugin_list('report');

    // Loop through the list of reports till we find the matching name
    foreach($reports as $report) {
        if ($report['name'] == $options['name']) {
            // Load the report include file
            if (require_once($report['path'])) {
                // Run the report and return the output
                list($status, $report_output) = rpt_run($options, $options['format']);
            }
        }
    }

    $text = $report_output;

    // Return the success notice
    return(array($status , $text."\n"));



}


?>