<?
//EXAMPLE:$conf[$modulename]['LINK_TITLE']['url'] = "http://something";
$conf[$modulename]['Splunk']['url'] = "https://splunk.example.com:8001/?events/?eventspage=1&num=10&q=%fqdn";
$conf[$modulename]['Cacti Graph']['url'] = "https://cacti.example.com/cacti/graph_view.php?action=tree&name=%fqdn";
$conf[$modulename]['Wiki Page']['url'] = "https://wiki.example.com/dokuwiki/network/servers/%fqdn";
?>