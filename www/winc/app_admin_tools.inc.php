<?php

global $conf, $self, $onadb ;
global $font_family, $color, $style, $images;

// Check permissions
//if (!auth('advanced')) {
  //  $window['js'] = "removeElement('{$window_name}'); alert('Permission denied!');";
  //  return;
//}

$window['title'] = "Admin tools";

$window['js'] .= <<<EOL

EOL;

$window['html'] .= <<<EOL

<!-- Window Content -->

<div class="wwTT_ona_menu" style="width: 100%;">

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_user_list');"
         title="Manage users"
    ><img style="vertical-align: middle;" src="{$images}/silk/user.png" border="0"
     />&nbsp;Manage users</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_group_list');"
         title="Manage groups"
     ><img style="vertical-align: middle;" src="{$images}/silk/group.png" border="0"
     />&nbsp;Manage groups</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_location_list');"
         title="Manage locations"
    ><img style="vertical-align: middle;" src="{$images}/silk/map.png" border="0"
     />&nbsp;Manage locations</div>
<!--
    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="removeElement('start_menu'); toggle_window('app_template_list');"
         title="Template administration"
     ><img style="vertical-align: middle;" src="{$images}/silk/plugin.png" border="0"
     />&nbsp;Template administration</div>
-->
    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_sysconf_list');"
         title="Manage system config"
    ><img style="vertical-align: middle;" src="{$images}/silk/page_edit.png" border="0"
     />&nbsp;Manage system config</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_custom_attribute_type_list');"
         title="Manage custom attribute types"
    ><img style="vertical-align: middle;" src="{$images}/silk/tag_blue_edit.png" border="0"
     />&nbsp;Manage custom attribute types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_config_type_list');"
         title="Manage config types"
    ><img style="vertical-align: middle;" src="{$images}/silk/cog_edit.png" border="0"
     />&nbsp;Manage config types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_manufacturer_list');"
         title="Manage manufacturers"
    ><img style="vertical-align: middle;" src="{$images}/silk/lorry.png" border="0"
     />&nbsp;Manage manufacturers</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_device_role_list');"
         title="Manage device roles"
    ><img style="vertical-align: middle;" src="{$images}/silk/drive_edit.png" border="0"
     />&nbsp;Manage device roles</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_device_model_list');"
         title="Manage device models"
    ><img style="vertical-align: middle;" src="{$images}/silk/drive_edit.png" border="0"
     />&nbsp;Manage device models</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_device_type_list');"
         title="Manage device types"
    ><img style="vertical-align: middle;" src="{$images}/silk/drive_edit.png" border="0"
     />&nbsp;Manage device types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_dhcp_option_list');"
         title="Manage DHCP options"
    ><img style="vertical-align: middle;" src="{$images}/silk/table_edit.png" border="0"
     />&nbsp;Manage DHCP options</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_subnet_type_list');"
         title="Manage subnet types"
    ><img style="vertical-align: middle;" src="{$images}/silk/transmit_blue.png" border="0"
     />&nbsp;Manage subnet types</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_domain_list');"
         title="Manage DNS domains"
    ><img style="vertical-align: middle;" src="{$images}/silk/world_edit.png" border="0"
     />&nbsp;Manage DNS domains</div>

    <div class="row"
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_dhcp_failover_list');"
         title="Manage DHCP failover groups"
    ><img style="vertical-align: middle;" src="{$images}/silk/world_edit.png" border="0"
     />&nbsp;Manage DHCP failover groups</div>


    <div class="row"
        onMouseOver="this.className='hovered';"
        onMouseOut="this.className='row';"
        onClick="removeElement('start_menu'); toggle_window('app_plugin_list');"
        title="List Plugins"
    ><img style="vertical-align: middle;" src="{$images}/silk/plugin_edit.png" border="0"
    />&nbsp;Manage Plugins</div>


</div>

EOL;

?>
