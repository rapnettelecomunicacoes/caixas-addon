
<?php
if ($_SERVER["SCRIPT_FILENAME"] === __FILE__) { print 'Acesso negado...'; header('Refresh:5; url=/admin', TRUE, 302); exit(); }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/core/database/database.php") !== "c8d38e0556d70fa9bff6bf19188f4e17") { header('HTTP/1.1 500'); exit; }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/controllers/controller.php") !== "47331342647c55cf27643765fe804878") { header('HTTP/1.1 500'); exit; }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/core/constants/Net/Telnet3.php") !== "d36791b4ccf87f5e82af0ca0f1c53c71") { header('HTTP/1.1 500'); exit; }


	require_once "../core/constants/Net/Telnet3.php";

	/* ---------------------  INICIO CONEXAO OLT --------------------- */
	if (isset($regulate) && $dataInput["cmd"] === "autofind" && !in_array($olt->maker,$freeMaker) ) { 
		$result = 5;
	}
	else {
		$telnet = new PHPTelnet();
		$telnet->show_connect_error=0;
		$result = $telnet->Connect($olt->ipaddress, $olt->access_port, $olt->username, $olt->password);
	}
	/* ---------------------  FIM CONEXAO OLT --------------------- */
	$msg = "";
	$resultado = "";
	$err = false;
	$ret["info"][0]["oltName"] = $olt->name;
	$ret["info"][0]["oltMaker"] = $olt->maker;
	$ip_add = $_SERVER["REMOTE_ADDR"];

	switch ($result) {
		case 0:
	/* --------------------- INICIO BUSCA ONU --------------------- */
	if (isset($dataInput["find"]) && $dataInput["find"]==="finont") {

		/* --------------------- INICIO AUTOFIND ONU --------------------- */
		if($dataInput["cmd"] === "autofind"){
			$ret["info"][0]["cmd"] = $dataInput["cmd"];

				/* ---------------------  INICIO CONSULTA OLT --------------------- */
				$cmmd[0] = "ENABLE";
				$cmmd[1] = "$olt->password";
				$cmmd[2] = "cd gpononu";
				$cmmd[3] = "show unauth_discovery";

				$telnet->DoCommand($cmmd, $result);
				$result=  str_replace("[2J[1;74HMaster[2;1H", "", $result);

				$linhas = explode("\n", $result);
				$s=0;
				for ($h=0; $h<=count($linhas)-1; $h++) {
					if (strpos($linhas[$h], "ONU Unauth Table")) {
						$arr = array("-----  ONU Unauth Table ","-----",",");
						$clean = str_replace($arr, "", $linhas[$h]);
						$data_clean = explode(" ", $clean);
					    $slts = explode("=",$data_clean[0]);
					    $pns = explode("=",$data_clean[1]);
					    $imts = explode("=",$data_clean[2]);
					    $cmd[$s] = "clear \r show discovery slot $slts[1] link $pns[1]";
						$s++;
					} // END if(strpos($linhas[$h], "ONU Unauth Table"))
				} // END for($h=0; $h<=count($linhas); $h++)

				$telnet->DoCommand($cmd, $results);
				$results=  str_replace("[2J[1;74HMaster[2;1H", "", $results);
				$results=  str_replace("[2J[01;74HMaster", "", $results);
				$results=  str_replace("clear", "", $results);
				$results = str_replace( ", \n", "\n", $results);
				$results = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $results);

				$linhas1 = explode("\n", $results);
				$k = 0;
				for ($h1=0; $h1<=count($linhas1)-1; $h1++) {
					if (strpos($linhas1[$h1], "ONU Unauth Table")) {
						$arr1 = array("-----  ONU Unauth Table ","-----",",");
					  	$clean1 = str_replace($arr1, "", $linhas1[$h1]);
					  	$data_clean1 = explode(" ", $clean1);
					  	$slts1 = explode("=",$data_clean1[0]);
					  	$pns1 = explode("=",$data_clean1[1]);
					  	$imts1 = explode("=",$data_clean1[2]);
					  	$qtdOnts += $imts1[1];
					  	if ($imts1[1]>0) {
					  		for ($i=1; $i<=$imts1[1]; $i++) {
					    		$retorno = str_replace ( "  ", " ", $linhas1[$h1+$i+2]);
					    		$retorno = str_replace ( "  ", " ", $retorno);
					    		$retorno = str_replace ( "  ", " ", $retorno);
					   	  		$retorno = str_replace ( "  ", " ", $retorno);
					        	$data_clean2 = explode(" ", $retorno);
					    		$ret["retorno"][$k]["frame"] = 0;
					    		$ret["retorno"][$k]["slot"] = $slts1[1];
					    		$ret["retorno"][$k]["pon"] = $pns1[1];
					    		$ret["retorno"][$k]["modelo"] = rtrim($data_clean2[1]);
					    		$ret["retorno"][$k]["serial"] = $data_clean2[2];
					    		$ret["retorno"][$k]["fsp"] = "0/$slts1[1]/$pns1[1]";

								include_once "../core/utils/utils.php";
								$vlan = makeVlan($slts1[1],$pns1[1],$olt->id);

					    		$ret["retorno"][$k]["json_string"] = json_encode(array(
									"portaOlt" => "0/$slts1[1]/$pns1[1]",
									"frame" => 0,
									"slot" => $slts1[1],
									"pon" => $pns1[1],
									"onuOnt" => $data_clean2[2],
									"oltId" => $olt_id,
									"ontType" => str_replace("AN", "", $data_clean2[1]),
									"vlan" => $vlan
									));
								$k++;
							} // END for($i=1; $i<=$imts1[1]; $i++)

						} // END if($imts1[1]>0)

					} // END if(strpos($linhas1[$h1], "ONU Unauth Table"))

				} // END for($h1=0; $h1<=count($linhas1); $h1++)

				if($qtdOnts === 0) {
					$ret["errorMessage"]["msg"] = utf8_encode("NENHUMA ONU ENCONTRADA!");
					$ret["errorMessage"]["btn"] = "back";
				}
				else { $ret["info"][0]["qtd"] = $qtdOnts; }
				/* ---------------------  FIM CONSULTA OLT --------------------- */

		} // END if($dataInput["cmd"] === "autofind")
		/* --------------------- FIM AUTOFIND ONU --------------------- */

	} // END if($dataInput["find"]==="finont")
	/* --------------------- FIM BUSCA ONU --------------------- */

	/* --------------------- INICIO BUSCA SINAL --------------------- */
	if(isset($dataInput["find"]) && $dataInput["find"]==="finsig"){

		if(!empty($dataInput["onu_ont"])){
			$onu_ont = $dataInput["onu_ont"];
			$table = $dataInput['finTab'];
			$tySQL = ($table==="cliente") ? "login" : "username";
   			$sql = mysql_query("SELECT accesslist,mac,nome,porta_olt,onu_ont,switch,caixa_herm,porta_splitter,armario_olt,$tySQL FROM sis_$table WHERE onu_ont='$onu_ont'");

 		}

		if(mysql_num_rows($sql) > 0){
			$client = mysql_fetch_object($sql);
				$login = $client->$tySQL;
				$endereco = $client->endereco;
				$numero = $client->numero;
				$fsp = $client->porta_olt;
				$exp = explode("/",$fsp);
				$frame = $exp[0];
				$slot = $exp[1];
				$pon = $exp[2];
				$switch = explode(";",$client->switch);
				$oid = $switch[0];
				$model = $switch[1];

				/* ---------------------  INICIO CONSULTA SINAL --------------------- */
				$cmmd[0] = "ENABLE";
				$cmmd[1] = "$olt->password";
				$cmmd[2] = "cd gpononu";
				$cmmd[3] = "show cpu_using slot $slot link $pon onu $oid";
				$cmmd[4] = "show onu_time slot $slot link $pon onu $oid";
				$cmmd[5] = "show rtt_value slot $slot link $pon onu $oid";
				$cmmd[6] = "show optic_module slot $slot link $pon onu $oid";
				$cmmd[7] = "clear";
				$cmmd[8] = "cd epononu";
				$cmmd[9] = "cd qinq";
				$cmmd[10] = "show wanbind slot $slot $pon $oid index 1";
				$cmmd[11] = "show wancfg slot $slot $pon $oid index 1";

				$telnet->DoCommand($cmmd, $result);
				$vowels = array("[2J[1;74HMaster[2;1H", "[2J[01;74HMaster", "(V)", "('C)", "(Dbm)");
				$result=  str_replace($vowels, "", $result);
				$result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $result);

				$linhas = explode("\n", $result);
					for ($i=0; $i<=count($linhas)-1; $i++) {
						if (strpos($linhas[$i], "CPU")) {
		    				$cpu = explode(":", $linhas[$i+1]);
		    				$memory = explode(":", $linhas[$i+2]);
						} // END if(strpos($linhas[$i], "CPU"))	
						if(strpos($linhas[$i], "TIMESHOW")){
		    				$time_sys = explode("Date:", $linhas[$i+1]);
		    				$time_run = explode(":", $linhas[$i+2]);
						} // END if(strpos($linhas[$i], "TIMESHOW"))
						if(strpos($linhas[$i], "RTT VALUE")){
		    				$distancy = explode(" = ", $linhas[$i]);
						} // END if(strpos($linhas[$i], "RTT VALUE"))
						if(strpos($linhas[$i], "OPTIC MODULE")){
		    				$distance_type = explode(":", $linhas[$i+3]);
		    				$temperature = explode(":", $linhas[$i+4]);
		    				$voltage = explode(":", $linhas[$i+5]);
		    				$laser = explode(":", $linhas[$i+6]);
		   					$tx_op = explode(":", $linhas[$i+7]);
		    				$rx_op = explode(":", $linhas[$i+8]);
		    				$olt_rx_op = explode(":", $linhas[$i+9]);
						} // END if(strpos($linhas[$i], "OPTIC MODULE"))
					} // END for($i=0; $i<=count($linhas); $i++)
				/* ---------------------  FIM CONSULTA SINAL --------------------- */

			$tx=0;$rx=0;
			if(isset($tx_op) && isset($rx_op)) {
				$tx += $tx_op[1];
				$rx += $rx_op[1];
			}

			$ret["retorno"][0]["temperature"] = (isset($temperature)) ? str_replace("\r", '', $temperature[1]) : "N/A";
			$ret["retorno"][0]["voltage"] = (isset($voltage)) ? str_replace("\r", '', $voltage[1]) : "N/A";
			$ret["retorno"][0]["laser"] = (isset($laser)) ? str_replace("\r", '', $laser[1]) : "N/A";
			$ret["retorno"][0]["tx_op"] = str_replace("\r", '', $tx);
			$ret["retorno"][0]["rx_op"] = str_replace("\r", '', $rx);
			$ret["retorno"][0]["olt_id"] = str_replace("\r", '', $olt_id);

			$ret["retorno"][0]["fsp"] = str_replace("\r", '', $fsp);
			$ret["retorno"][0]["frame"] = str_replace("\r", '', $frame);
			$ret["retorno"][0]["slot"] = str_replace("\r", '', $slot);
			$ret["retorno"][0]["pon"] = str_replace("\r", '', $pon);
			$ret["retorno"][0]["onu_ont"] = str_replace("\r", '', $onu_ont);
			$ret["retorno"][0]["oid"] = str_replace("\r", '', $oid);
			$ret["retorno"][0]["login"] = $login;

			$ret["retorno"][0]["url"] = ($table==="cliente") ? "../../clientes.php?tipo=todos&busca=".$client->$tySQL."&campo=login&ordem=nenhum&enviar=Buscar" : "../../adicionais.php?acao=busca&busca=".$client->$tySQL."&campo=sis_adicional.username&enviar=Buscar";

			$ret["retorno"][0]["rx"] = $rx;

			$radQuery = mysql_query("SELECT callingstationid FROM radacct WHERE username='".$client->$tySQL."' && acctstoptime is NULL LIMIT 1");
			$callingstationid = (mysql_num_rows($radQuery) > 0) ? mysql_fetch_object($radQuery)->callingstationid : NULL;
			$mac = ($client->mac != NULL) ? $client->mac : $callingstationid;

			if($rx <= "-27"){ $sigBar = "5%"; $ret["retorno"][0]["bar"] = "5%"; $ret["retorno"][0]["cobar"] = "progressive-bar bg-danger progress-bar-striped progress-bar-animated"; $ret["retorno"][0]["texbar"] = "MUITO RUIM";}
			if($rx <= "-26" && $rx > "-27"){ $sigBar = "25%"; $ret["retorno"][0]["bar"] = "25%"; $ret["retorno"][0]["cobar"] = "progressive-bar bg-warning progress-bar-striped progress-bar-animated"; $ret["retorno"][0]["texbar"] = "RUIM"; }
			if($rx <= "-24" && $rx > "-26"){ $sigBar = "55%"; $ret["retorno"][0]["bar"] = "55%"; $ret["retorno"][0]["cobar"] = "progressive-bar bg-orange progress-bar-striped progress-bar-animated"; $ret["retorno"][0]["texbar"] = "ACEITAVEL"; }
			if($rx <= "-22" && $rx > "-24"){ $sigBar = "85%"; $ret["retorno"][0]["bar"] = "85%"; $ret["retorno"][0]["cobar"] = "progressive-bar bg-green progress-bar-striped progress-bar-animated"; $ret["retorno"][0]["texbar"] = "BOM"; }
			if($rx <= "-12" && $rx > "-22"){ $sigBar = "95%"; $ret["retorno"][0]["bar"] = "95%"; $ret["retorno"][0]["cobar"] = "progressive-bar bg-blue progress-bar-striped progress-bar-animated"; $ret["retorno"][0]["texbar"] = "MUITO BOM"; }
			if($rx > "-12"){ $sigBar = "100%"; $ret["retorno"][0]["bar"] = "100%"; $ret["retorno"][0]["cobar"] = "progressive-bar bg-hidanger progress-bar-striped progress-bar-animated"; $ret["retorno"][0]["texbar"] = "MUITO FORTE"; }

			if (!empty($mac) && $client->accesslist==='sim') mysql_query("INSERT INTO tab_sinal (idapi, sinal, mac, cartao, rate, data) VALUES ( '".$fsp.":".$oid."', '".$sigBar."', '".$mac."', '".$onu_ont."', '".$tx." dBm / ".$rx." dBm', NOW())");


		} // END if(mysql_num_rows($sql) > 0)

	} // END if($dataInput["find"]==="finsig")
	/* --------------------- FIM BUSCA SINAL --------------------- */

	/* --------------------- INICIO WiFi CONFIG --------------------- */
	if (isset($dataInput["find"]) && $dataInput["find"]==="wifiConf") {
		$fsp = explode("/",$dataInput["ontFsp"]);
		$oid = $dataInput["ontId"];
		$slot = $fsp[1];
		$pon = $fsp[2];

		/* ---------------------  INICIO CONSULTA OLT --------------------- */
		$cmmd[0] = "ENABLE";
		$cmmd[1] = "$olt->password";
		$cmmd[2] = "cd gpononu";
		$cmmd[3] = "show wifi_serv slot $slot link $pon onu $oid";
		$cmmd[4] = "exit";

		$telnet->DoCommand($cmmd, $result);
		$result=  str_replace("[2J[1;74HMaster[2;1H", "", $result);
		$result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $result);

		$linhas = explode("\n", $result);

			for($h=0; $h<=count($linhas)-1; $h++){
				if(strpos($linhas[$h], "SSID:")){
					$expSsid = explode(":",$linhas[$h]);
				} // END if(strpos($linhas[$h], "SSID:"))

				if(strpos($linhas[$h], "WPA Share Key:")){
					$expPass = explode(":",$linhas[$h]);
				} // END if(strpos($linhas[$h], "WPA Share Key:"))

			} // END for($h=0; $h<=count($linhas); $h++)

			$ret["retorno"][0]["ssidName"] = rtrim($expSsid[1]);
			$ret["retorno"][0]["ssidPass"] = rtrim($expPass[1]);

	} // if($dataInput["find"]==="wifiConf")
	/* --------------------- FIM BUSCA SINAL --------------------- */

	/* ---------------------  INICIO ACAO INSERT --------------------- */
	if(isset($dataInput["acao"]) && $dataInput["acao"]==="insont") {

		if(!empty($dataInput['conf'])){
			$login = trim($dataInput['conf']['login']);
			$table = $dataInput['conf']['finTab'];
			$mode = $dataInput['conf']['mode'];
			$oltMaker = $dataInput['conf']['oltMaker'];
			$ctoName = $dataInput['conf']['ctoName'];
			$ctoPort = (is_numeric($dataInput['conf']['ctoPort'])) ? $dataInput['conf']['ctoPort']:null;

			$usrVlan = $dataInput['conf']['usrVlan'];
			$srvVlan = $dataInput['conf']['srvVlan'];
			$useCttr = (isset($dataInput['conf']['useCttr'])) ? $dataInput['conf']['useCttr'] : "nao";
			$rxCttr = (isset($dataInput['conf']['rxCttr'])) ? $dataInput['conf']['rxCttr'] : "";
			$txCttr = (isset($dataInput['conf']['txCttr'])) ? $dataInput['conf']['txCttr'] : "";
			$useSsid = (isset($dataInput['conf']['useSsid'])) ? $dataInput['conf']['useSsid'] : "nao";
			$ssidName = (isset($dataInput['conf']['ssidName'])) ? $dataInput['conf']['ssidName'] : "";
			$ssidPass = (isset($dataInput['conf']['ssidPass'])) ? $dataInput['conf']['ssidPass'] : "";

			$json = json_decode($dataInput['conf']['jsonString']);
			$frame = $json->frame;
			$slot = $json->slot;
			$pon = $json->pon;
			$onu_ont = $json->onuOnt;
			$olt_id = $json->oltId; 
			$ont_type = ($olt->maker === "fiberhome") ? str_replace("AN", "", $json->ontType) : $json->ontType;

			$gpon = "$frame/$slot";
			$fsp = "$frame/$slot/$pon";

			$wan = (isset($dataInput['conf']['wan'])) ? implode(" ", $dataInput['conf']['wan']) : "";
			$veipMode = (isset($dataInput['conf']['veipMode'])) ? $dataInput['conf']['veipMode'] : "nao";
			$veipProfile = (isset($dataInput['conf']['veipProfile'])) ? $dataInput['conf']['veipProfile'] : "";

			$tySQL = ($table==="cliente") ? "login" : "username";
   			$sqlCli = mysql_query("SELECT nome, porta_olt, onu_ont, switch, armario_olt, porta_splitter, caixa_herm, $tySQL, senha FROM sis_$table WHERE $tySQL='".$login."'");

		} // END if(!empty($dataInput['conf']))

		if (mysql_num_rows($sqlCli)>0) {
			$client = mysql_fetch_object($sqlCli);
			$ppp_login = $client->$tySQL;
			$ppp_pass = $client->senha;

			if ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null ) {

					/* ---------------------  INICIO INCLUSAO ONT --------------------- */
					$cmmd[0] = "ENABLE";
					$cmmd[1] = "$olt->password";
					$cmmd[2] = "cd gpononu";
					$cmmd[3] = "set whitelist phy_addr address $onu_ont password null action add slot $slot link $pon onu null type $ont_type";
					$cmmd[4] = "show authorization slot $slot link $pon";

					$telnet->DoCommand($cmmd, $result);
					$vowels = array("[2J[1;74HMaster[2;1H", "[2J[01;74HMaster");
					$result=  str_replace($vowels, "", $result);
					$result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $result);
					$result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $result);
					$linhas = explode("\n", $result);
					for ($h=0; $h<=count($linhas)-1; $h++) {
						if (strpos($linhas[$h], $onu_ont)) {
							$retorno = str_replace ( "   ", " ", $linhas[$h]);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$data_clean = explode(" ", $retorno);
							$oid = $data_clean[3];
						} // END if(strpos($linhas[$h], $onu_ont))
					} // END for($h=0; $h<=count($linhas); $h++)

					$cmmd2[0] = "set authorization slot $slot link $pon type $ont_type onuid $oid phy_id $onu_ont password null";
					$cmmd2[1] = "clear";
					$cmmd2[2] = "cd epononu";

					if($useCttr==='sim') {
						$cmmd2[3] = "set epon slot $slot pon $pon onu $oid bandwidth upstream_band $txCttr downstream_band $rxCttr";
						$cmmd2[4] = "cd qinq";
					} // END if($useCttr==='sim')
					else {
						$cmmd2[3] = "cd qinq";
						$cmmd2[4] = "clear";
					} // END else if($useCttr==='sim')

					if($mode === "bridge"){
			  			if($veipMode==='sim') {
							$cmmd2[5] = "set epon slot $slot pon $pon onu $oid port 1 onuveip $veipProfile 33024 $srvVlan 65535 33024 $srvVlan 65535 33024 65535 65535 0 1 65535 servname null";
							$cmmd2[6] = "clear";
			  			} // END if($veipMode==='sim')
			  			else {
							$cmmd2[5] = "set epon slot $slot pon $pon onu $oid port 1 service number 1";
							$cmmd2[6] = "clear";
							$cmmd2[7] = "set epon slot $slot pon $pon onu $oid port 1 service 1 vlan_mode tag 0 33024 $srvVlan";
							$cmmd2[8] = "apply onu $slot $pon $oid vlan";
			  			} // END else if($veipMode==='sim')
					} // END if($mode === "bridge")

					if($mode === "router"){ 
			 			$entries = count($dataInput['conf']['wan']);
			  			$cmmd2[5] = "set wancfg slot $slot $pon $oid index 1 mode internet type route $srvVlan 0xffff nat enable qos disable dsp pppoe proxy disable admin 1234 null auto";
			  			$cmmd2[6] = "clear";
			  			$cmmd2[7] = "set wanbind slot $slot $pon $oid index 1 entries $entries $wan";
			  			$cmmd2[8] = "apply wancfg slot $slot $pon $oid";
			  			$cmmd2[9] = "apply wanbind slot $slot $pon $oid";
			  			$cmmd2[10] = "set wancfg slot $slot $pon $oid index 1 mode internet type route $srvVlan 0xffff nat enable qos disable dsp pppoe proxy disable $ppp_login $ppp_pass null auto";
			  			$cmmd2[11] = "clear";
			  			$cmmd2[12] = "set wanbind slot $slot $pon $oid index 1 entries $entries $wan";
			  			$cmmd2[13] = "apply wancfg slot $slot $pon $oid";
			  			$cmmd2[14] = "apply wanbind slot $slot $pon $oid";
						$cmmd2[15] = "cd gpononu";
						$cmmd2[16] = "set onu_local_manage_config slot $slot link $pon onu $oid config_enable_switch enable console_switch enable telnet_switch enable web_switch enable web_port 80 web_ani_switch enable tel_ani_switch disable";
			  			if($useSsid==='sim') {
			    			$cmmd2[17] = "set wifi_serv_wlan slot $slot link $pon onu $oid index 1 ssid enable $ssidName hide disable authmode wpa2psk encrypt_type aes wpakey $ssidPass interval 0 radius_serv ipv4 192.168.1.18 port 1812 pswd 12345678";
			    			$cmmd2[18] = "set wifi_serv_cfg slot $slot link $pon onu $oid wifi enable district etsi channel 0";
			  			} // ENS if($useSsid==='sim')
					} // END if($mode === "router")

					$telnet->DoCommand($cmmd2, $result);
					$vowels = array("[2J[1;74HMaster[2;1H", "[2J[01;74HMaster");
					$result=  str_replace($vowels, "", $result);
					$result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $result);

					if(!str_replace("apply", "", $result)){
						$err = true;
					} // END if(!str_replace("apply", "", $result))
					/* ---------------------  FIM INCLUSAO ONT --------------------- */

				/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
				if ($err !== true) {
					$oidModel = "$oid;$ont_type";
        			$sqlCliUp = mysql_query("UPDATE sis_$table SET porta_splitter = '" . $ctoPort . "', caixa_herm = '" . $ctoName . "', armario_olt = '" . $olt->name . "', porta_olt = '" . $fsp . "', switch = '" . $oidModel . "', onu_ont = '" . $onu_ont . "', interface = 'vlan" . $srvVlan . "', accesslist = 'sim' where $tySQL = '" . $login . "'");

					$reg_data = date("d/m/Y H:i:s");
					$ip_add = $_SERVER["REMOTE_ADDR"];
					$nome = $client->nome;

					$reg_admin = "alterou dados do cliente: $nome <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";
					$reg_central = "$login_atend alterou dados do cliente: <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";

					$sqlInAdm = mysql_query("INSERT INTO sis_logs (registro, data, login, operacao) VALUES ( '".$reg_admin."', '".$reg_data."', '".$login_atend."', '690498EE')");
					$sqlInUsr = mysql_query("INSERT INTO sis_logs (registro, data, login, tipo, operacao) VALUES ( '".$reg_central."', '".$reg_data."', '".$login."', 'central', '690498EE')");
				} // END if (!err)
				/* ---------------------  FIM UPDATE MK-AUTH --------------------- */

				$ret["messages"]["msg"] = utf8_encode("ONU '$onu_ont' Habilitada com Sucesso!");
				$ret["messages"]["login"] = $login;
				$ret["messages"]["url"] = ($table==="adicional") ? "../../adicionais.php?acao=busca&busca=".$login."&campo=sis_adicional.username&enviar=Buscar" : "../../clientes.php?tipo=todos&busca=".$login."&campo=login&ordem=nenhum&enviar=Buscar";

			} // END if ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )
			else {
				$ret["errorMessage"]["msg"] = utf8_encode("Login '".$login."' j� possui ONU cadastrada!");
				$ret["errorMessage"]["btn"] = "back";
			} // END elseif ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )

		} // END if (mysql_num_rows($sqlCli)>0)
		else {
			$ret["errorMessage"]["msg"] = utf8_encode("Login '".$login."' n�o existe nos clientes ativos!");
			$ret["errorMessage"]["btn"] = "back";
		} // END elseif (mysql_num_rows($sqlCli)>0)

	} // END if($dataInput["acao"]==="insont")
	/* ---------------------  FIM ACAO INSERT --------------------- */

	/* ---------------------  INICIO OUTRAS ACOES --------------------- */
	if(isset($dataInput["acao"]) && ($dataInput["acao"]==="delOnt" || $dataInput["acao"]==="resOnt" || $dataInput["acao"]==="unWan" || $dataInput["acao"]==="wifiConf")) {

		if ($dataInput['onuOnt']) {
			$onu_ont = $dataInput['onuOnt'];
			$table = $dataInput['finTab'];
			$tySQL = ($table==="cliente") ? "login" : "username";
   			$sql = mysql_query("SELECT nome, porta_olt, onu_ont, switch, caixa_herm, porta_splitter, armario_olt, $tySQL FROM sis_$table WHERE onu_ont='$onu_ont'");

   			$client = mysql_fetch_object($sql);

			$porta_olt = explode("/", $client->porta_olt);
			$frame = $porta_olt[0];
			$slot = $porta_olt[1];
			$pon = $porta_olt[2];
			$switch = explode(";",$client->switch);
			$oid = rtrim($switch[0]);
			$model = $switch[1];
			$nome = $client->nome;
			$onu_ont = $client->onu_ont;
			$login = $client->$tySQL;
			$gpon = "$frame/$slot";
			$fsp = "$frame/$slot/$pon";

		} // END if($dataInput['onuOnt'])

			$cmmd[0] = "ENABLE";
			$cmmd[1] = "$olt->password";
			/* ---------------------  INICIO EXCLUSAO ONT --------------------- */
			if ($dataInput["acao"]==="delOnt") {
				$cmmd[2] = "cd epononu";
				$cmmd[3] = "cd qinq";
				$cmmd[4] = "del wanbind slot $slot $pon $oid index 1";
				$cmmd[5] = "del wancfg slot $slot $pon $oid index 1";
				$cmmd[6] = "cd gpononu";
				$cmmd[7] = "set whitelist phy_addr address $onu_ont password null action delete slot $slot link $pon onu $oid type $model";
			} // END if ($dataInput["acao"]==="delOnt") 
			/* ---------------------  FIM EXCLUSAO ONT --------------------- */
			/* ---------------------  INICIO REBOOT ONT --------------------- */
			if ($dataInput["acao"]==="resOnt") {
				$cmmd[2] = "cd gpononu";
				$cmmd[3] = "reset slot $slot link $pon onulist $oid";
			} // END if ($dataInput["acao"]==="resOnt") 
			/* ---------------------  FIM REBOOT ONT --------------------- */
			/* ---------------------  INICIO WAN CONFIG ONT --------------------- */
			if ($dataInput["acao"]==="wifiConf") {
				$ontFsp = $dataInput['conf']['ontFsp'];
				$fsp = explode("/",$ontFsp);
				$slot = $fsp[1];
				$pon = $fsp[2];
				$oid = $dataInput["conf"]["ontId"];
				$ssidName = rtrim($dataInput['conf']['ssidName']);
				$ssidPass = rtrim($dataInput['conf']['ssidPass']);

				$cmmd[2] = "cd gpononu";
				$cmmd[3] = "set wifi_serv_wlan slot $slot link $pon onu $oid index 1 ssid enable $ssidName hide disable authmode wpa2psk encrypt_type aes wpakey $ssidPass interval 0 radius_serv ipv4 192.168.1.18 port 1812 pswd 12345678";
				$cmmd[4] = "set wifi_serv_cfg slot $slot link $pon onu $oid wifi enable district etsi channel 0";
			} // END if ($dataInput["acao"]==="wifiConf")
			/* ---------------------  FIM WAN CONFIG ONT --------------------- */
			/* ---------------------  INICIO WAN ACCESS ONT --------------------- */
			if ($dataInput["acao"]==="unWan") {
				$cmmd[2] = "cd gpononu";
				$cmmd[3] = "set onu_local_manage_config slot $slot link $pon onu $oid config_enable_switch enable console_switch enable telnet_switch enable web_switch enable web_port 80 web_ani_switch enable tel_ani_switch disable";
			} // END if ($dataInput["acao"]==="unWan") 
			/* ---------------------  FIM WAN ACCESS ONT --------------------- */

			$telnet->DoCommand($cmmd, $result);

			if ($dataInput["acao"]==="delOnt") {
				if (preg_match("/ERR/", $result)) {
					$err = true;
         			$ret["errorMessage"]["msg"] = "Erro ao excluir ONU '".$client->onu_ont."'!";
					$ret["errorMessage"]["btn"] = "back";
				} // END if (!strpos($result, "ok"))
			} // END if ($dataInput["acao"]==="delOnt")

		/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
		if($err !== true) {
			$reg_data = date("d/m/Y H:i:s");

			if ($dataInput["acao"]==="delOnt") {
        		$sqlUpCli = mysql_query("UPDATE sis_$table SET porta_olt = null, armario_olt = null, switch = null, onu_ont = null, caixa_herm = null, porta_splitter = null, interface = null where $tySQL = '$login'");
				$reg_admin = "alterou dados do cliente: ".$nome." <b>removido: ONU/ONT</b> ".$onu_ont." - IP: $ip_add";
				$reg_central = "$login_atend alterou dados do cliente: <b>removido: ONU/ONT</b> ".$onu_ont." - IP: $ip_add";
				$sqlInAdm = mysql_query("INSERT INTO sis_logs (registro, data, login, operacao) VALUES ('".$reg_admin."', '".$reg_data."', '".$login_atend."', '690498EE')");
				$sqlInUsr = mysql_query("INSERT INTO sis_logs (registro, data, login, tipo, operacao) VALUES ('".$reg_central."', '".$reg_data."', '".$login."', 'central', '690498EE')");
        		$ret["info"][0]["title"] = utf8_encode("EXCLUS�O");
        		$ret["messages"]["msg"] = "ONT $onu_ont EXCLUIDA COM SUCESSO!!!";
			} // END if ($dataInput["acao"]==="delOnt")


			if ($dataInput["acao"]==="resOnt") {
        		$ret["info"][0]["title"] = "REBOOT";
        		$ret["messages"]["msg"] = "ONT $onu_ont REINICIADA COM SUCESSO !!!";
			} // END if ($dataInput["acao"]==="resOnt")

			if ($dataInput["acao"]==="unWan") {
        		$ret["info"][0]["title"] = "ACESSO REMOTO";
        		$ret["messages"]["msg"] = "ACESSO WAN PARA ONT $onu_ont LIBERADO COM SUCESSO !!!";
			} // END if ($dataInput["acao"]==="unWan") 

			if ($dataInput["acao"]==="wifiConf") {
        		$ret["info"][0]["title"] = utf8_encode("CONFIGURA��O WI-FI");
        		$ret["messages"]["msg"] = "SSID E SENHA DA ONT ".$dataInput['conf']['ontSn']." ALTERADOS COM SUCESSO !!!";
			} // END if ($dataInput["acao"]==="wifiConf") 

		} // END if(!$err)
		/* ---------------------  FIM UPDATE MK-AUTH --------------------- */

	} // END if($dataInput["acao"]==="delOnt" || $dataInput["acao"]==="resOnt" || $dataInput["acao"]==="unWan")
	/* ---------------------  FIM OUTRAS ACOES --------------------- */

	/* ---------------------  INICIO FECHANDO CONEXAO --------------------- */
	$telnet->Disconnect();
	/* ---------------------  FIM FECHANDO CONEXAO --------------------- */

		break;
		case 1:
				$ret["errorMessage"]["msg"] = utf8_encode("FALHA NA COMUNICA��O COM A OLT!");
				$ret["errorMessage"]["btn"] = "back";
		break;
		case 2:
				$ret["errorMessage"]["msg"] = utf8_encode("FALHA NA COMUNICA��O COM HOST!");
				$ret["errorMessage"]["btn"] = "back";
		break;
		case 3:
				$ret["errorMessage"]["msg"] = utf8_encode("FALHA LOGIN!");
				$ret["errorMessage"]["btn"] = "back";
		break;
		case 4:
				$ret["errorMessage"]["msg"] = utf8_encode("FALHA PHP VERSION!");
				$ret["errorMessage"]["btn"] = "back";
		break;
		case 5:
				$ret["errorMessage"]["msg"] = utf8_encode("OPS! REGISTRE O ADDON PARA CONTINUAR USANDO!");
				$ret["errorMessage"]["btn"] = "reg";
		break;
	}