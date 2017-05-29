<?php


?>


<div class="panel panel-default ws_panel">
  <div class="panel-heading">
    <h3 class="panel-title">Workspace: Home</h3>
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

    </div>
  </div>
</div>

