<?php

/*
This workspace module will display links to reports that are available.

It will look through the list of $directories and if it findes files ending in .inc.php
it will include each one it finds.

Each of those files should contain relavant logic to determine if the report is available
for the display page that is running.  They should look in the $record element for data to make this
determination.  They should then add table rows to the $row_html variable to build this table.

If no reports match, then it should not display the report box.


*/

global $base;

// REPORT LIST
//if (auth('host_config_admin',$debug_val)) {
    $title_left_html = "Reports";
    $row_html = '';

    // Generate a list of reports available
    $reports = plugin_list('report_item');
    $z=0;
    foreach ($reports as $report) {
        @include_once $report['path'];
    }

    $modbodyhtml .= <<<EOL
    <!-- Report LIST -->
    <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
    {$row_html}
    </table>
EOL;

    // dont display anything if there are no available reports
    if ($row_html == '') $modbodyhtml = '';


//}
// END REPORT LIST



?>