<?php

  // temp fixup
  $form = $_REQUEST;
  // Load the host record
  if ($form['id'])
    list($status, $rows, $host) = ona_get_host_record(array('id' => $form['id']));
  else if ($form['name']) {
    list($status, $rows, $host) = ona_find_host($form['name']);
  }

  list ($status, $rows, $interfaces) =
    db_get_records(
      $onadb,
      'interfaces',
      "host_id = " . $onadb->qstr($host['id']). " OR id in (select interface_id from interface_clusters where host_id = ".$onadb->qstr($host['id']).")",
      "ip_addr ASC"
    );

//print_r($interface);

?>


<div class="panel panel-default ws_panel">
  <div class="panel-heading">
    <h3 class="panel-title">Workspace: [Host] - <?php echo $host['fqdn']; ?></h3>
  </div>
  <div class="panel-body">
    <div id="wsplugins" >
      <div class="ws_plugin_content">
        <table cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
                <tbody><tr>
                    <td class="ws_plugin_title_right" title="">Host Actions</td>
                    <td class="ws_plugin_title_left">

                    <img src="/images/silk/bullet_arrow_up.png" id="host_actions_dropdown" title="Min/Max" onclick="if (el('host_actions_content').style.display=='none') { el('host_actions_content').style.display=''; el('host_actions_dropdown').src='/images/silk/bullet_arrow_up.png'; }
                                   else { el('host_actions_content').style.display='none'; el('host_actions_dropdown').src='/images/silk/bullet_arrow_down.png';}"></td>
                </tr>
                <tr><td colspan="99" id="host_actions_content">            <span>
                <a title="Splunk" class="act" href="https://splunk.example.com:8001/?events/?eventspage=1&amp;num=10&amp;q=testhost.example.com" target="_blank"><img src="/images/silk/lightning_go.png" border="0">Splunk</a>&nbsp;
            </span><br>            <span>
                <a title="Cacti Graph" class="act" href="https:///cacti/graph_view.php?action=tree&amp;name=testhost.example.com" target="_blank"><img src="/images/silk/lightning_go.png" border="0">Cacti Graph</a>&nbsp;
            </span><br>            <span>
                <a title="Wiki Page" class="act" href="https://wiki..example.com/dokuwiki/network/servers/testhost.example.com" target="_blank"><img src="/images/silk/lightning_go.png" border="0">Wiki Page</a>&nbsp;
            </span><br>                </td></tr>
                </tbody></table>

      </div>
    </div>

    <br style="clear: both;">

    <div class="panel panel-default ws_list_tables">
      <div id="interfacetoolbar" class="panel-heading">
        <h4 class="panel-title">Interfaces</h4>
      </div>
      <table data-toggle="table"
               data-toolbar="#interfacetoolbar"
               data-pagination="true"
               data-striped="true"
               data-search="true">
            <thead>
                <tr>
                    <th data-sortable="true">IP Address</th>
                    <th data-sortable="true">Subnet</th>
                    <th data-sortable="true">MAC</th>
                    <th data-sortable="true">Name</th>
                </tr>
            </thead>
            <tbody>
<?php
        // Loop and display each record
        foreach($interfaces as $record) {
            // Get additional info about each host record //

            // Check if this interface has an external NAT
            unset($extnatint, $extnatdisplay, $extnatdisplay, $extnatsubdisplay);
            if ($record['nat_interface_id'] > 0) {
                list($status, $rows, $extnatint) = ona_get_interface_record(array('id' => $record['nat_interface_id']));
                // GDO: get the subnet object of the NATing interface, display it in both Interface and Subnet columns
                list($status, $rows, $extnatintsub) = ona_get_subnet_record(array('id' => $extnatint['subnet_id']));
                $extnatint['ip_addr'] = ip_mangle($extnatint['ip_addr'], 'dotted');
                //$extnatdisplay = "<span title='Interface is NATed to {$extnatint['ip_addr']}'> &nbsp;&nbsp;=> &nbsp;{$extnatint['ip_addr']}</span>";
                $extnatdisplay = "<span title='Interface is NATed to {$extnatint['ip_addr']} (on {$extnatintsub['name']})'> &nbsp;&nbsp;=> &nbsp;{$extnatint['ip_addr']}</span>";
                $extnatsubdisplay = " => <a title=\"View NATed subnet. ID: {$extnatintsub['id']}\"
                                            class=\"nav\"
                                            onClick=\"xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$extnatintsub['id']}\', \'display\')');\"
                                         >{$extnatintsub['name']}</a>";
            }

            // Check if this interface is an external NAT for another interface
            list ($isnatstatus, $isnatrows, $isnat) = db_get_records($onadb, 'interfaces', "nat_interface_id = {$record['id']}", '', 0 );
            // If the current interface is external NAT for another, dont display it in the list.
            if ($isnatrows > 0) {
                continue;
            }

            list ($status, $intclusterrows, $intcluster) = db_get_records($onadb, 'interface_clusters', "interface_id = {$record['id']}");

            // Grab some info from the associated subnet record
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $record['subnet_id']));
            $record['ip_mask'] = $subnet['ip_mask'];
            $record['subnet_id'] = $subnet['id'];
            $record['subnet_description'] = $subnet['name'];

            // Convert IP and Netmask to a presentable format
            $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');
            $record['ip_mask'] = ip_mangle($record['ip_mask'], 'dotted');
            $record['ip_mask_cidr'] = ip_mangle($record['ip_mask'], 'cidr');
            if ($record['mac_addr']) { $record['mac_addr'] = mac_mangle($record['mac_addr']); }

            $record['description_short'] = truncate($record['description'], 40);

            // Escape data for display in html
            foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }


            // Format the date and colorize if its older than 2 months
            if ($record['last_response']) {
                $record['last_response_fmt'] = date($conf['date_format'],strtotime($record['last_response']));
                if (strtotime($record['last_response']) < strtotime('-2 month')) {
                    $record['last_response_fmt'] = "<span style=\"color: red;\">".$record['last_response_fmt']."</style>";
                }
            }

            echo <<<EOL
                <tr>
                    <td>{$record['ip_addr']}/{$record['ip_mask_cidr']}</td>
                    <td><a href="?ws=subnet&name={$record['subnet_description']}">{$record['subnet_description']}</td>
                    <td>{$record['mac_addr']}</td>
                    <td>{$record['name']}</td>
                </tr>
EOL;
         }
?>
            </tbody>
      </table>
    </div>

    </div>
  </div>
</div>
