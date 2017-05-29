<?php

  // temp fixup
  $form = $_REQUEST;
  // Load the host record
  if ($form['id'])
    list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['id']));
  else if ($form['name']) {
    list($status, $rows, $subnet) = ona_find_subnet($form['name']);
  }

//print_r($subnet);

  list ($status, $rows, $hosts) =
    db_get_records(
      $onadb,
      "(
SELECT distinct a.*
from hosts as a, interfaces as b
where a.id = b.host_id
and b.subnet_id = ". $onadb->qstr($subnet['id']). "
order by b.ip_addr) h",
      'h.id > 0',
      "",
      10,
      -1
    );

//print_r($hosts);

?>


<div class="panel panel-default ws_panel">
  <div class="panel-heading">
    <h3 class="panel-title">Workspace: [Subnet] - <?php echo $subnet['name']; ?></h3>
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
      <div id="subnet_toolbar" class="panel-heading">
        <h4 class="panel-title">Interfaces</h4>
      </div>
      <table data-toggle="table"
             data-toolbar="#subnet_toolbar"
             data-pagination="true"
             data-striped="true"
             data-search="true">
            <thead>
                <tr>
                    <th data-sortable="true">Name</th>
                    <th data-sortable="true">Subnet</th>
                    <th data-sortable="true">Interface</th>
                    <th data-sortable="true">Name</th>
                    <th data-sortable="true">device type</th>
                </tr>
            </thead>
            <tbody>
<?php
        // Loop and display each record
        foreach($hosts as $record) {
          // Get additional info about eash host record


          // If a subnet_id was passed use it as part of the search.  Used to display the IP of the subnet you searched
          if (is_numeric($subnet['id'])) {
              list($status, $interfaces, $interface) = ona_get_interface_record(array('host_id' => $record['id'], 'subnet_id' => $subnet['id']), '');

              // Count how many rows and assign it back to the interfaces variable
              list($status, $rows, $records) = db_get_records($onadb,
                                                              'interfaces',
                                                              'host_id = '. $onadb->qstr($record['id']),
                                                              "ip_addr",
                                                              0);

              $interfaces = $rows;

          } else if (is_numeric($ip)) {
              list($status, $interfaces, $interface) = db_get_record($onadb,
                                                              'interfaces',
                                                              'host_id = '. $onadb->qstr($record['id']) .
                                                              ' AND ip_addr >= ' . $onadb->qstr($ip) .
                                                              ' AND ip_addr <= ' . $onadb->qstr($ip_end),
                                                              "ip_addr",
                                                              0);

              // Count how many rows and assign it back to the interfaces variable
              list($status, $rows, $records) = db_get_records($onadb,
                                                              'interfaces',
                                                              'host_id = '. $onadb->qstr($record['id']),
                                                              "ip_addr",
                                                              0);

              $interfaces = $rows;

          }  else {
              // Interface (and find out how many there are)
              list($status, $interfaces, $interface) = ona_get_interface_record(array('host_id' => $record['id']), '');
          }

          // bz: why did someone add this??  You especially want to show hosts with no interfaces so you can fix them!
          // if (!$interfaces) {$count -1; continue;}

          // get interface cluster info
          $clusterhtml = '';
          list ($status, $intclusterrows, $intcluster) = db_get_records($onadb, 'interface_clusters', "interface_id = {$interface['id']}");
          if ($intclusterrows>0) {
              $clusterscript= "onMouseOver=\"wwTT(this, event,
                      'id', 'tt_interface_cluster_list_{$record['id']}',
                      'type', 'velcro',
                      'styleClass', 'wwTT_niceTitle',
                      'direction', 'south',
                      'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>interface_cluster_list,id=>tt_interface_cluster_list_{$record['id']},interface_id=>{$interface['id']}\');'
                      );\"";
              $clusterhtml .= <<<EOL
                  <img src="{$images}/silk/sitemap.png" {$clusterscript} />
EOL;
          }


          $record['ip_addr'] = ip_mangle($interface['ip_addr'], 'dotted');
          $interface_style = '';
          if ($interfaces > 1) $interface_style = 'font-weight: bold;';

          // DNS A record
          list($status, $rows, $dns) = ona_get_dns_record(array('id' => $record['primary_dns_id']));
          $record['name'] = $dns['name'];

          // Domain Name
          list($status, $rows, $domain) = ona_get_domain_record(array('id' => $dns['domain_id']));
          $record['domain'] = $domain['fqdn'];

          // Subnet description
          //list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
          $record['subnet'] = $subnet['name'];
          $record['ip_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
          $record['ip_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');

          // Device Description
          list($status, $rows, $device) = ona_get_device_record(array('id' => $record['device_id']));
          list($status, $rows, $device_type) = ona_get_device_type_record(array('id' => $device['device_type_id']));
          list($status, $rows, $model) = ona_get_model_record(array('id' => $device_type['model_id']));
          list($status, $rows, $role) = ona_get_role_record(array('id' => $device_type['role_id']));
          list($status, $rows, $manufacturer) = ona_get_manufacturer_record(array('id' => $model['manufacturer_id']));
          $record['devicefull'] = "{$manufacturer['name']}, {$model['name']} ({$role['name']})";
          $record['device'] = str_replace('Unknown', '?', $record['devicefull']);


          $record['notes_short'] = truncate($record['notes'], 40);

          // Get location_number from the location_id
          list($status, $rows, $location) = ona_get_location_record(array('id' => $device['location_id']));

          // Escape data for display in html
          foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }

          $primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['id']}\', \'display\')');";

            echo <<<EOL
                <tr>
                <td>
                    <a href="?ws=host&name={$record['name']}.{$record['domain']}">{$record['name']}</a>.<a title="View domain. ID: {$domain['id']}"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$domain['id']}\', \'display\')');"
                    >{$record['domain']}</a>
                </td>
                    <td>{$record['subnet']}&nbsp;</td>
                    <td>{$record['ip_addr']}</td>
                    <td>{$record['device']}</td>
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
