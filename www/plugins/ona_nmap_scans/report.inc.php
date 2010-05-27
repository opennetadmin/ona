<?php

//////////////////////////////////////////////////////////////////////////////
// Function: rpt_run()
//
// Description:
//   Returns the output for this report.
//   It will first get the DATA for the report by executing whatever code gathers
//   data used by the report.  This is handled by the rpt_get_data() function.
//   It will then pass that data to the appropriate output generator.
//
//   A rpt_output_XYZ() function should be written for each type of output format
//   you want to support.  The data from rpt_get_data will be used by this function.
//
//   IN GENERAL, YOU SHOULD NOT NEED TO EDIT THIS FUNCTION
//
//////////////////////////////////////////////////////////////////////////////
function rpt_run($form, $output_format='html') {

    $status=0;

    // See if the output function they requested even exists
    $func_name = "rpt_output_{$output_format}";
    if (!function_exists($func_name)) {
        $rptoutput = "ERROR => This report does not support an '{$form['format']}' output format.";
        return(array(1,$rptoutput));
    }

    // if we are looking for the usage, skip gathering data.  Otherwise, gather report data.
    if (!$form['rpt_usage']) list($status, $rptdata) = rpt_get_data($form);

    if ($status) {
        $rptoutput = "NOTICE => There was a problem getting the data. <br> {$rptdata}";
    }
    // Pass the data to the output type
    else {
        // If the rpt_usage option was passed, add it to the gathered data
        if ($form['rpt_usage']) $rptdata['rpt_usage'] = $form['rpt_usage'];

        // Pass the data to the output generator
        list($status, $rptoutput) = $func_name($rptdata);
        if ($status)
            $rptoutput = "ERROR => There was a problem getting the output: {$rptoutput}";
    }

    return(array($status,$rptoutput));
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////START EDITING BELOW////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



//////////////////////////////////////////////////////////////////////////////
// Function: rpt_html_form()
//
// Description:
//   Returns the HTML form text for this report.
//   This is used by the display report code to present an html form to
//   the user.  This simply provides a gui to gather all the input variables.
//////////////////////////////////////////////////////////////////////////////
function rpt_html_form($report_name, $rptform='',$rptjs='') {
    global $images, $color, $style, $conf;
    $rpthtml = '';
    $rptjs = '';

    // Create your input form below
    $rpthtml .= <<<EOL

        <form id="{$report_name}_report_form" onsubmit="el('rpt_submit_button').onclick(); return false;">
            <input type="hidden" value="{$report_name}" name="report"/>
            Subnet: <input id="subnet" name="subnet" value="{$rptform['subnet']}" class="edit" type="text" size="15" />
            OR Show global issues: <input id="all" name="all" class="edit" type="checkbox" />
            <input type="submit"
                   id="rpt_submit_button"
                   title="Search"
                   value="Run Report"
                   class="act"
                   onClick="el('report_content').innerHTML='<br><center><img src={$images}/loading.gif></center><br>';xajax_window_submit('display_report', xajax.getFormValues('{$report_name}_report_form'), 'run_report');"
            />
            <input class="act" type="button" name="reset" value="Clear" onClick="clearElements('{$report_name}_report_form');">
        </form>


EOL;

    // Return the html code for the form
    return(array(0,$rpthtml,$rptjs));
}














function rpt_get_data($form) {
    global $base;


// If they want to perform a scan on an existing file
if ($form['subnet']) {
    $rptdata['scansource'] = "Based on an existing scan file for '{$form['subnet']}'";
    //$xml = shell_exec("{$nmapcommand} -sP -R -oX - {$form['subnet']}");

    list($status, $rows, $subnet) = ona_find_subnet($form['subnet']);
    if ($rows) {
        $netip = ip_mangle($subnet['ip_addr'],'dotted');
        $netcidr = ip_mangle($subnet['ip_mask'],'cidr');
        $nmapxmlfile = "{$base}/local/nmap_scans/subnets/{$netip}-{$netcidr}.xml";

        if(file_exists($nmapxmlfile)) {
            $xml[0]=xml2ary(file_get_contents($nmapxmlfile));
        } else {
            $self['error'] = "ERROR => The subnet '{$form['subnet']}' does not have an nmap scan XML file on this server.";
            return(array(2, $self['error']."\n"));
        }
    } else {
        $self['error'] = "ERROR => The subnet '{$form['subnet']}' does not exist.";
        return(array(2, $self['error']."\n"));
    }
}



// If they want to build a report on ALL the nmap data
if ($form['all']) {
    $rptdata['scansource'] = "Showing all scan data";

    $nmapdir = "{$base}/local/nmap_scans/subnets";
    $dh  = @opendir($nmapdir);
    $c=0;
    while (false !== ($filename = @readdir($dh))) {
        if(strpos($filename, 'xml')) {
            $xml[$c]=xml2ary(file_get_contents($nmapdir.'/'.$filename));
        }
        $c++;
    }

}

// If they pass a file from the remote host via CLI
if ($form['file']) {
    $rptdata['scansource'] = "Based on an uploaded XML file";
    $nmapxmlfile = $form['file'];
    // clean up escaped characters
    $nmapxmlfile = preg_replace('/\\\"/','"',$nmapxmlfile);
    $nmapxmlfile = preg_replace('/\\\=/','=',$nmapxmlfile);
    $nmapxmlfile = preg_replace('/\\\&/','&',$nmapxmlfile);
    $xml[0]=xml2ary($nmapxmlfile);
}


// loop through all the xml arrays that have been built.
for($z=0;$z < count($xml); $z++) {
 // Find out how many total hosts we have in the array
 $rptdata['totalhosts'] = $xml[$z]['nmaprun']['_c']['runstats']['_c']['hosts']['_a']['total'];
 $rptdata['runtime'] = $xml[$z]['nmaprun']['_c']['runstats']['_c']['finished']['_a']['timestr'];


 // Process the array for the total amount of hosts reported
 for($i=0;$i < $rptdata['totalhosts']; $i++) {
    // Gather some info from the nmap XML file
    $netstatus = $xml[$z]['nmaprun']['_c']['host'][$i]['_c']['status']['_a']['state'];
    $ipaddr = $xml[$z]['nmaprun']['_c']['host'][$i]['_c']['address']['_a']['addr'];
    //$macaddr = $xml['nmaprun']['_c']['host'][$i]['_c']['address']['_a']['addr'];
    $dnsname = $xml[$z]['nmaprun']['_c']['host'][$i]['_c']['hostnames']['_c']['hostname']['_a']['name'];

    // Try the older nmap format if no IP found.. not sure of what differences there are in the XSL used?
    if (!$ipaddr) {
        $ipaddr = $xml[$z]['nmaprun']['_c']['host'][$i]['_c']['address']['0']['_a']['addr'];
        $macaddr = $xml[$z]['nmaprun']['_c']['host'][$i]['_c']['address']['1']['_a']['addr'];
    }

    // Lookup the IP address in the database
    list($status, $introws, $interface) = ona_find_interface($ipaddr);
    if (!$introws) {
        $interface['ip_addr_text'] = 'NOT FOUND';
        list($status, $introws, $tmp) = ona_find_subnet($ipaddr);
        $interface['subnet_id'] = $tmp['id'];
    }

    // Find out if this IP falls inside of a pool
    $inpool = 0;
    $ip = ip_mangle($ipaddr,'numeric');
    list($status, $poolrows, $pool) = ona_get_dhcp_pool_record("ip_addr_start <= '{$ip}' AND ip_addr_end >= '{$ip}'");
    if ($poolrows) {
        $inpool = 1;
    }

    // Lookup the DNS name in the database
    list($status, $dnsrows, $dns) = ona_get_dns_record(array('interface_id' => $interface['id'], 'type' => 'A'));

    // some base logic
    // if host is up in nmap but no db ip then put in $nodb
    // if host is up and is in db then put in $noissue
    // if host is down and not in db then skip
    // if host is down and in db then put in $nonet
    // if host is up an in db, does DNS match?
    //    in DNS but not DB
    //    in DB but not DNS
    //    DNS and DB dont match

    // Setup the base array element for the IP
    $rptdata['ip'][$ipaddr]=array();
    $rptdata['ip'][$ipaddr]['netstatus'] = $netstatus;
    $rptdata['ip'][$ipaddr]['netip'] = $ipaddr;
    $rptdata['ip'][$ipaddr]['netdnsname'] = strtolower($dnsname);
    if ($macaddr != -1) $rptdata['ip'][$ipaddr]['netmacaddr'] = $macaddr;

    $rptdata['ip'][$ipaddr]['inpool'] = $inpool;

    $rptdata['ip'][$ipaddr]['dbip'] = $interface['ip_addr_text'];
    $rptdata['ip'][$ipaddr]['dbsubnetid'] = $interface['subnet_id'];
    $rptdata['ip'][$ipaddr]['dbdnsname'] = $dns['fqdn'];
    $rptdata['ip'][$ipaddr]['dbmacaddr'] = $interface['mac_addr'];

    $rptdata['netip'] = $netip;
    $rptdata['netcidr'] = $netcidr;
    if ($form['all']) $rptdata['all'] = 1;
    if ($form['update_response']) $rptdata['update_response'] = 1;

 }
}

    return(array(0,$rptdata));
}






function rpt_output_html($form) {
    global $onadb, $style, $images;

    if (!$form['scansource']) {
        $text .= "Please fill out input form.";
        return(array(0,$text));
    }

    if (!$form['all']) { 
        if ($form['totalhosts']) $text .=  "NMAP scan of {$form['totalhosts']} hosts done on {$form['runtime']}. {$form['scansource']} <a href=\"local/nmap_scans/subnets/{$form['netip']}-{$form['netcidr']}.xml\">Display RAW scan</a><br>";
    } else {
        $text .= "Displaying records for ALL nmap scans in the system.  It also only shows issues, not entries that are OK.";
    }

    if (!$form['totalhosts'] and !$form['all']) $text .=  "ERROR => No hosts found in this NMAP scan, check that the XML file is not empty.<br>";

    $text .= <<<EOL
    <table class="list-box" cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 0;">
            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center">NMAP SCAN</td>
                <td class="list-header" align="center">DATABASE</td>
                <td class="list-header" align="center">&nbsp;</td>
                <td class="list-header" align="center">Actions</td>
            </tr>
    </table>
        <div id="nmap_scan_results" style="overflow: auto; width: 100%; height: 89%;border-bottom: 1px solid;">
            <table class="list-box" cellspacing="0" border="0" cellpadding="0">
EOL;

    // netip    netname     netmac      dbip    dbname  dbmac

    $poolhostcount = 0;

    // find out the broadcast IP for this subnet
    $num_hosts = 0xffffffff - ip_mangle($form['netcidr'], 'numeric');
    $broadcastip = ip_mangle((ip_mangle($form['netip'], 'numeric') + $num_hosts),'dotted');



    foreach ((array)$form['ip'] as $record) {

        $act_status_fail = "<img src=\"{$images}/silk/stop.png\" border=\"0\">";
        $act_status_ok = "<img src=\"{$images}/silk/accept.png\" border=\"0\">";
        $act_status_partial = "<img src=\"{$images}/silk/error.png\" border=\"0\">";

        $action = '';
        $redcolor = '';

        // button info to view subnet
        $viewsubnet = <<<EOL
    <a onclick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$record['dbsubnetid']}\', \'display\')');" title="Goto this subnet."><img src="{$images}/silk/application.png" border="0"></a>
EOL;

        // Check devices that are down
        if ($record['netstatus'] == "down") {
            // Set a red background color
            $redcolor = "color: red;";
            // Skip over hosts that are not in network or database
            if ($record['dbip'] == "NOT FOUND") continue;
            // If it is only in the database then they should validate the ip or remove from database
            if (($record['netip'] == $record['dbip']) or ($record['netdnsname'] != $record['dbdnsname'])) {
                $action = <<<EOL
                        {$act_status_partial}
                        <a title="Ping"
                            class="act"
                            onClick="xajax_window_submit('tooltips', 'ip=>{$record['dbip']}', 'ping');"
                        >Ping to verify</a> then delete as desired
EOL;
            }
        }



        // check devices that are up
        if ($record['netstatus'] == "up") {



            // If this is the subnet address or broadcast then skip it.  Sometimes nmap shows them as up
            if ($record['netip'] == $form['netip']) continue;
            if ($record['netip'] == $broadcastip) continue;



            // Break out the host and domain parts of the name if we can
            if ($record['netdnsname']) {
                list($status, $rows, $domain) = ona_find_domain($record['netdnsname'],0);
                // Now find what the host part of $search is
                $hostname = str_replace(".{$domain['fqdn']}", '', $record['netdnsname']);
            }

            // If we dont find it in the database
            if ($record['dbip'] == "NOT FOUND") {
                $action = <<<EOL
                        {$act_status_fail}
                        <a title="Add host."
                            class="act"
                            onClick="xajax_window_submit('edit_host', 'ip_addr=>{$record['netip']},hostname=>{$hostname},domain_id=>{$domain['id']},js=>null', 'editor');"
                        >Add as host</a> or 
                        <a title="Add interface."
                            class="act"
                            onClick="xajax_window_submit('edit_interface', 'ip_addr=>{$record['netip']},js=>null', 'editor');"
                        >Add as interface</a>, check proper pool range
EOL;
            }
            // If it is in the database and network
            if ($record['netip'] == $record['dbip']) {
                $action = '&nbsp;'.$act_status_ok.' OK';
                // But if the names are not the same then action is partial
                if ($record['netdnsname'] != $record['dbdnsname']) { $action = '&nbsp;'.$act_status_partial.' Update DNS'; }
            }


            // if the database name is empty, then provide a generic "name"
            if (!$record['dbdnsname'] and ($record['dbip'] != 'NOT FOUND') and $record['netdnsname']) $record['dbdnsname'] = 'NONE SET';

            // if the names are different, offer an edit button for the DB
            if (($record['netdnsname']) and strtolower($record['netdnsname']) != $record['dbdnsname']) {
                // not a lot of testing here to make sure it will find the right name.
                list($status, $rows, $rptdnsrecord) = ona_find_dns_record($record['dbdnsname']);
                $record['dbdnsname'] = <<<EOL
                        <a title="Edit DNS record"
                            class="act"
                            onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$rptdnsrecord['id']},ip_addr=>{$record['dbip']},hostname=>{$hostname},domain_id=>{$domain['id']},js=>null', 'editor');"
                        >{$record['dbdnsname']}</a>
EOL;
            }

            // If the device is in a dhcp pool range, then count it and identify it.
            if ($record['inpool'] == 1) {
                $poolhostcount++;
                $record['dbip'] = 'DHCP Pooled';
                $action = '&nbsp; DHCP Pooled device';
            }

        }

