<?php
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
//require_once($base . '/config/config.inc.php');
require($include.'/desktop.inc.php');
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php echo $pagetitle; ?></title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-table.css" rel="stylesheet">
    <link href="css/typeahead.css" rel="stylesheet">
    <link href="css/onamain.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <a class="navbar-brand" href="#">
        <span class="glyphicon glyphicon-record" aria-hidden="true"></span>
      </a>
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Menu <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="test.php" class="ajax" data-replace=".ws_plugin_content">Add Subnet</a></li>
            <li><a href="#">Aadd Host</a></li>
            <li><a href="#">Stuff</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="#">Separated link</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="#">One more separated link</a></li>
          </ul>
        </li>
      </ul>
      <form class="navbar-form navbar-left" role="search">
        <div class="form-group">
          <div id="the-basics">
            <input type="text" class="typeahead form-control" placeholder="Quick Search">
          </div>
        </div>
        <button type="submit" class="btn btn-default">Submit</button>
      </form>
      <ul class="nav navbar-nav navbar-right">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Login <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="#">Logout</a></li>
            <li><a href="#">User Info</a></li>
          </ul>
        </li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>



<!-- start container for main content under navbar -->
  <div class="onacontainer">
   

    <div class="panel panel-default ws_panel">
      <div class="panel-heading">
        <h3 class="panel-title">Workspace: [Host] - host.example.com</h3>
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
               data-search="true">
            <thead>
                <tr>
                    <th data-sortable="true">Item ID</th>
                    <th data-sortable="true">Item Name</th>
                    <th data-sortable="true">Item Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Item 1</td>
                    <td>$1</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Item 2</td>
                    <td>$2</td>
                </tr>
            </tbody>
      </table>
    </div>


      </div>
    </div>


  </div>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/bootstrap-table.js"></script>
    <script src="js/eldarion-ajax.min.js"></script>
    <script src="js/typeahead.bundle.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/autocomplete.js"></script>
  </body>
</html>
