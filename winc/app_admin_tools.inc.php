<?

global $conf, $self, $onadb ;
global $font_family, $color, $style, $images;

// Check permissions
//if (!auth('advanced')) {
  //  $window['js'] = "removeElement('{$window_name}'); alert('Permission denied!');";
  //  return;
//}

$window['title'] = "Admin tools";

$window['js'] .= <<<EOL
    
    /* Put a minimize icon in the title bar */
    el('{$window_name}_title_r').innerHTML = 
        '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;
    
    /* Put a help icon in the title bar */
    el('{$window_name}_title_r').innerHTML = 
        '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;
    
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
         onClick="removeElement('start_menu'); toggle_window('app_template_list');"
         title="Template administration"
     ><img style="vertical-align: middle;" src="{$images}/silk/plugin.png" border="0"
     />&nbsp;Template administration</div>
    
 <!-- Decided not to use this option.. left it here for now   
    <div class="row" 
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_system_default_list');"
         title="Manage system defaults"
    ><img style="vertical-align: middle;" src="{$images}/silk/page_edit.png" border="0"
     />&nbsp;Manage system defaults</div>
  -->

    <div class="row" 
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_infobit_type_list');"
         title="Manage classification type"
    ><img style="vertical-align: middle;" src="{$images}/silk/tag_blue_edit.png" border="0"
     />&nbsp;Manage classification types</div>

    <div class="row" 
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_infobit_list');"
         title="Manage classifications"
    ><img style="vertical-align: middle;" src="{$images}/silk/tag_blue_edit.png" border="0"
     />&nbsp;Manage classifications</div>

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
         onClick="toggle_window('app_device_type_list');"
         title="Manage device types"
    ><img style="vertical-align: middle;" src="{$images}/silk/drive_edit.png" border="0"
     />&nbsp;Manage device types</div>
    
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
         onClick="toggle_window('app_dhcp_parameter_type_list');"
         title="Manage DHCP parameter types"
    ><img style="vertical-align: middle;" src="{$images}/silk/table_edit.png" border="0"
     />&nbsp;Manage DHCP parameter types</div>

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
         onClick="toggle_window('app_zone_list');"
         title="Manage DNS zones"
    ><img style="vertical-align: middle;" src="{$images}/silk/world_edit.png" border="0"
     />&nbsp;Manage DNS zones</div>

    <div class="row" 
         onMouseOver="this.className='hovered';"
         onMouseOut="this.className='row';"
         onClick="toggle_window('app_dhcp_failover_list');"
         title="Manage DHCP failover groups"
    ><img style="vertical-align: middle;" src="{$images}/silk/world_edit.png" border="0"
     />&nbsp;Manage DHCP failover groups</div>


</div>

EOL;

?>