/*
TODO:
* more testing of mac address stuff
* display info about last response time.. add option to update last response form file.. flag if db has newer times than the scan
*/



        $txt = <<<EOL
            <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">
                <td class="list-row" align="left" style="{$style['borderR']};{$redcolor}">{$record['netstatus']}</td>
                <td class="list-row" align="left" style="{$redcolor}">{$record['netip']}</td>
                <td class="list-row" align="left">{$record['netdnsname']}&nbsp;</td>
                <td class="list-row" align="left" style="{$style['borderR']};">{$record['netmacaddr']}&nbsp;</td>
                <td class="list-row" align="left">{$record['dbip']}&nbsp;</td>
                <td class="list-row" align="left">{$record['dbdnsname']}&nbsp;</td>
                <td class="list-row" align="left" style="{$style['borderR']};">{$record['dbmacaddr']}&nbsp;</td>
                <td class="list-row" align="left">{$viewsubnet}{$action}&nbsp;</td>
            </tr>
EOL;


        // if we are in all mode, print only errors.. otherwise, print it all
        if ($form['all'] and strpos($action,'OK')) $txt = '';
        // add the new line to the html output variable
        $text .= $txt;
    }


    if (!$form['all']) $hostpoolinfo = "Hosts in DHCP pool range: {$poolhostcount}<br>";
    $text .=  "</table>{$hostpoolinfo}<center>END OF REPORT</center></div>";


    return(array(0,$text));
}





