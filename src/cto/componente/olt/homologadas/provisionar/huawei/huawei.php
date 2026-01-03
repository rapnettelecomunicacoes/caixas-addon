
<?php
if ($_SERVER["SCRIPT_FILENAME"] === __FILE__) { print 'Acesso negado...'; header('Refresh:5; url=/admin', TRUE, 302); exit(); }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/core/database/database.php") !== "c8d38e0556d70fa9bff6bf19188f4e17") { header('HTTP/1.1 500'); exit; }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/controllers/controller.php") !== "47331342647c55cf27643765fe804878") { header('HTTP/1.1 500'); exit; }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/core/constants/Net/SSH2.php") !== "240b848b1b25503c7288bf28a166727a") { header('HTTP/1.1 500'); exit; }

	include "../core/constants/Net/SSH2.php";

	$msg = "";
	$resultado = "";
	$err = false;
	$ret["info"][0]["oltName"] = $olt->name;
	$ret["info"][0]["oltMaker"] = $olt->maker;
	$ip_add = $_SERVER["REMOTE_ADDR"];

	/* ---------------------  INICIO CONEXAO OLT --------------------- */
	if (isset($regulate) && $dataInput["cmd"] === "autofind" && !in_array($olt->maker,$freeMaker)) {
		$ret["errorMessage"]["msg"] = utf8_encode("OPS! REGISTRE O ADDON PARA CONTINUAR USANDO!");
		$ret["errorMessage"]["btn"] = "reg";
 	}
	else {
		$ssh = new Net_SSH2($olt->domain);
		if (!$ssh->login($olt->username, $olt->password)) {
			$ret["errorMessage"]["msg"] = utf8_encode("Falha na comunica��o com OLT!");
			$ret["errorMessage"]["btn"] = "back";
		}
		$ssh->read('username@username:~$');

	/* ---------------------  FIM CONEXAO OLT --------------------- */
 
	/* --------------------- INICIO BUSCA ONU --------------------- */
	if (isset($dataInput["find"]) && $dataInput["find"]==="finont") {

		/* --------------------- INICIO AUTOFIND ONU --------------------- */
		if($dataInput["cmd"] === "autofind"){
			$ret["info"][0]["cmd"] = $dataInput["cmd"];

				/* ---------------------  INICIO CONSULTA OLT --------------------- */
        		$ssh->write("\n enable\n config\n");
         		$ssh->write(" display ont autofind all\n");
				$result = $ssh->read('username@username:~$');

 				do {
					$vowels = array(
						"---- More ( Press 'Q' to break ) ----",
						"[37D                                     [37D"
			  		);
					$result = str_replace($vowels, "", $result);
         			$ssh->write("\t\n");
					$result .= $ssh->read('username@username:~$');
				}
				while( strripos($result,"---- More ( Press 'Q' to break ) ----") !== false || strripos($result,"[37D                                     [37D") !== false );

				$linhas = explode("\n", $result);
				$s = 0;
				$retQtd = 0;
				for ($n=0; $n<=count($linhas)-1; $n++) {
					if (preg_match("/Failure:/", $linhas[$n])) {
						$ret["errorMessage"]["msg"] = utf8_encode("NENHUMA ONU ENCONTRADA!");
						$ret["errorMessage"]["btn"] = "back";
					}
					if (strpos($linhas[$n], "The number of GPON autofind ONT is")) {
						$retQtd += preg_replace("/[^0-9]/", "", $linhas[$n]);
						$ret["info"][0]["qtd"] = $retQtd;
					}
					if (strpos($linhas[$n], "F/S/P               : 0/")) {
						$fsp_line = explode(" : ", $linhas[$n]);
						$fsp_explode = explode("/", trim($fsp_line[1]));
						$serial_line = explode(" : ", $linhas[$n+1]);
						$serial_explode = explode(" ", $serial_line[1]);
						$modelo_line = str_replace("[37D                                     [37D ", "", $linhas[$n+8]);
						$modelo_explode = explode(" : ", $modelo_line);
						$ret["retorno"][$s]["modelo"] = rtrim($modelo_explode[1]);
						$ret["retorno"][$s]["serial"] = $serial_explode[0];
						$ret["retorno"][$s]["fsp"] = $fsp_line[1];
						$ret["retorno"][$s]["frame"] = $fsp_explode[0];
						$ret["retorno"][$s]["slot"] = $fsp_explode[1];
						$ret["retorno"][$s]["pon"] = $fsp_explode[2];

						include_once "../core/utils/utils.php";
						$vlan = makeVlan($fsp_explode[1],$fsp_explode[2],$olt->id);
						$comm = makeComm($fsp_explode[1],$fsp_explode[2],$olt->id);

						$ret["retorno"][$s]["json_string"] = json_encode(array(
										"portaOlt" => $fsp_line[1],
										"frame" => $fsp_explode[0],
										"slot" => $fsp_explode[1],
										"pon" => $fsp_explode[2],
										"onuOnt" => $serial_explode[0],
										"oltId" => $olt_id,
										"ontType" => $modelo_explode[1],
										"vlan" => $vlan,
										"comm" => $comm
										));
						$s++;
					}
				}
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
			$sql = mysql_query("SELECT accesslist,mac,porta_olt,switch,onu_ont,$tySQL,nome FROM sis_$table WHERE onu_ont='$onu_ont'");
 		}
		if(mysql_num_rows($sql) > 0){
			$client = mysql_fetch_object($sql);
			$login = $client->$tySQL;
			$fsp = rtrim($client->porta_olt);
			$fspEx = explode("/",$fsp);
			$frame = $fspEx[0];
			$slot = $fspEx[1];
			$pon = $fspEx[2];
			$switch = explode(";",$client->switch);
			$oid = $switch[0];

				/* ---------------------  INICIO CONSULTA SINAL ---------------------  */
				$gpon = "$frame/$slot";
				$interfaces = "interface gpon $gpon"; 	
				$cmdFind = "display ont optical-info $pon $oid";

				$ssh->write("\n enable\n config\n $interfaces\n $cmdFind\n \n \n \n \n \n \n \n \n");

				while ($line = $ssh->read('username@username:~$')) {
					$line = str_replace("---- More ( Press 'Q' to break ) ----[37D                                     [37D", "", $line);
					$linhas = explode("\n", $line);
					for ($n=0; $n<=count($linhas)-1; $n++) {
						if(strpos($linhas[$n], "Rx optical power(dBm)                  : ")){
							$rx_op = explode(" : ", $linhas[$n]);
						}

						if(strpos($linhas[$n], "Tx optical power(dBm)                  : ")){
							$tx_op = explode(" : ", $linhas[$n]);
						}

						if(strpos($linhas[$n], "Laser bias current(mA)                 : ")){
							$laser = explode(" : ", $linhas[$n]);
						}

						if(strpos($linhas[$n], "Temperature(C)                         : ")){
							$temperature = explode(" : ", $linhas[$n]);
						}

						if(strpos($linhas[$n], "Voltage(V)                             : ")){
							$voltage = explode(" : ", $linhas[$n]);
						}

						if(strpos($linhas[$n], "OLT Rx ONT optical power(dBm)          : ")){
							$olt_rx_op = explode(" : ", $linhas[$n]);
						}
					} // END for ($n=0; $n<=count($linhas); $n++)
				} // END while ($line = $ssh->read('username@username:~$'))
				/* ---------------------  FIM CONSULTA SINAL ---------------------  */

			$tx=0;$rx=0;
			if(isset($olt_rx_op) && isset($rx_op)) {
				$tx += $olt_rx_op[1];
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
			$ret["retorno"][0]["login"] = $client->$tySQL;

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

	/* ---------------------  INICIO ACAO INSERT --------------------- */
	if(isset($dataInput["acao"]) && $dataInput["acao"]==="insont") {

		if(!empty($dataInput['conf'])){
			$login = $dataInput['conf']['login'];
			$mode = $dataInput['conf']['mode'];
			$oltMaker = $dataInput['conf']['oltMaker'];
			$ctoName = $dataInput['conf']['ctoName'];
			$ctoPort = (is_numeric($dataInput['conf']['ctoPort'])) ? $dataInput['conf']['ctoPort']:null;

			$json = json_decode($dataInput['conf']['jsonString']);
			$frame = $json->frame;
			$slot = $json->slot;
			$pon = $json->pon;
			$onu_ont = $json->onuOnt;
			$olt_id = $json->oltId; 
			$ont_type = $json->ontType;
			$gpon = "$frame/$slot";
			$fsp = "$frame/$slot/$pon";

			$usrVlan = $dataInput['conf']['usrVlan'];
			$srvVlan = $dataInput['conf']['srvVlan'];


			$useOmci = (isset($dataInput['conf']['useOmci'])) ? $dataInput['conf']['useOmci'] : "nao";

			$useTt = (isset($dataInput['conf']['useTt'])) ? $dataInput['conf']['useTt'] : "nao";
			$ttIn = (isset($dataInput['conf']['ttIn'])) ? $dataInput['conf']['ttIn'] : "";
			$ttOut = (isset($dataInput['conf']['ttOut'])) ? $dataInput['conf']['ttOut'] : "";

			$useCttr = (isset($dataInput['conf']['useCttr'])) ? $dataInput['conf']['useCttr'] : "nao";
			$rxCttr = (isset($dataInput['conf']['rxCttr'])) ? $dataInput['conf']['rxCttr'] : "";
			$txCttr = (isset($dataInput['conf']['txCttr'])) ? $dataInput['conf']['txCttr'] : "";

			$gemport = (isset($dataInput['conf']['gemport'])) ? $dataInput['conf']['gemport'] : "";
			$lineProfile = (isset($dataInput['conf']['lineProfile'])) ? $dataInput['conf']['lineProfile'] : "";
			$srvProfile = (isset($dataInput['conf']['srvProfile'])) ? $dataInput['conf']['srvProfile'] : "";
			$wanProfile = (isset($dataInput['conf']['wanProfile'])) ? $dataInput['conf']['wanProfile'] : "";
			$wan = (isset($dataInput['conf']['wan'])) ? $dataInput['conf']['wan'] : "";
			$qinq = (isset($dataInput['conf']['qinq'])) ? $dataInput['conf']['qinq'] : "nao";

			$table = $dataInput['conf']['finTab'];
			$tySQL = ($table==="cliente") ? "login" : "username";
   			$sqlCli = mysql_query("SELECT nome, porta_olt, onu_ont, switch, armario_olt, porta_splitter, caixa_herm, $tySQL, senha FROM sis_$table WHERE $tySQL='".$login."'");

		} // END if(!empty($dataInput['conf']))

		if (mysql_num_rows($sqlCli)==0) $err = 2;
		
		if (!$err) {
			$client = mysql_fetch_object($sqlCli);
			if ($client->porta_olt !== null || $client->onu_ont !== null || $client->switch !== null) $err = 3;
		}
			
		if (!$err) {
         	$ssh->write("\n enable\n config\n");
			$ssh->write("display ont info by-sn $onu_ont\n\n");
			$result0 = $ssh->read('username@username:~$');
			if (!preg_match("/ONT does not exist/", $result0)) $err = 4;
		}

		if (!$err) {
			/* ---------------------  INICIO INCLUSAO ONT --------------------- */
			$ppp_login = $client->$tySQL;
			$ppp_pass = $client->senha;
        	$ssh->write("interface gpon $gpon\n");

			if ($useOmci==="nao") {
         		$ssh->write("ont add $pon sn-auth $onu_ont omci desc \"to ".$login." by HelpFiber\"\n\n");
            	$result1 = $ssh->read('username@username:~$');
				if (preg_match("/The line profile does not exist/", $result1)) $err = 5;

				if (!$err) {
         			$pos = strpos($result1, "ONTID :");
         			$oid = rtrim(substr($result1,$pos+7,3));

         			$ssh->write("quit\n");
					for($n=0;$n<count($wan)-1;$n++) {
         				$ssh->write("service-port vlan ".$srvVlan." gpon ".$porta_olt." ont ".$oid." ".$wan[$n]." multi-service user-vlan untagged\n\n");
					}
	         		$result2 = $ssh->read('username@username:~$');
				}
			}

			if ($useOmci==="sim") {

				if ($useTt === "sim") { $traficCttr = "transparent inbound traffic-table index $ttIn outbound traffic-table index $ttOut"; }
				if ($useCttr === "sim") { $traficCttr = "translate rx-cttr $rxCttr tx-cttr $txCttr";}
				$executa = ($useCttr === "sim" || $useTt === "sim") ? "tag-transform $traficCttr\n\n" : "\n\n";
   				$ssh->write("ont add $pon sn-auth $onu_ont omci ont-lineprofile-id ".$lineProfile." ont-srvprofile-id ".$srvProfile." desc \"to ".$login." by HelpFiber\"\n\n");
       			$result1 = $ssh->read('username@username:~$');

				if (preg_match("/The line profile does not exist/", $result1)) $err = 5;
				//if (!is_numeric($oid)) $err = 5;

				if (!$err) {
   					$pos = strpos($result1, "ONTID :");
   					$oid = rtrim(substr($result1,$pos+7,3));
					for($n=0;$n<count($wan);$n++) {
						if ($mode === "bridge" && $qinq === "sim") {
							$ssh->write("ont port vlan ".$pon." ".$oid." ".$wan[$n]." q-in-q ".$srvVlan." user-vlan untagged\n\n");
						} else {
							$ssh->write("ont port native-vlan ".$pon." ".$oid." ".$wan[$n]." vlan ".$usrVlan."\n\n");
						}
					}

       				if ($mode === "router") {
						$ssh->write("ont ipconfig ".$pon." ".$oid." pppoe vlan ".$usrVlan." priority 0 user-account username ".$ppp_login." password ".$ppp_pass."\n\n");
						$ssh->write("ont internet-config ".$pon." ".$oid." ip-index 0\n\n");
						$ssh->write("ont wan-config ".$pon." ".$oid." ip-index 0 profile-id ".$wanProfile."\n\n");
	  					for($n=0;$n<count($wan);$n++) {
							$ssh->write("ont port route ".$pon." ".$oid." ".$wan[$n]." enable\n\n");
	  					}
					}

   					$ssh->write("quit\n");
   					$ssh->write("service-port vlan ".$srvVlan." gpon ".$fsp." ont ".$oid." gemport ".$gemport." multi-service user-vlan ".$usrVlan." ".$executa);
   					$result2 = $ssh->read('username@username:~$');

   					if ($mode === "router") {
						$ssh->write("quit\nquit\ny\n");
						sleep(1);
						$ssh = new Net_SSH2($olt->domain);
						$ssh->login($olt->username, $olt->password);
						$ssh->write("\n enable\n diagnose\n");
						$ssh->write("ont wan-access http ".$frame."/".$slot."/".$pon." ".$oid." enable\n\n");
   						$result3 = $ssh->read('username@username:~$');
					}
				}
			}
			/* ---------------------  FIM INCLUSAO ONT --------------------- */
			//$result = $result0 . $result1 . $result2 . $result3;
		}

		/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
		if (!$err) {
			$oidModel = "$oid;$ont_type";
        	$sqlCliUp = mysql_query("UPDATE sis_$table SET porta_splitter = '" . $ctoPort . "', caixa_herm = '" . $ctoName . "', armario_olt = '" . $olt->name . "', porta_olt = '" . $fsp . "', switch = '" . $oidModel . "', onu_ont = '" . $onu_ont . "', interface = 'vlan" . $srvVlan . "', accesslist = 'sim' where $tySQL = '" . $login . "'");

			$reg_data = date("d/m/Y H:i:s");
			$ip_add = $_SERVER["REMOTE_ADDR"];
			$nome = $client->nome;

			$reg_admin = "alterou dados do cliente: $nome <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";
			$reg_central = "$login_atend alterou dados do cliente: <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";

			$sqlInAdm = mysql_query("INSERT INTO sis_logs (registro, data, login, operacao) VALUES ( '".$reg_admin."', '".$reg_data."', '".$login_atend."', '690498EE')");
			$sqlInUsr = mysql_query("INSERT INTO sis_logs (registro, data, login, tipo, operacao) VALUES ( '".$reg_central."', '".$reg_data."', '".$login."', 'central', '690498EE')");

			$ret["messages"]["msg"] = "ONU '$onu_ont' Habilitada com Sucesso!";
			$ret["messages"]["login"] = $login;
			$ret["messages"]["url"] = ($table==="adicional") ? "../../adicionais.php?acao=busca&busca=".$login."&campo=sis_adicional.username&enviar=Buscar" : "../../clientes.php?tipo=todos&busca=".$login."&campo=login&ordem=nenhum&enviar=Buscar";

		} // END if (!err)
		/* ---------------------  FIM UPDATE MK-AUTH --------------------- */

		if ($err) {
			switch($err) {
				case 2:
					$ret["errorMessage"]["msg"] = utf8_encode("Login '".$login."' n�o existe nos clientes ativos!");
					$ret["errorMessage"]["btn"] = "back";
				break;
				case 3:
					$ret["errorMessage"]["msg"] = utf8_encode("Login '".$login."' j� possui ONU cadastrada!");
					$ret["errorMessage"]["btn"] = "back";
				break;
				case 4:
					$linhas = explode("\n", $result0);
					for($h=0; $h<=count($linhas)-1; $h++){
						if ( preg_match("/F\/S\/P/", $linhas[$h]) ) { $fsp = explode(":", $linhas[$h]); $fsp=trim($fsp[1]); }
						if ( preg_match("/ONT-ID/", $linhas[$h]) ) { $oid = explode(":", $linhas[$h]); $oid=trim($oid[1]); }
						if ( preg_match("/Description/", $linhas[$h]) ) { $desc = explode(":", $linhas[$h]); $desc=trim($desc[1]); }
					}
					$ret["errorMessage"]["msg"] = utf8_encode("ONU j� cadastrada, descri��o '".$desc."', FSP: $fsp, OID: $oid");
					$ret["errorMessage"]["btn"] = "back";
				break;
				case 5:
					$ret["errorMessage"]["msg"] = utf8_encode("Erro ao autorizar, reveja o comissionamento da ONU!!");
					$ret["errorMessage"]["btn"] = "back";
				break;
				default:
					$ret["errorMessage"]["msg"] = utf8_encode("Erro default!!");
					$ret["errorMessage"]["btn"] = "back";
				break;
			}
		}
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

        	$ssh->write("\n enable\n config\n");
			/* ---------------------  INICIO EXCLUSAO ONT --------------------- */
			if ($dataInput["acao"]==="delOnt") {
         		$ssh->write("undo service-port port $gpon/$pon ont $oid \n\ny\n");
         		$ssh->write("interface gpon $gpon \n");
         		$ssh->write("ont delete $pon $oid \n");
			} // END if ($dataInput["acao"]==="delOnt") 
			/* ---------------------  FIM EXCLUSAO ONT --------------------- */
			/* ---------------------  INICIO REBOOT ONT --------------------- */
			if ($dataInput["acao"]==="resOnt") {
         		$ssh->write("interface gpon $gpon \n");
       	  		$ssh->write("ont reset $pon $oid \ny\n");
			} // END if ($dataInput["acao"]==="resOnt") 
			/* ---------------------  FIM REBOOT ONT --------------------- */
			/* ---------------------  INICIO WAN ACCESS ONT --------------------- */
			if ($dataInput["acao"]==="unWan") {
         		$ssh->write("diagnose \n");
       	  		$ssh->write("ont wan-access http $gpon/$pon $oid enable \n");
			} // END if ($dataInput["acao"]==="unWan") 
			/* ---------------------  FIM WAN ACCESS ONT --------------------- */
        	$resultado = $ssh->read('username@username:~$');

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
}