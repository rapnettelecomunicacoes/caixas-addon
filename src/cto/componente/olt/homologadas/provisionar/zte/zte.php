<?php
/**
*	Autor: brsavi
*	Date: 2020-06-01
*	Version: 20.04r62
*/

if ($_SERVER['SCRIPT_FILENAME'] === __FILE__) { print 'Acesso negado...'; header('Refresh:5; url=/admin', TRUE, 302); exit(); }
//if ( strtotime( date( "Y-m-d", filemtime( __FILE__ ) ) ) > strtotime( date( "Y-m-d", strtotime('06/01/2020') ) ) ) { header('HTTP/1.1 500'); exit; }

	require_once "../core/constants/Net/Telnet3.php";

	/* ---------------------  INICIO CONEXAO OLT --------------------- */
	if ($regulate && $dataInput["cmd"] === "autofind" && !in_array($olt->maker,$freeMaker) ) { 
		$result = 5;
	}
	else {
		$telnet = new PHPTelnet();
		$telnet->show_connect_error=0;
		$result = $telnet->Connect($olt->ipaddress, $olt->access_port, $olt->username, $olt->password);
	}
	/* ---------------------  FIM CONEXAO OLT --------------------- */
//	$profileData = "HELP";
	$profileData = "HELPFIBER_DADOS";