// csv wrapper function
function rpt_output_csv($form) {
    $form['csv_output'] = true;
    list($stat,$out) = rpt_output_text($form);
    return(array($stat,$out));
}





// output for text
function rpt_output_text($form) {
    global $onadb, $style, $images;

    // Provide a usage message here
    $usagemsg = <<<EOL
Report: nmap_scan
  Processes the XML output of an nmap scan and compares it to data in the database.

  Required:
    subnet=ID|IP|STRING   Subnet ID, IP, or name of existing subnet with a scan
      OR
    file=PATH             Local XML file will be sent to server for processing
      OR
    all                   Process ALL XML files on the server
      OR
    update_response       Update the last response field for all UP IPs to time in scan

  Output Formats:
    html
    text
    csv

NOTE: When running update_response, any entry that was updated will have a * indication
      at the beginning of the line.

EOL;

    // Provide a usage message
    if ($form['rpt_usage']) {
        return(array(0,$usagemsg));
    }

    if (!$form['totalhosts'] and !$form['all']) return(array(1,"\nERROR => No hosts found, check that the XML file is not empty, or that your subnet exists in the database.\n{$usagemsg}"));

    if (!$form['all']) { $text .=  "NMAP scan of {$form['totalhosts']} hosts done on {$form['runtime']}. {$form['scansource']}\n\n";
    } else {
        $text .= "Displaying records for ALL nmap scans in the system.  It also only shows issues, not entries that are OK.\n\n";
    }

    //$text .= sprintf("%-50s %-8s %-8s\n",'NMAP SCAN','DATABASE','Actions');
    if ($form['csv_output'])
        $text .= sprintf("%s,%s,%s,%s,%s,%s,%s,%s\n",'STAT','NET IP','NET NAME','NET MAC','DB IP','DB NAME','DB MAC','ACTION');
    else
        $text .= sprintf("%-6s %-15s %-25s %-12s %-15s %-25s %-12s %s\n",'STAT','NET IP','NET NAME','NET MAC','DB IP','DB NAME','DB MAC','ACTION');

    // netip    netname     netmac      dbip    dbname  dbmac

    $poolhostcount = 0;

    // find out the broadcast IP for this subnet
    $num_hosts = 0xffffffff - ip_mangle($form['netcidr'], 'numeric');
    $broadcastip = ip_mangle((ip_mangle($form['netip'], 'numeric') + $num_hosts),'dotted');



    foreach ($form['ip'] as $record) {

        $action='';
        $upresp=' ';

        // Check devices that are down
        if ($record['netstatus'] == "down") {
            // Skip over hosts that are not in network or database
            if ($record['dbip'] == "NOT FOUND") continue;
            // If it is only in the database then they should validate the ip or remove from database
            if (($record['netip'] == $record['dbip']) or ($record['netdnsname'] != $record['dbdnsname'])) {
                $action = "Ping to verify then delete as desired";
            }
        }

        // check devices that are up
        if ($record['netstatus'] == "up") {

            // update the database last response field.
            if ($form['update_response'] and $record['dbip'] != "NOT FOUND") {
                //if (isset($options['dcm_output'])) { $text .=  "dcm.pl -r interface_modify interface={$record['ip']} set_last_response='{$runtime}'\n"; }
                list($status, $output) = run_module('interface_modify', array('interface' => $record['dbip'], 'set_last_response' => $form['runtime']));
                if ($status) {
                    $self['error'] = "ERROR => Failed to update response time for '{$record['dbip']}': " . $output;
                    printmsg($self['error'], 1);
                }
                $upresp='*';
            }

            // If this is the subnet address or broadcast then skip it.  Sometimes nmap shows them as up
            if ($record['netip'] == $form['netip']) continue;
            if ($record['netip'] == $broadcastip) continue;

            // Break out the host and domain parts of the name if we can
            if ($record['netdnsname']) {
                list($status, $rows, $domain) = ona_find_domain($record['netdnsname'],0);
                // Now find what the host part of $search is
                $hostname = str_replace(".{$domain['fqdn']}", '', $record['netdnsname']);
            }

            // If we dont find it in the database
            if ($record['dbip'] == "NOT FOUND") $action = "Add as host or Add as interface, check proper pool range";

            // If it is in the database and network
            if ($record['netip'] == $record['dbip']) {
                $action = 'OK';
                // But if the names are not the same then action is partial
                if ($record['netdnsname'] != $record['dbdnsname']) { $action = 'Update DNS'; }
            }


            // if the database name is empty, then provide a generic "name"
            if (!$record['dbdnsname'] and ($record['dbip'] != 'NOT FOUND') and $record['netdnsname']) $record['dbdnsname'] = 'NONE SET';

            // if the names are different, offer an edit button for the DB
            if (($record['netdnsname']) and strtolower($record['netdnsname']) != $record['dbdnsname']) {
                // not a lot of testing here to make sure it will find the right name.
                list($status, $rows, $rptdnsrecord) = ona_find_dns_record($record['dbdnsname']);
            }

            // If the device is in a dhcp pool range, then count it and identify it.
            if ($record['inpool'] == 1) {
                $poolhostcount++;
                $record['dbip'] = 'DHCP Pooled';
                $action = 'DHCP Pooled device';
            }
        }

/*
TODO:
* more testing of mac address stuff
* display info about last response time.. add option to update last response form file.. flag if db has newer times than the scan
*/
        if ($form['csv_output']) {
            $txt = sprintf("%s,%s,%s,%s,%s,%s,%s,\"%s\"\n", $upresp.$record['netstatus'],$record['netip'],$record['netdnsname'],$record['netmacaddr'],$record['dbip'],$record['dbdnsname'],$record['dbmacaddr'],$action);
        } else {
            $txt = sprintf("%-6s %-15s %-25s %-12s %-15s %-25s %-12s %s\n",$upresp.$record['netstatus'],$record['netip'],$record['netdnsname'],$record['netmacaddr'],$record['dbip'],$record['dbdnsname'],$record['dbmacaddr'],$action);
        }

        // if we are in all mode, print only errors.. otherwise, print it all
        if ($form['all'] and $action == 'OK') $txt = '';
        // add the new line to the html output variable
        $text .= $txt;
    }


    if (!$form['all']) $hostpoolinfo = "Hosts in DHCP pool range: {$poolhostcount}\n";
    $text .=  "\n{$hostpoolinfo}END OF REPORT";


    return(array(0,$text));
}








