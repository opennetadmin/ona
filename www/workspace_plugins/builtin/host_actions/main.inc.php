<?php

$rec_content = print_r($record, true);
$debug_display = 0;  // Set this to 1 to see contents of the $record array


// Load any user specific entries and add them to the list
// If you re-define an existing entry, it will override the default
$mod_conf="{$base}/local/config/{$modulename}.config.php";
if (file_exists($mod_conf)) { require_once($mod_conf); }


$title_left_html = 'Host Actions';


$title_right_html .= '';


if ($debug_display) {
    $modbodyhtml .= <<<EOL
        <pre>{$rec_content}</pre>
EOL;
}

// If there is a location id then get the info so we can replace it later
if ($record['location_id'] > 0) {
    // TODO: maybe do some checks if we dont find a location but ohhhh well for now.
    list($status, $rows, $location) = ona_get_location_record(array('id' => $record['location_id']));
}

// Build hostaction list from the $conf['hostaction'] array
foreach ($conf[$modulename] as $hostaction=>$hval) {
    // Skip the module title entry
    // Use the title if there is one, otherwise just use the arrayname
    $hval['title'] = ($hval['title']) ? $hval['title'] : $hostaction;
    // Substitute %fqdn and %ip and %loc
    $hval['url'] = str_replace('%fqdn', $record['fqdn'], $hval['url']);
    $hval['url'] = str_replace('%ip', $record['ip_address'], $hval['url']);
    $hval['url'] = str_replace('%loc', $location['reference'], $hval['url']);



    // Get custom attributes if there is "%ca[.*]" string in URL
    // Patch from Greg.  It allows custom attribute replacements in host actions
    // if it does not find a CA it will check in the system_config table for that name
    // beginning with "default_"
    $found_ca_types = preg_match_all("#%ca\[(.*?)\]#",$hval['url'],$ca_types,PREG_PATTERN_ORDER);
    if ( $found_ca_types ) {
       foreach ($ca_types[1] as $name) {
            $replace_with='';
            // Get the CA value for this host
            list($status, $rows, $attribute) = ona_get_record("custom_attribute_type_id in (select id from custom_attribute_types where name='".$name."') and table_id_ref = ".$record['id']." and table_name_ref = 'hosts'",'custom_attributes');
            if ( $rows) {
                $replace_with=$attribute['value'];
            }
            else {
                // If there's no CA for this host, last chance search in system config
                list($status,$conf_rows,$conf) = ona_get_record("name = 'default_".$name."'",'sys_config');
                if ($conf_rows) {
                    $replace_with=$conf['value'];
                }
            }
            $hval['url'] = str_replace("%ca[$name]", $replace_with, $hval['url']);
       }
    }



    // If the URL has data in it, print.
    // TODO: MDP, maybe offer an $hval['icon'] option to use a different icon specified in the $conf['hostaction']['Name']['icon'] variable
    if ($hval['url']) {
    $modbodyhtml .= <<<EOL
            <span>
                <a title="{$hval['title']}"
                    class="act"
                    href="{$hval['url']}"
                ><img src="{$images}/silk/lightning_go.png" border="0">{$hostaction}</a>&nbsp;
            </span><br>
EOL;
    }
}


?>