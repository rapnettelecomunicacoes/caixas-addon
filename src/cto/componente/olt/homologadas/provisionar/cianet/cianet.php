
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
		$result = $telnet->Connect($olt->ipaddress, $olt->access_port, $olt->username, $olt->password, false);
	}
	/* ---------------------  FIM CONEXAO OLT --------------------- */
	$profileData = "HELPFIBER_DADOS";
	$msg = "";
	$resultado = "";
	$err = false;
	$ret["info"][0]["oltName"] = $olt->name;
	$ret["info"][0]["oltMaker"] = $olt->maker;
	$ip_add = $_SERVER["REMOTE_ADDR"];
	switch ($result) {
		case 0:

	/* --------------------- INICIO CRIANDO PERFIS --------------------- */
	if ($createProfile) {
		$cmmd[0] = "enable";
		$cmmd[1] = "configure terminal";
		$cmmd[2] = "gpon";
		$cmmd[3] = "profile bandwidth $profileData maximum 1200000";
		$cmmd[4] = "profile onutype HBR eth 1 pots 0 card 1 down-queue 8 up-queue 8";
		$cmmd[5] = "profile onutype HRT eth 4 pots 0 card 1 down-queue 8 up-queue 8";
		$cmmd[6] = "quit";
		$cmmd[7] = "exit";
		$cmmd[8] = "write";

		$telnet->DoCommand($cmmd, $result);
	}
	/* --------------------- FIM CRIANDO PERFIS --------------------- */


	/* --------------------- INICIO BUSCA ONU --------------------- */
	if (isset($dataInput["find"]) && $dataInput["find"]==="finont") {
		/* --------------------- INICIO AUTOFIND ONU --------------------- */
		if($dataInput["cmd"] === "autofind"){			
			$ret["info"][0]["cmd"] = $dataInput["cmd"];
	
				/* ---------------------  INICIO CONSULTA OLT --------------------- */
				$cmd[0] = "enable";
				$cmd[1] = "show onu unconfig";
				$telnet->DoCommand($cmd, $result);

				if (preg_match("/Total: 0/", $result)) {
					$ret["errorMessage"]["msg"] = utf8_encode("NENHUMA ONU ENCONTRADA!");
					$ret["errorMessage"]["btn"] = "back";
				} // END if (preg_match("/No related information/", $linhas[$h]))
				else {
					$linhas = explode("\n",$result);
					$qtdOnu=0;

					for($h=0; $h<=count($linhas)-1; $h++){
						if(strpos($linhas[$h], "gpon-olt") !== FALSE){
							$j=1;
							do {
								if (!preg_match("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", $linhas[$h+$j])) {
									$retorno = str_replace ( "   ", " ", trim($linhas[$h+$j]));
									$retorno = str_replace ( "  ", " ", $retorno);
									$retorno = str_replace ( "  ", " ", $retorno);
									$retorno = str_replace ( "  ", " ", $retorno);
									$retorno = str_replace ( "sn:", "", $retorno);
									$r = explode(" ",$retorno);
									$fsp = "0/$r[0]";
									$fspExp = explode("/", $fsp);
				    	    		$ret["retorno"][$j]["frame"] = $fspExp[0];
				    	    		$ret["retorno"][$j]["slot"] = $fspExp[1];
				    	    		$ret["retorno"][$j]["pon"] = $fspExp[2];
				    	    		$ret["retorno"][$j]["modelo"] = "unKnown";
				    	    		$ret["retorno"][$j]["serial"] = $r[2];
				    	    		$ret["retorno"][$j]["fsp"] = rtrim($fsp);

									include_once "../core/utils/utils.php";
									$vlan = makeVlan($fspExp[1],$fspExp[2],$olt->id);

				    	   			$ret["retorno"][$j]["json_string"] = json_encode(array(
										"portaOlt" => rtrim($fsp),
										"frame" => $fspExp[0],
										"slot" => $fspExp[1],
										"pon" => $fspExp[2],
										"onuOnt" => $r[2],
										"oltId" => $olt_id,
										"ontType" => "unKnown",
										"vlan" => $vlan
										));

									$qtdOnu++;
								}
								$j++;
							} while (!preg_match("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", $linhas[$h+$j]));
						}
					}

					$ret["info"][0]["qtd"] = $qtdOnu; 

				} // END elseif (preg_match("/Total: 0/", $linhas[$h]))
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
			$fsp = trim($client->porta_olt);
			$exFsp = explode("/", trim($client->porta_olt));
			$frame = $exFsp[0];
			$slot = $exFsp[1];
			$pon = $exFsp[2];
			$switch = explode(";",$client->switch);
			$oid = $switch[0];

			/* ---------------------  INICIO CONSULTA SINAL --------------------- */

				$cmd[0] = "enable";
				$cmd[1] = "configure terminal";
				$cmd[2] = "interface gpon-onu $slot/$pon:$oid";
				$cmd[3] = "show onu power";

				$telnet->DoCommand($cmd, $result);

				$linhas = explode("\n", $result);
					for($h=0; $h<=count($linhas)-1; $h++){
						if(preg_match("/olt rx power/", $linhas[$h])){
								$retorno = str_replace ( "  ", " ", $linhas[$h]);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "dbm", "", $retorno);
								$tx_op = explode(":", $retorno); 
						}

						if(preg_match("/onu rx power/", $linhas[$h])){
								$retorno = str_replace ( "  ", " ", $linhas[$h]);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "dbm", "", $retorno);
								$rx_op = explode(":", $retorno); 
						}
					}

			/* ---------------------  FIM CONSULTA SINAL --------------------- */

			$tx=0;$rx=0;
			if(isset($tx_op) && isset($rx_op)) {
				$tx += $tx_op[1];
				$rx += $rx_op[1];
			}

			$ret["retorno"][0]["tx_op"] = str_replace("\r", '', $tx);
			$ret["retorno"][0]["rx_op"] = str_replace("\r", '', $rx);
			$ret["retorno"][0]["olt_id"] = str_replace("\r", '', $olt_id);

			$ret["retorno"][0]["fsp"] = str_replace("\r", '', $fsp);
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
			$table = $dataInput['conf']['finTab'];
			$mode = $dataInput['conf']['mode'];
			$oltMaker = $dataInput['conf']['oltMaker'];
			$ctoName = $dataInput['conf']['ctoName'];
			$ctoPort = (is_numeric($dataInput['conf']['ctoPort'])) ? $dataInput['conf']['ctoPort']:null;

			$vlanData = $dataInput['conf']['srvVlan'];
			$usrVlan = $dataInput['conf']['usrVlan'];
			$vpData = "HELPFIBER_VLAN".$usrVlan;

			$json = json_decode($dataInput['conf']['jsonString']);
			$frame = $json->frame;
			$slot = $json->slot;
			$pon = $json->pon;
			$onu_ont = $json->onuOnt;
			$olt_id = $json->oltId; 
			$fsp = "$frame/$slot/$pon";
			$ont_type = ($mode === "bridge") ? "HBR" : "HRT";
			$tySQL = ($table==="cliente") ? "login" : "username";

   			$sqlCli = mysql_query("SELECT nome, porta_olt, onu_ont, switch, armario_olt, porta_splitter, caixa_herm, $tySQL, senha FROM sis_$table WHERE $tySQL='".$login."'");

		} // END if(!empty($dataInput['conf']))

		if (mysql_num_rows($sqlCli)>0) {
			$client = mysql_fetch_object($sqlCli);
			$ppp_login = $client->$tySQL;
			$ppp_pass = $client->senha;

			if ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null ) {

				$cmd[0] = "enable";
				$cmd[1] = "show interface gpon-olt$slot/$pon onu status";
				$telnet->DoCommand($cmd, $result);

				$linhasOid = explode("\n", $result);
				for ($h=0; $h<=count($linhasOid)-1; $h++) {
					if (strpos($linhasOid[$h], "$slot/$pon") !== FALSE) {
						$dL = explode(" ", $linhasOid[$h]); 
						$dL0 = explode(":", $dL[0]);
						$usedOid = $dL0[1];
      	  				$used[$usedOid] = utf8_encode("Oid em Uso");
					} // END if (strpos($linhasOid[$h], $fsp) !== FALSE)
				}

				for($n=128; $n>=1; $n--){
      	  			if (empty($used[$n])) $oid = $n;
      			} // END for($n=128; $n>=1; $n--)


					/* ---------------------  INICIO INCLUSAO ONT --------------------- */

				$cmd[0] = "configure terminal";
				$cmd[1] = "interface gpon-olt $slot/$pon";
				$cmd[2] = "onu $oid type $ont_type sn $onu_ont";
				$telnet->DoCommand($cmd, $result);

				if (preg_match("/failed/", $result)) { 
					$ret["errorMessage"]["msg"] = utf8_encode("FALHA NA AUTORIZA��O DA ONU!");
					$ret["errorMessage"]["btn"] = "back";
					$err = true;
				}

				$cmd[0] = "interface gpon-onu $slot/$pon:$oid";

				if ($mode==="bridge") {
					$cmd[1] = "tcont 1 profile $profileData";
					$cmd[2] = "gemport 1 tcont 1";
					$cmd[3] = "service-port 1 gemport 1 user-vlan untagged vlan-add $vlanData";
					$cmd[4] = "remote service 1 gem 1";
					$cmd[5] = "remote uni eth_1/1 vlan-mode transparent";
				}

				if ($mode==="router") {
					$cmd[1] = "tcont 1 profile $profileData";
					$cmd[2] = "gemport 1 tcont 1";
					$cmd[3] = "service-port 1 gemport 1 user-vlan $usrVlan vlan $vlanData";
					$cmd[4] = "remote service 1 gem 1";
					$cmd[5] = "remote uni eth_1/1 vlan-mode trunk tag $vlanData";
				}

				if (!$err) Sleep(4); $telnet->DoCommand($cmd, $result);

				if (preg_match("/%Failed/", $result)) { 
					$cmd[0] = "interface gpon-olt $slot/$pon";
					$cmd[1] = "no onu $oid";
					$telnet->DoCommand($cmd, $result);

					$ret["errorMessage"]["msg"] = utf8_encode("FALHA NO COMISSIONAMENTO DA ONU!");
					$ret["errorMessage"]["btn"] = "back";
					$err = true;
				}

					/* ---------------------  FIM INCLUSAO ONT --------------------- */

				/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
				if (!$err) {
					$oidModel = "$oid;$ont_type";
        			$sqlCliUp = mysql_query("UPDATE sis_$table SET porta_splitter = '" . $ctoPort . "', caixa_herm = '" . $ctoName . "', armario_olt = '" . $olt->name . "', porta_olt = '" . $fsp . "', switch = '" . $oidModel . "', onu_ont = '" . $onu_ont . "', interface = 'vlan" . $vlanData . "', accesslist = 'sim' where $tySQL = '" . $login . "'");

					$reg_data = date("d/m/Y H:i:s");
					$nome = $client->nome;

					$reg_admin = "alterou dados do cliente: $nome <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";
					$reg_central = "$login_atend alterou dados do cliente: <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";

					$sqlInAdm = mysql_query("INSERT INTO sis_logs (registro, data, login, operacao) VALUES ( '".$reg_admin."', '".$reg_data."', '".$login_atend."', '690498EE')");
					$sqlInUsr = mysql_query("INSERT INTO sis_logs (registro, data, login, tipo, operacao) VALUES ( '".$reg_central."', '".$reg_data."', '".$login."', 'central', '690498EE')");
				} // END if (!err)
				/* ---------------------  FIM UPDATE MK-AUTH --------------------- */

				if (!$err) {
					$ret["messages"]["msg"] = utf8_encode("ONU '$onu_ont' Habilitada com Sucesso!");
					$ret["messages"]["login"] = $login;
					$ret["messages"]["url"] = ($table==="adicional") ? "../../adicionais.php?acao=busca&busca=".$login."&campo=sis_adicional.username&enviar=Buscar" : "../../clientes.php?tipo=todos&busca=".$login."&campo=login&ordem=nenhum&enviar=Buscar";
				}

			} // END if ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )
			else {
				$ret["errorMessage"]["msg"] = utf8_encode("Login '".$login."' j� possui ONU cadastrada!");
				$ret["errorMessage"]["btn"] = "back";
			} // END elseif ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )

		} // END if (mysql_num_rows($sqlCli)>0)
		else {
			$ret["errorMessage"]["msg"] = utf8_encode("Login '".$login."' n�o existe nos clientes ativos!");
			$ret["errorMessage"]["btn"] = "back";
		} // END elseif ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )

	} // END if($dataInput["acao"]==="insont")
	/* ---------------------  FIM ACAO INSERT --------------------- */

	/* ---------------------  INICIO OUTRAS ACOES --------------------- */
	if(isset($dataInput["acao"]) && ($dataInput["acao"]==="delOnt" || $dataInput["acao"]==="resOnt" || $dataInput["acao"]==="unWan" || $dataInput["acao"]==="wifiConf")) {

		if ($dataInput['onuOnt']) {
			$onu_ont = $dataInput['onuOnt'];
			$table = $dataInput['finTab'];
			$tySQL = ($table==="cliente") ? "login" : "username";
   			$sql = mysql_query("SELECT nome, porta_olt, onu_ont, switch, caixa_herm, porta_splitter, armario_olt, $tySQL FROM sis_$table WHERE onu_ont='$onu_ont'");

   			while ($client = mysql_fetch_object($sql)) {
      			if ($client->porta_olt == null and $client->onu_ont == null and $client->switch == null ) {
        			$ret["info"][0]["error"] = "Login N�O possui ONU cadastrada!";
      			} // END if ($client->porta_olt == null and $client->onu_ont == null and $client->switch == null )
				else {
					$porta_olt = explode("/", $client->porta_olt);
					$frame = $porta_olt[0];
					$slot = $porta_olt[1];
					$pon = $porta_olt[2];
					$switch = explode(";",$client->switch);
					$oid = rtrim($switch[0]);
					$model = $switch[1];
					$nome = $client->nome;
					$onu_ont = $client->onu_ont;
					$login = ($table==="cliente") ? $client->login : $client->username;
      			} // END elseif ($client->porta_olt == null and $client->onu_ont == null and $client->switch == null )
   			} // END while ($client = mysql_fetch_object($sql))

			$gpon = "$frame/$slot";
			$fsp = "$frame/$slot/$pon";

		} // END if($dataInput['onuOnt'])

			/* ---------------------  INICIO EXCLUSAO ONT --------------------- */
			if ($dataInput["acao"]==="delOnt") {
				$cmd[0] = "enable";
				$cmd[1] = "configure terminal";
				$cmd[2] = "interface gpon-olt $slot/$pon";
				$cmd[3] = "no onu $oid";
			} // END if ($dataInput["acao"]==="delOnt") 
			/* ---------------------  FIM EXCLUSAO ONT --------------------- */
			/* ---------------------  INICIO REBOOT ONT --------------------- */
			if ($dataInput["acao"]==="resOnt") {
				$cmd[0] = "enable";
				$cmd[1] = "configure terminal";
				$cmd[2] = "interface gpon-onu $slot/$pon:$oid";
				$cmd[3] = "remote onu reboot";
			} // END if ($dataInput["acao"]==="resOnt")
			/* ---------------------  FIM REBOOT ONT --------------------- */

			$telnet->DoCommand($cmmd, $result);

			if (preg_match("/%Failed/", $result)) { $err = true; }

		/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
		if(!$err) {
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
				$err=true;
		break;
		case 2:
				$ret["errorMessage"]["msg"] = utf8_encode("FALHA NA COMUNICA��O COM HOST!");
				$ret["errorMessage"]["btn"] = "back";
				$err=true;
		break;
		case 3:
				$ret["errorMessage"]["msg"] = utf8_encode("FALHA LOGIN!");
				$ret["errorMessage"]["btn"] = "back";
				$err=true;
		break;
		case 4:
				$ret["errorMessage"]["msg"] = utf8_encode("FALHA PHP!");
				$ret["errorMessage"]["btn"] = "back";
				$err=true;
		break;
		case 5:
				$ret["errorMessage"]["msg"] = utf8_encode("OPS! REGISTRE O ADDON PARA CONTINUAR USANDO!");
				$ret["errorMessage"]["btn"] = "reg";
		break;
	}