/*
    The following functions were taken from http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
*/
/*
    Working with XML. Usage:
    $xml=xml2ary(file_get_contents('1.xml'));
    $link=&$xml['ddd']['_c'];
    $link['twomore']=$link['onemore'];
    // ins2ary(); // dot not insert a link, and arrays with links inside!
    echo ary2xml($xml);
*/

// XML to Array
function xml2ary(&$string) {
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parse_into_struct($parser, $string, $vals, $index);
    xml_parser_free($parser);

    $mnary=array();
    $ary=&$mnary;
    foreach ($vals as $r) {
        $t=$r['tag'];
        if ($r['type']=='open') {
            if (isset($ary[$t])) {
                if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                $cv=&$ary[$t][count($ary[$t])-1];
            } else $cv=&$ary[$t];
            if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
            $cv['_c']=array();
            $cv['_c']['_p']=&$ary;
            $ary=&$cv['_c'];

        } elseif ($r['type']=='complete') {
            if (isset($ary[$t])) { // same as open
                if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                $cv=&$ary[$t][count($ary[$t])-1];
            } else $cv=&$ary[$t];
            if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
            $cv['_v']=(isset($r['value']) ? $r['value'] : '');

        } elseif ($r['type']=='close') {
            $ary=&$ary['_p'];
        }
    }

    _del_p($mnary);
    return $mnary;
}

