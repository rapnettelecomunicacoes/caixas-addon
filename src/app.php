<?php
  include('./src/cto/config/database.hhvm');
  include('./src/cto/database/index.hhvm');
  include('./src/cto/models/client.hhvm');

  $get_routeprincipal = (isset($_REQUEST['_routeprincipal'])) ? $_REQUEST['_routeprincipal'] : null;

  switch($get_routeprincipal) {
      case "cto": 
        include('./src/cto/componente/app.hhvm');
        break; 
    
    default:
        include('./src/cto/componente/app.hhvm');
      break;
  }