//	$profileVoIP = "HELPFIBER_VOIP";
//	$profileIPTv = "HELPFIBER_IPTV";
	$msg = "";
	$resultado = "";
	$err = false;
	$ret['info'][0]["oltName"] = $olt->name;
	$ret['info'][0]["oltMaker"] = $olt->maker;
	$login_atend = $_SESSION["MM_Usuario"];
	$ip_add = $_SERVER["REMOTE_ADDR"];
	switch ($result) {
		case 0:

	/* --------------------- INICIO CRIANDO PERFIS --------------------- */
	if ($createProfile) {
		$cmmd[0] = "configure terminal";
		$cmmd[1] = "gpon";
		$cmmd[2] = "profile tcont $profileData type 4 maximum 1024000";
		//$cmmd[2] = "profile tcont $profileVoIP type 3 assured 512 maximum 1024";
		//$cmmd[2] = "profile tcont $profileIPTv type 2 assured 2048";
		$cmmd[3] = "profile traffic HELPUP sir 1024000 pir 1024000";
		$cmmd[4] = "profile traffic HELPDW sir 1024000 pir 1024000";
		$cmmd[5] = "exit";
		$cmmd[6] = "pon";
		$cmmd[7] = "onu-type HBR gpon description HELP_BRIDGE";
		$cmmd[8] = "onu-type HBR gpon max-tcont 7";
		$cmmd[9] = "onu-type HBR gpon max-gemport 32";
		$cmmd[10] = "onu-type HBR gpon max-switch-perslot 8";
		$cmmd[11] = "onu-type HBR gpon max-flow-perswitch 8";
		$cmmd[12] = "onu-type HBR gpon max-iphost 2";
		$cmmd[13] = "onu-type-if HBR eth_0/1-4";
		$cmmd[14] = "onu-type HRT gpon description HELP_ROUTER";
		$cmmd[15] = "onu-type HRT gpon max-tcont 7";
		$cmmd[16] = "onu-type HRT gpon max-gemport 32";
		$cmmd[17] = "onu-type HRT gpon max-switch-perslot 8";
		$cmmd[18] = "onu-type HRT gpon max-flow-perswitch 8";
		$cmmd[19] = "onu-type HRT gpon max-iphost 5";
		$cmmd[20] = "onu-type HRT gpon max-ipv6-host 5";
		$cmmd[21] = "onu-type-if HRT eth_0/1-4";
		$cmmd[22] = "onu-type-if HRT pots_0/1-2";
		$cmmd[23] = "onu-type-if HRT wifi_0/1-8";
		$cmmd[24] = "exit";
		$cmmd[25] = "exit";
		$cmmd[26] = "write";
		$telnet->DoCommand($cmmd, $result);
		if (!preg_match("/Successful/", $result)) {
				$err=true;
		} // END if (preg_match("/No related information/", $linhas[$h]))
	}
	/* --------------------- FIM CRIANDO PERFIS --------------------- */


	/* --------------------- INICIO BUSCA ONU --------------------- */
	if ($dataInput["find"]==="finont") {
		/* --------------------- INICIO AUTOFIND ONU --------------------- */
		if($dataInput["cmd"] === "autofind"){			
			$ret['info'][0]["cmd"] = $dataInput["cmd"];
	
				/* ---------------------  INICIO CONSULTA OLT --------------------- */
				$cmd[0] = "show gpon onu uncfg";
				$telnet->DoCommand($cmd, $results);
				$linhas = explode("\n", $results);
				for ($h=0; $h<=count($linhas); $h++) {

					if (preg_match("/No related information/", $linhas[$h])) {
						$ret['errorMessage']['msg'] = utf8_encode("NENHUMA ONU ENCONTRADA!");
						$ret['errorMessage']['btn'] = "back";
					} // END if (preg_match("/No related information/", $linhas[$h]))
					else {

						if (strpos($linhas[$h], "OnuIndex") !== FALSE) {
							$j=1;
							do{
								$retorno = str_replace ( "   ", " ", $linhas[$h+1+$j]);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$dL = explode(" ", $retorno); 
								$dL0 = explode(":", $dL[0]);
								$fsp = str_replace ( "gpon-onu_", "", $dL0[0]);
								$fspExp = explode("/", $fsp);
				    	    	$ret['retorno'][$j]["frame"] = $fspExp[0];
				    	    	$ret['retorno'][$j]["slot"] = $fspExp[1];
				    	    	$ret['retorno'][$j]["pon"] = $fspExp[2];
				    	    	$ret['retorno'][$j]["modelo"] = "unKnown";
				    	    	$ret['retorno'][$j]["serial"] = $dL[1];
				    	    	$ret['retorno'][$j]["fsp"] = rtrim($fsp);

								include_once "../core/utils/utils.php";
								$vlan = makeVlan($fspExp[1],$fspExp[2],$olt->id);

				    	   		$ret[retorno][$j]["json_string"] = json_encode(array(
									"portaOlt" => rtrim($fsp),
									"frame" => $fspExp[0],
									"slot" => $fspExp[1],
									"pon" => $fspExp[2],
									"onuOnt" => $dL[1],
									"oltId" => $olt_id,
									"ontType" => "unKnown",
									"vlan" => $vlan
									));
								$j++;
							} while( strpos($linhas[$h+1+$j],"#") === false );

							$qtdOnts += $j;

						} // END if (strpos($linhas[$h], "OnuIndex") !== FALSE)

					} // END for ($h=0; $h<=count($linhas); $h++)

					$ret[info][0]["qtd"] = $qtdOnts; 
				} // END elseif (preg_match("/No related information/", $linhas[$h]))
				/* ---------------------  FIM CONSULTA OLT --------------------- */

		} // END if($dataInput["cmd"] === "autofind")
		/* --------------------- FIM AUTOFIND ONU --------------------- */

	} // END if($dataInput["find"]==="finont")
	/* --------------------- FIM BUSCA ONU --------------------- */

	/* --------------------- INICIO BUSCA SINAL --------------------- */
	if($dataInput["find"]==="finsig"){

		if(!empty($dataInput["onu_ont"])){
			$onu_ont = $dataInput["onu_ont"];
			$table = $dataInput['finTab'];
			$tySQL = ($table==="cliente") ? "login" : "username";
			$sql = mysqli_query($connection, "SELECT accesslist,mac,porta_olt,switch,onu_ont,$tySQL,nome FROM sis_$table WHERE onu_ont='$onu_ont'");
 		}
		if(mysqli_num_rows($sql) > 0){
			$client = mysqli_fetch_object($sql);
			$fsp = rtrim($client->porta_olt);
			$switch = explode(";",$client->switch);
			$oid = $switch[0];

			/* ---------------------  INICIO CONSULTA SINAL --------------------- */

			$cmmd[0] = "show pon power attenuation gpon-onu_$fsp:$oid";

			$telnet->DoCommand($cmmd, $result);

			$linhas = explode("\n", $result);
				for($h=0; $h<=count($linhas); $h++){
					if(preg_match("/up/", $linhas[$h])){
							$retorno = str_replace ( "        ", " ", $linhas[$h]);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$sL = explode(" ", $retorno); 
							$tx_op = explode(":", $sL[4]);
							//$tx_op = $sL1[1];
					}

					if(preg_match("/down/", $linhas[$h])){
							$retorno = str_replace ( "   ", " ", $linhas[$h]);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$s2L = explode(" ", $retorno); 
							$rx_op = explode(":", $s2L[4]);
							//$rx_op = $s2L1[1];
					}
				}

			/* ---------------------  FIM CONSULTA SINAL --------------------- */

			$ret['retorno'][0]["tx_op"] = str_replace("\r", '', $tx_op[1]);
			$ret['retorno'][0]["rx_op"] = str_replace("\r", '', $rx_op[1]);
			$ret['retorno'][0]["olt_id"] = str_replace("\r", '', $olt_id);

			$ret['retorno'][0]["fsp"] = str_replace("\r", '', $fsp);
			$ret['retorno'][0]["onu_ont"] = str_replace("\r", '', $onu_ont);
			$ret['retorno'][0]["oid"] = str_replace("\r", '', $oid);
			$ret['retorno'][0]["login"] = $client->$tySQL;
			$ret['retorno'][0]["url"] = ($table==="cliente") ? "../../clientes.php?tipo=todos&busca=".$client->$tySQL."&campo=login&ordem=nenhum&enviar=Buscar" : "../../adicionais.php?acao=busca&busca=".$client->$tySQL."&campo=sis_adicional.username&enviar=Buscar";

			$tx += $tx_op[1];
			$rx += $rx_op[1];
			$ret['retorno'][0]["rx"] = $rx;

			$radQueryResult = mysqli_query($connection, "SELECT callingstationid FROM radacct WHERE username='".$client->$tySQL."' && acctstoptime is NULL LIMIT 1");
			$mac = ($client->mac != NULL) ? $client->mac : (mysqli_num_rows($radQueryResult) > 0 ? mysqli_fetch_object($radQueryResult)->callingstationid : NULL);

			if($rx <= "-27"){ $sigBar = "5%"; $ret['retorno'][0]["bar"] = "5%"; $ret['retorno'][0]["cobar"] = "progressive-bar bg-danger progress-bar-striped progress-bar-animated"; $ret['retorno'][0]["texbar"] = "MUITO RUIM";}
			if($rx <= "-26" && $rx > "-27"){ $sigBar = "25%"; $ret['retorno'][0]["bar"] = "25%"; $ret['retorno'][0]["cobar"] = "progressive-bar bg-warning progress-bar-striped progress-bar-animated"; $ret['retorno'][0]["texbar"] = "RUIM"; }
			if($rx <= "-24" && $rx > "-26"){ $sigBar = "55%"; $ret['retorno'][0]["bar"] = "55%"; $ret['retorno'][0]["cobar"] = "progressive-bar bg-orange progress-bar-striped progress-bar-animated"; $ret['retorno'][0]["texbar"] = "ACEITAVEL"; }
			if($rx <= "-22" && $rx > "-24"){ $sigBar = "85%"; $ret['retorno'][0]["bar"] = "85%"; $ret['retorno'][0]["cobar"] = "progressive-bar bg-green progress-bar-striped progress-bar-animated"; $ret['retorno'][0]["texbar"] = "BOM"; }
			if($rx <= "-12" && $rx > "-22"){ $sigBar = "95%"; $ret['retorno'][0]["bar"] = "95%"; $ret['retorno'][0]["cobar"] = "progressive-bar bg-blue progress-bar-striped progress-bar-animated"; $ret['retorno'][0]["texbar"] = "MUITO BOM"; }
			if($rx > "-12"){ $sigBar = "100%"; $ret['retorno'][0]["bar"] = "100%"; $ret['retorno'][0]["cobar"] = "progressive-bar bg-hidanger progress-bar-striped progress-bar-animated"; $ret['retorno'][0]["texbar"] = "MUITO FORTE"; }

			if (!empty($mac) && $client->accesslist==='sim') mysqli_query($connection, "INSERT INTO tab_sinal (idapi, sinal, mac, cartao, rate, data) VALUES ( '".$fsp.":".$oid."', '".$sigBar."', '".$mac."', '".$onu_ont."', '".$tx." dBm / ".$rx." dBm', NOW())");


		} // END if(mysql_num_rows($sql) > 0)

	} // END if($dataInput["find"]==="finsig")
	/* --------------------- FIM BUSCA SINAL --------------------- */

	/* ---------------------  INICIO ACAO INSERT --------------------- */
	if($dataInput["acao"]==="insont") {

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
		//	$vlanVoIP = $dataInput['conf']['vlanVoIP'];
		//	$vpVoIP = "VLAN_".$vlanVoIP;
		//	$vlanIPTv = $dataInput['conf']['vlanIPTv'];
		//	$vpIPTv = "VLAN_".$vlanIPTv;
		//	$useCttr = $dataInput['conf']['useCttr'];
		//	$rxCttr = $dataInput['conf']['rxCttr'];
		//	$txCttr = $dataInput['conf']['txCttr'];
		//	$useSsid = $dataInput['conf']['useSsid'];
		//	$ssidName = $dataInput['conf']['ssidName'];
		//	$ssidPass = $dataInput['conf']['ssidPass'];

		//	$useVoIP = $dataInput['conf']['useVoIP'];
		//	$useIPTv = $dataInput['conf']['useIPTv'];

			$json = json_decode($dataInput['conf']['jsonString']);
			$frame = $json->frame;
			$slot = $json->slot;
			$pon = $json->pon;
			$onu_ont = $json->onuOnt;
			$olt_id = $json->oltId; 
			$fsp = "$frame/$slot/$pon";
			//$oid = rtrim($dataInput['conf']['oid']);
			$ont_type = ($mode === "bridge") ? "HBR" : "HRT";
			$tySQL = ($table==="cliente") ? "login" : "username";

   			$sqlCli = mysqli_query($connection, "SELECT nome, porta_olt, onu_ont, switch, armario_olt, porta_splitter, caixa_herm, $tySQL, senha FROM sis_$table WHERE $tySQL='".$login."'");

		} // END if(!empty($dataInput['conf']))

		if (mysqli_num_rows($sqlCli)>0) {
			$client = mysqli_fetch_object($sqlCli);
			$ppp_login = $client->$tySQL;
			$ppp_pass = $client->senha;

		//	$sip_login = $dataInput['conf']['sipLogin'];
		//	$sip_pass = $dataInput['conf']['sipPass'];
		//	$sip_server = $dataInput['conf']['sipServer'];
		//	$sip_host = $sip_login."@".$sip_server;

			if ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null ) {

				$cmd[0] = "show gpon onu state gpon-olt_$fsp";
				$telnet->DoCommand($cmd, $result);
				$linhasOid = explode("\n", $result);
				for ($h=0; $h<=count($linhasOid); $h++) {
					if (strpos($linhasOid[$h], $fsp) !== FALSE) {
						$dL = explode(" ", $linhasOid[$h]); 
						$dL0 = explode(":", $dL[0]);
						$usedOid = $dL0[1];
      	  				$used[$usedOid] = utf8_encode("Oid em Uso");
					} // END if (strpos($linhasOid[$h], $fsp) !== FALSE)
				}

				for($n=128; $n>=1; $n--){
      	  			if (empty($used[$n])) $oid = $n;
      			} // END for($n=128; $n>=1; $n--)

				if ($mode === "router") {
					$chkVP[0] = "show gpon onu profile vlan $vpData";
					$telnet->DoCommand($chkVP, $result);
					if (preg_match("/%Code 63981/", $result)) {
						$makeVP[0] = "configure terminal";
						$makeVP[1] = "gpon";
						$makeVP[2] = "onu profile vlan $vpData tag-mode tag cvlan $usrVlan";
						$makeVP[3] = "exit";
						$makeVP[4] = "exit";
						$telnet->DoCommand($makeVP, $result);
					}
				}

				$oltVersion = "2";
				$cmd2[0] = "show version-running";
				$telnet->DoCommand($cmd2, $result2);
				$linhasVer = explode("\n", $result2);
				for ($h=0; $h<=count($linhasVer); $h++) {
					if (strpos($linhasVer[$h], "GTGHG      MVR        V1.") !== FALSE) {
						$oltVersion = "1";
					}
				}

					/* ---------------------  INICIO INCLUSAO ONT --------------------- */
					$cmmd[0] = "configure terminal";
					$cmmd[1] = "interface gpon-olt_$fsp";
					$cmmd[2] = "onu $oid type $ont_type sn $onu_ont";
					$cmmd[3] = "exit";
					$cmmd[4] = "interface gpon-onu_$fsp:$oid";
					$cmmd[5] = "name $ppp_login";
					$cmmd[6] = "description from $login_atend by HelpFiber";
					$cmmd[7] = "tcont 1 profile $profileData";
					$cmmd[8] = ($oltVersion==="1") ? "gemport 11 name $profileData unicast tcont 1" : "gemport 11 name $profileData tcont 1";
					$cmmd[9] = "service-port 1 vport 11 user-vlan $usrVlan vlan $vlanData";

					if ($mode === "bridge") {
						$cmmd[10] = "exit";
						$cmmd[11] = "pon-onu-mng gpon-onu_$fsp:$oid";
						$cmmd[12] = "service $profileData gemport 11 vlan $usrVlan";
						$cmmd[13] = "vlan port eth_0/1 mode tag vlan $usrVlan";
						$cmmd[14] = "exit";
					}

					if ($mode === "router") {

					//	if ($useVoIP==="sim") {
					//		$cmmd[10] = "tcont 2 profile $profileVoIP";
					//		$cmmd[11] = "gemport 12 name $profileVoIP tcont 2";
					//		$cmmd[12] = "service-port 2 vport 12 user-vlan $usrVlan vlan $vlanVoIP";
					//	}
					//	if ($useIPTv==="sim") {
					//		$cmmd[13] = "tcont 3 profile $profileIPTv";
					//		$cmmd[14] = "gemport 13 name $profileIPTv tcont 3";
					//		$cmmd[15] = "service-port 3 vport 13 user-vlan $usrVlan vlan $vlanIPTv";
					//		$cmmd[16] = "igmp version v2 vport 13";
					//	}

						$cmmd[10] = "exit";
						$cmmd[11] = "pon-onu-mng gpon-onu_$fsp:$oid";
						$cmmd[12] = "service $profileData gemport 11 vlan $usrVlan";
						$cmmd[13] = "wan 1 service internet";
						$cmmd[14] = "wan-ip mode pppoe username $ppp_login password $ppp_pass vlan-profile $vpData host 1";
						$cmmd[15] = "pppoe 1 connect always nat enable user $ppp_login password $ppp_pass";
						$cmmd[16] = "exit";

					//	if ($useVoIP==="sim") {
					//		$cmmd[21] = "service $profileVoIP gemport 12 vlan $usrVlan";
					//		$cmmd[22] = "voip-ip mode dhcp vlan-profile $vpVoIP host 2";
					//		$cmmd[23] = "voip protocol sip";
					//		$cmmd[24] = "sip-service pots_0/1 profile VOIPMG userid $sip_host username $sip_login password $sip_pass";
					//	}
					//	if ($useIPTv==="sim") {
					//		$cmmd[25] = "vlan port eth_0/4 mode tag vlan $usrVlan";
					//		$cmmd[26] = "dhcp-ip ethuni eth_0/4";
					//		$cmmd[27] = "exit";
					//		$cmmd[28] = "igmp mvlan $vlanIPTv receive-port gpon-onu_$fsp:$oid vport 13";
					//	}
					//	if ($useSsid === 'sim') {
					//		$cmmd[14] = "pon-onu-mng gpon-onu_$fsp:$oid";
					//		$cmmd[15] = "ssid ctrl wifi_0/1 name $ssidName user-isolation disable";
					//		$cmmd[16] = "ssid auth wpa wifi_0/1 wpa2-psk encrypt aes key $ssidPass";
					//		$cmmd[17] = "exit";
					//	}
					}

					$telnet->DoCommand($cmmd, $result);

					if(!preg_match("/Successful/", $result)){
						$err = true;
					} // END if(!str_replace("apply", "", $result))

					/* ---------------------  FIM INCLUSAO ONT --------------------- */

				/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
				if ($err !== true) {
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

				$ret['messages']["msg"] = utf8_encode("ONU '$onu_ont' Habilitada com Sucesso!");
				$ret['messages']["login"] = $login;
				$ret['messages']["url"] = ($table==="adicional") ? "../../adicionais.php?acao=busca&busca=".$login."&campo=sis_adicional.username&enviar=Buscar" : "../../clientes.php?tipo=todos&busca=".$login."&campo=login&ordem=nenhum&enviar=Buscar";

			} // END if ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )
			else {
				$ret['errorMessage']['msg'] = utf8_encode("Login '".$login."' j� possui ONU cadastrada!");
				$ret['errorMessage']['btn'] = "back";
			} // END elseif ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )

		} // END if (mysqli_num_rows($sqlCli)>0)
		else {
			$ret['errorMessage']['msg'] = utf8_encode("Login '".$login."' n�o existe nos clientes ativos!");
			$ret['errorMessage']['btn'] = "back";
		} // END elseif ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null )

	} // END if($dataInput["acao"]==="insont")
	/* ---------------------  FIM ACAO INSERT --------------------- */

	/* ---------------------  INICIO OUTRAS ACOES --------------------- */
	if($dataInput["acao"]==="delOnt" || $dataInput["acao"]==="resOnt" || $dataInput["acao"]==="unWan" || $dataInput["acao"]==="wifiConf") {

		if ($dataInput['onuOnt']) {
			$onu_ont = $dataInput['onuOnt'];
			$table = $dataInput['finTab'];
			$tySQL = ($table==="cliente") ? "login" : "username";
   			$sql = mysql_query("SELECT nome, porta_olt, onu_ont, switch, caixa_herm, porta_splitter, armario_olt, $tySQL FROM sis_$table WHERE onu_ont='$onu_ont'");

   			while ($client = mysql_fetch_object($sql)) {
      			if ($client->porta_olt == null and $client->onu_ont == null and $client->switch == null ) {
        			$ret[info][0]["error"] = "Login N�O possui ONU cadastrada!";
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
				$cmmd[0] = "configure terminal";
				$cmmd[1] = "interface gpon-olt_$fsp";
				$cmmd[2] = "no onu $oid";
				$cmmd[3] = "exit";
				$cmmd[4] = "exit";
			} // END if ($dataInput["acao"]==="delOnt") 
			/* ---------------------  FIM EXCLUSAO ONT --------------------- */
			/* ---------------------  INICIO REBOOT ONT --------------------- */
			if ($dataInput["acao"]==="resOnt") {
				$cmmd[0] = "configure terminal";
				$cmmd[1] = "pon-onu-mng gpon-onu_$fsp:$oid";
				$cmmd[2] = "reboot";
				$cmmd[3] = "yes";
			} // END if ($dataInput["acao"]==="resOnt")
			/* ---------------------  FIM REBOOT ONT --------------------- */

			$telnet->DoCommand($cmmd, $result);

		/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
		if($err !== true) {
			$reg_data = date("d/m/Y H:i:s");

			if ($dataInput["acao"]==="delOnt") {
        		$sqlUpCli = mysql_query("UPDATE sis_$table SET porta_olt = null, armario_olt = null, switch = null, onu_ont = null, caixa_herm = null, porta_splitter = null, interface = null where $tySQL = '$login'");
				$reg_admin = "alterou dados do cliente: ".$nome." <b>removido: ONU/ONT</b> ".$onu_ont." - IP: $ip_add";
				$reg_central = "$login_atend alterou dados do cliente: <b>removido: ONU/ONT</b> ".$onu_ont." - IP: $ip_add";
				$sqlInAdm = mysql_query("INSERT INTO sis_logs (registro, data, login, operacao) VALUES ('".$reg_admin."', '".$reg_data."', '".$login_atend."', '690498EE')");
				$sqlInUsr = mysql_query("INSERT INTO sis_logs (registro, data, login, tipo, operacao) VALUES ('".$reg_central."', '".$reg_data."', '".$login."', 'central', '690498EE')");
        		$ret['info'][0]["title"] = utf8_encode("EXCLUS�O");
        		$ret[messages]["msg"] = "ONT $onu_ont EXCLUIDA COM SUCESSO!!!";
			} // END if ($dataInput["acao"]==="delOnt")

			if ($dataInput["acao"]==="resOnt") {
        		$ret[info][0]["title"] = "REBOOT";
        		$ret[messages]["msg"] = "ONT $onu_ont REINICIADA COM SUCESSO !!!";
			} // END if ($dataInput["acao"]==="resOnt")

			if ($dataInput["acao"]==="unWan") {
        		$ret[info][0]["title"] = "ACESSO REMOTO";
        		$ret[messages]["msg"] = "ACESSO WAN PARA ONT $onu_ont LIBERADO COM SUCESSO !!!";
			} // END if ($dataInput["acao"]==="unWan") 

			if ($dataInput["acao"]==="wifiConf") {
        		$ret[info][0]["title"] = utf8_encode("CONFIGURA��O WI-FI");
        		$ret[messages]["msg"] = "SSID E SENHA DA ONT ".$dataInput['conf']['ontSn']." ALTERADOS COM SUCESSO !!!";
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
				$ret[errorMessage][msg] = utf8_encode("FALHA NA COMUNICA��O COM A OLT!");
				$ret[errorMessage][btn] = "back";
				$err=true;
		break;
		case 2:
				$ret[errorMessage][msg] = utf8_encode("FALHA NA COMUNICA��O COM HOST!");
				$ret[errorMessage][btn] = "back";
				$err=true;
		break;
		case 3:
				$ret[errorMessage][msg] = utf8_encode("FALHA LOGIN!");
				$ret[errorMessage][btn] = "back";
				$err=true;
		break;
		case 4:
				$ret[errorMessage][msg] = utf8_encode("FALHA PHP!");
				$ret[errorMessage][btn] = "back";
				$err=true;
		break;
		case 5:
				$ret[errorMessage][msg] = utf8_encode("OPS! REGISTRE O ADDON PARA CONTINUAR USANDO!");
				$ret[errorMessage][btn] = "reg";
		break;
	}