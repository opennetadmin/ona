<?php

$title_right_html = '';
$title_left_html  = '';
$modbodyhtml = '';

// if this is a display host screen then go ahead and make a puppet facts window
if ($extravars['window_name'] == 'html_desktop') {



        $title_left_html .= <<<EOL
                    &nbsp;Where to begin
EOL;


        $modbodyhtml .= <<<EOL
                If you are wondering where to start,<br>
                try one of these tasks:<br>
                <a title="Add DNS domain"
                class="act"
                onClick="xajax_window_submit('edit_domain', 'js=> ', 'editor');"
                ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
                <a title="Add DNS domain"
                class="act"
                onClick="xajax_window_submit('edit_domain', 'js=> ', 'editor');"
                >Add a DNS domain</a>&nbsp;
                <br>
                <a title="Add subnet"
                class="act"
                onClick="xajax_window_submit('edit_subnet', 'js=> ', 'editor');"
                ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
                <a title="Add subnet"
                class="act"
                onClick="xajax_window_submit('edit_subnet', 'js=> ', 'editor');"
                >Add a new subnet</a>&nbsp;
                <br>
                <a title="Add host"
                class="act"
                onClick="xajax_window_submit('edit_host', ' ', 'editor');"
                ><img src="{$images}/silk/page_add.png" border="0"></a>&nbsp;
                <a title="Add host"
                class="act"
                onClick="xajax_window_submit('edit_host', ' ', 'editor');"
                >Add a new host</a>&nbsp;
                <br>
                <a title="Advanced search" class="act"
                   onClick="xajax_window_submit('search_results', 'search_form_id=>subnet_search_form'); return false;"
                ><img style="vertical-align: middle;" src="{$images}/silk/application_form_magnify.png" border="0" /></a>&nbsp;
                <a title="Advanced search"
                class="act"
                onClick="xajax_window_submit('search_results', 'search_form_id=>subnet_search_form'); return false;"
                >Perform a search</a>&nbsp;
                <br>
                <a title="List Hosts"
                class="act"
                onClick="xajax_window_submit('search_results', 'search_form_id=>qsearch_form'); return false;"
                ><img src="{$images}/silk/application_view_detail.png" border="0"></a>&nbsp;
                <a title="List Hosts"
                class="act"
                onClick="xajax_window_submit('search_results', 'search_form_id=>qsearch_form');"
                >List Hosts</a>&nbsp;
                <br><br>
                <ul>
                <li>If you need further assistance,<br>look for the <img src='{$images}/silk/help.png'> icon<br>
                in the title bar of windows.<br></li>
                <li>You can also try the main<br> help index located <a href='{$_ENV['help_url']}'>here</a><br></li>
                </ul>
EOL;

   }



?>
