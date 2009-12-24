<?php
global $base;

// Provide a short (less than 50 characters) description
$report_description="Compare NMAP results to data stored in ONA.";

if ($record['ip_addr']) $record['ip_address'] = $record['ip_addr'];

if(file_exists("{$base}/local/nmap_scans/subnets/{$record['ip_address']}-{$record['ip_subnet_mask_cidr']}.xml")) {

    $row_html .= <<<EOL
            <tr title="{$report_description}">
                <td class="padding" align="right" nowrap="true">Nmap Scan:
                <a onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_report\', \'report=>ona_nmap_scans,subnet=>{$record['ip_address']}\', \'display\')');"
                >View Report</a>

                </td>
            </tr>
EOL;
}
?>