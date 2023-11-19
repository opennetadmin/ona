<?php

$title_right_html = '';
$title_left_html  = '';
$modbodyhtml = '';

// if this is a display host screen then go ahead and make a puppet facts window
if ($extravars['window_name'] == 'html_desktop') {

        $title_left_html .= <<<EOL
                    Where to begin
EOL;


        $modbodyhtml .= <<<EOL
                If you are wondering where to start,<br>
                try one of these tasks:<br>
                <i class="nf nf-md-web_plus"></i>
                <a title="Add DNS domain"
                  class="act"
                  onClick="xajax_window_submit('edit_domain', 'js=> ', 'editor');"
                > Add a DNS domain</a>&nbsp;
                <br>
                <i class="nf nf-md-plus_network"></i>
                <a title="Add subnet"
                  class="act"
                  onClick="xajax_window_submit('edit_subnet', 'js=> ', 'editor');"
                > Add a new subnet</a>
                <br>
                <i class="nf nf-md-vector_polyline_plus"></i>
                <a title="Add host"
                  class="act"
                  onClick="xajax_window_submit('edit_host', 'js=> ', 'editor');"
                > Add a new host</a>
                <br>
                <i class="nf nf-seti-search"></i>
                <a title="Advanced search"
                  class="act"
                  onClick="xajax_window_submit('search_results', 'search_form_id=>subnet_search_form'); return false;"
                > Perform a search</a>&nbsp;
                <br>
                <i class="nf nf-fa-list_alt"></i>
                <a title="List Hosts"
                  class="act"
                  onClick="xajax_window_submit('search_results', 'search_form_id=>qsearch_form');"
                > List Hosts</a>
                <br><br>
                <ul>
                <li>If you need further assistance,<br>look for the <i class="nf nf-md-help_circle_outline"></i> icon<br>
                in the title bar of windows.<br></li>
                <li>You can also try the main<br> help index located <a href='{$_ENV['help_url']}'>here</a><br></li>
                </ul>
EOL;

   }



?>