// _Internal: Remove recursion in result array
function _del_p(&$ary) {
    foreach ($ary as $k=>$v) {
        if ($k==='_p') unset($ary[$k]);
        elseif (is_array($ary[$k])) _del_p($ary[$k]);
    }
}

// Array to XML
function ary2xml($cary, $d=0, $forcetag='') {
    $res=array();
    foreach ($cary as $tag=>$r) {
        if (isset($r[0])) {
            $res[]=ary2xml($r, $d, $tag);
        } else {
            if ($forcetag) $tag=$forcetag;
            $sp=str_repeat("\t", $d);
            $res[]="$sp<$tag";
            if (isset($r['_a'])) {foreach ($r['_a'] as $at=>$av) $res[]=" $at=\"$av\"";}
            $res[]=">".((isset($r['_c'])) ? "\n" : '');
            if (isset($r['_c'])) $res[]=ary2xml($r['_c'], $d+1);
            elseif (isset($r['_v'])) $res[]=$r['_v'];
            $res[]=(isset($r['_c']) ? $sp : '')."</$tag>\n";
        }

    }
    return implode('', $res);
}

// Insert element into array
function ins2ary(&$ary, $element, $pos) {
    $ar1=array_slice($ary, 0, $pos); $ar1[]=$element;
    $ary=array_merge($ar1, array_slice($ary, $pos));
}













?>
