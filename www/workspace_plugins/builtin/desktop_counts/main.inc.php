<?php

$title_right_html = '';
$title_left_html  = '';
$modbodyhtml = '';

// if this is a display host screen then go ahead and make a puppet facts window
if ($extravars['window_name'] == 'html_desktop') {

    // Set up a generic where clause
    $where = 'id > 0';

    // Start getting various record counts
    list ($status, $host_count, $records)       = db_get_records($onadb, 'hosts', $where, "", 0);
    list ($status, $dns_count, $records)        = db_get_records($onadb, 'dns', $where, "", 0);
    list ($status, $interface_count, $records)  = db_get_records($onadb, 'interfaces', $where, "", 0);
    list ($status, $domain_count, $records)     = db_get_records($onadb, 'domains', $where, "", 0);
    list ($status, $subnet_count, $records)     = db_get_records($onadb, 'subnets', $where, "", 0);
    list ($status, $pool_count, $records)       = db_get_records($onadb, 'dhcp_pools', $where, "", 0);
    list ($status, $block_count, $records)      = db_get_records($onadb, 'blocks', $where, "", 0);
    list ($status, $vlan_campus_count, $records) = db_get_records($onadb, 'vlan_campuses', $where, "", 0);
    list ($status, $config_archive_count, $records) = db_get_records($onadb, 'configurations', $where, "", 0);

    $title_left_html .= "&nbsp;Record Counts";


        $modbodyhtml .= <<<EOL
<script type="text/javascript">
  function record_counts_pie(rownum) {
    // Function modified from code posted on http://www.phpied.com/canvas-pie/
    //

    // source data table and canvas tag
    var data_table = document.getElementById('record_counts');
    var td_index = 1; // which TD contains the data
    var canvas = document.getElementById('record_counts_pie');

    // exit if canvas is not supported
    if (typeof canvas.getContext === 'undefined') {
        return;
    }

    // define some colors
    var color = [];
    color[0] = "#bbaaff";
    color[1] = "#ffaaaa";
    color[2] = "#8899ff";
    color[3] = "#ddffaa";
    color[4] = "#aaffee";
    color[5] = "#66ddcc";
    color[6] = "#dd6677";
    color[7] = "#55DD88";

    // get the data[] from the table
    var tds, data = [], value = 0, total = 0, bump = [];
    var trs = data_table.getElementsByTagName('tr'); // all TRs
    for (var i = 0; i < trs.length; i++) {
        tds = trs[i].getElementsByTagName('td'); // all TDs

        if (tds.value === 0) continue; //  no TDs here, move on

        bump[i] = 0;
        if (i == rownum) bump[i] = 10;

        // get the value, update total
        value  = parseFloat(tds[td_index].innerHTML);
        data[i] = value;
        total += value;
    }

    // get canvas context, determine radius and center
    var ctx = canvas.getContext('2d');
    var canvas_size = [canvas.width, canvas.height];
    var radius = Math.min((canvas_size[0]-20), (canvas_size[1]-20)) / 2;
    var center = [canvas_size[0]/2, canvas_size[1]/2];

    var sofar = 0; // keep track of progress

    // clear out the current contents
    ctx.fillStyle = "rgb(255,255,255)";
    ctx.fillRect(0,0,canvas.width,canvas.height);

    // loop through each table row
    for (var piece = 0; piece < trs.length; piece++) {

        var thisvalue = data[piece] / total;

        ctx.beginPath();
        ctx.moveTo(center[0], center[1]); // center of the pie

        ctx.arc(  // draw next arc
            center[0],
            center[1],
            (radius + bump[piece]),
            Math.PI * (- 0.5 + 2 * sofar), // -0.5 sets set the start to be top
            Math.PI * (- 0.5 + 2 * (sofar + thisvalue)),
            false
        );

        ctx.lineTo(center[0], center[1]); // line back to the center
        ctx.closePath();
        ctx.fillStyle = color[piece];
        ctx.fill();

        sofar += thisvalue; // increment progress tracker
    }
}
</script>

 <table cellspacing="0" border="0" cellpadding="0">
    <tr>
            <td nowrap="true" valign="top" style="padding: 15px;"><br/><canvas id="record_counts_pie" width="150" height="150"></canvas></td>
            <td nowrap="true" valign="top" style="padding: 15px;">

                <table onmouseout="record_counts_pie(99)" id="record_counts" border=1 style="border-collapse: collapse;border-color: #999999;"s>
                    <tr onmouseover="record_counts_pie(0)"><td><a title="List Subnets" onClick="xajax_window_submit('search_results', 'search_form_id=>subnet_search_form');">Subnets</a></td><td>{$subnet_count}</td>
                    <tr onmouseover="record_counts_pie(1)"><td><a title="List Hosts" onClick="xajax_window_submit('search_results', 'search_form_id=>host_search_form');">Hosts</a></td><td>{$host_count}</td>
                    <tr onmouseover="record_counts_pie(2)"><td>Interfaces</td><td>{$interface_count}</td>
                    <tr onmouseover="record_counts_pie(3)"><td>DNS Records</td><td>{$dns_count}</td>
                    <tr onmouseover="record_counts_pie(4)"><td><a title="List DNS Domains" onClick="toggle_window('app_domain_list');">DNS Domains</a></td><td>{$domain_count}</td>
                    <tr onmouseover="record_counts_pie(5)"><td>DHCP Pools</td><td>{$pool_count}</td>
                    <tr onmouseover="record_counts_pie(6)"><td><a title="List Blocks" onClick="xajax_window_submit('search_results', 'search_form_id=>block_search_form');"> Blocks</a></td><td>{$block_count}</td>
                    <tr onmouseover="record_counts_pie(7)"><td><a title="List VLAN Campuses" onClick="xajax_window_submit('search_results', 'search_form_id=>vlan_campus_search_form');">VLAN Campuses</a></td><td>{$vlan_campus_count}</td>
                    <tr onmouseover="record_counts_pie(8)"><td>Config Archives</td><td>{$config_archive_count}</td>
                </table>
            </td>
    </tr>
</table>

<script type="text/javascript">
    // Print the nice pie chart!
    record_counts_pie(99);
</script>

EOL;

}



?>
