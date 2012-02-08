<?php
// NOTE: This file will be overwritten when you upgrade!!!
// You should copy this file to www/local/config/host_actions.config.php and make changes there
//
// This section defines host actions. If you leave the url blank it will not show the option in the list
// You can use %fqdn, %ip, %loc or %ca[custom_attr] as substitutions in the url for the host being displayed
// You can specify a tooltip title for the option, otherwise it defaults to the hostaction name "Telnet" "Splunk" etc
// These will be listed in the order specified here.
// %loc = the "reference" value from the hosts location
// %fqdn = the fully qualified primary name of this host
// %ip = the IP address of the primary interface for this host
// %ca[custom_attr] will be substituted by the value of the Custom Attribute "custom_attr". If the host doesn't have one,
// it will be substituted by the value of the system configuration "default_custom_attr".

//EXAMPLE:$conf[$modulename]['LINK_TITLE']['url'] = "http://something";
$conf[$modulename]['Splunk']['url'] = "https://splunk.example.com:8001/?events/?eventspage=1&num=10&q=%fqdn";
$conf[$modulename]['Cacti Graph']['url'] = "https://%ca[cacti_server]/cacti/graph_view.php?action=tree&name=%fqdn";
$conf[$modulename]['Wiki Page']['url'] = "https://wiki.%loc.example.com/dokuwiki/network/servers/%fqdn";
?>