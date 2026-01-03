
<?php
if ($_SERVER["SCRIPT_FILENAME"] === __FILE__) { print 'Acesso negado...'; header('Refresh:5; url=/admin', TRUE, 302); exit(); }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/core/database/database.php") !== "c8d38e0556d70fa9bff6bf19188f4e17") { header('HTTP/1.1 500'); exit; }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/controllers/controller.php") !== "47331342647c55cf27643765fe804878") { header('HTTP/1.1 500'); exit; }
if (hash_file('md5', "/opt/mk-auth/admin/addons/helpfiber/core/constants/Net/SSH2.php") !== "240b848b1b25503c7288bf28a166727a") { header('HTTP/1.1 500'); exit; }

		include "../core/constants/Net/SSH2.php";

	$msg = "";
	$resultado = "";
	$err = false;
	$ret['info'][0]["oltName"] = $olt->name;
	$ret['info'][0]["oltMaker"] = $olt->maker;
	$ip_add = $_SERVER["REMOTE_ADDR"];

	/* ---------------------  INICIO CONEXAO OLT --------------------- */
	if (isset($regulate) && $dataInput["cmd"] === "autofind" && !in_array($olt->maker,$freeMaker)) {
		$ret['errorMessage']["msg"] = utf8_encode("OPS! REGISTRE O ADDON PARA CONTINUAR USANDO!");
		$ret['errorMessage']["btn"] = "reg";
 	}
	else {
		$ssh = new Net_SSH2($olt->domain);
		if (!$ssh->login($olt->username, $olt->password)) {
			$ret['errorMessage']["msg"] = utf8_encode("Falha na comunica��o com OLT!");
			$ret['errorMessage']["btn"] = "back";
		}
		$ssh->read('username@username:~$');

	/* ---------------------  FIM CONEXAO OLT --------------------- */
 
	/* --------------------- INICIO BUSCA ONU --------------------- */
	if (isset($dataInput["find"]) && $dataInput["find"]==="finont") {

		/* --------------------- INICIO AUTOFIND ONU --------------------- */
		if($dataInput["cmd"] === "autofind"){
			$ret['info'][0]["cmd"] = $dataInput["cmd"];

				/* ---------------------  INICIO CONSULTA OLT --------------------- */
        		$ssh->write("onu show\n");
				$result = $ssh->read('username@username:~$');

				$linhas = explode("\n", $result);
				$s = 0;
				for($h=0; $h<=count($linhas)-1; $h++){
					if(strpos($linhas[$h], "Free slots in GPON") !== FALSE){
						$pon = preg_replace("/[^0-9]/", "", $linhas[$h]);
					}
					if(strpos($linhas[$h], "Discovered serial") !== FALSE){

						$j=1;
						do {
							if (!preg_match("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", $linhas[$h+2+$j])) {
								$retorno = str_replace ( "   ", " ", $linhas[$h+2+$j]);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$retorno = str_replace ( "  ", " ", $retorno);
								$r = explode(" ",$retorno);
							$ret["retorno"][$s]["modelo"] = rtrim($r[3]);
							$ret["retorno"][$s]["serial"] = $r[1].$r[2];
							$ret["retorno"][$s]["fsp"] = "1/1/".$pon;
							$ret["retorno"][$s]["frame"] = 1;
							$ret["retorno"][$s]["slot"] = 1;
							$ret["retorno"][$s]["pon"] = $pon;
							$ret["retorno"][$s]["oid"] = $r[0];

							include_once "../core/utils/utils.php";
							$vlan = makeVlan("1",$pon,$olt->id);

							$ret["retorno"][$s]["json_string"] = json_encode(array(
										"portaOlt" => "1/1/".$pon,
										"frame" => 1,
										"slot" => 1,
										"pon" => $pon,
										"onuOnt" => $r[1].$r[2],
										"oltId" => $olt_id,
										"ontType" => rtrim($r[3]),
										"vlan" => $vlan,
										"oid" => $r[0]
										));
							$s++;
							}
							$j++;
						} while (!preg_match("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", $linhas[$h+2+$j]));
					}
				}
				if ($s<1) {
					$ret['errorMessage']["msg"] = utf8_encode("NENHUMA ONU ENCONTRADA!");
					$ret['errorMessage']["btn"] = "back";
				}
				else {
					$ret['info'][0]["qtd"] = $s;
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
			$sql = mysqli_query($connection, "SELECT accesslist,mac,porta_olt,switch,onu_ont,$tySQL,nome FROM sis_$table WHERE onu_ont='$onu_ont'");
 		}
		if(mysqli_num_rows($sql) > 0){
			$client = mysqli_fetch_object($sql);
			$fsp = rtrim($client->porta_olt);
			$fspEx = explode("/",$fsp);
			$pon = $fspEx[2];
			$switch = explode(";",$client->switch);
			$oid = $switch[0];

				/* ---------------------  INICIO CONSULTA SINAL ---------------------  */

				$ssh->write("onu status gpon $pon onu $oid\n");

				$result = $ssh->read('username@username:~$');

				$linhas = explode("\n", $result);
				for($h=0; $h<=count($linhas)-1; $h++){
					if(strpos($linhas[$h], "====================") !== FALSE){
							$retorno = str_replace ( "   ", " ", $linhas[$h+1]);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$retorno = str_replace ( "  ", " ", $retorno);
							$tx_rx = explode(" ", $retorno); 
					}
				}
				/* ---------------------  FIM CONSULTA SINAL ---------------------  */

			$tx=0;$rx=0;
			if(isset($tx_rx)) {
				$tx += $tx_rx[4];
				$rx += $tx_rx[6];
			}

			$ret['retorno'][0]["tx_op"] = str_replace("\r", '', $tx);
			$ret['retorno'][0]["rx_op"] = str_replace("\r", '', $rx);
			$ret['retorno'][0]["olt_id"] = str_replace("\r", '', $olt_id);

			$ret['retorno'][0]["fsp"] = str_replace("\r", '', $fsp);
			$ret['retorno'][0]["onu_ont"] = str_replace("\r", '', $onu_ont);
			$ret['retorno'][0]["oid"] = str_replace("\r", '', $oid);
			$ret['retorno'][0]["login"] = $client->$tySQL;
			$ret['retorno'][0]["url"] = ($table==="cliente") ? "../../clientes.php?tipo=todos&busca=".$client->$tySQL."&campo=login&ordem=nenhum&enviar=Buscar" : "../../adicionais.php?acao=busca&busca=".$client->$tySQL."&campo=sis_adicional.username&enviar=Buscar";

			$ret['retorno'][0]["rx"] = $rx;

			$radQuery = mysqli_query($connection, "SELECT callingstationid FROM radacct WHERE username='".$client->$tySQL."' && acctstoptime is NULL LIMIT 1");
			$callingstationid = (mysqli_num_rows($radQuery) > 0) ? mysqli_fetch_object($radQuery)->callingstationid : NULL;
			$mac = ($client->mac != NULL) ? $client->mac : $callingstationid;

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
	if(isset($dataInput["acao"]) && $dataInput["acao"]==="insont") {

		if(!empty($dataInput['conf'])){
			$login = $dataInput['conf']['login'];
			$mode = $dataInput['conf']['mode'];
			$oltMaker = $dataInput['conf']['oltMaker'];
			$ctoName = $dataInput['conf']['ctoName'];
			$ctoPort = (is_numeric($dataInput['conf']['ctoPort'])) ? $dataInput['conf']['ctoPort']:null;

			$srvVlan = $dataInput['conf']['srvVlan'];

			$json = json_decode($dataInput['conf']['jsonString']);
			$frame = $json->frame;
			$slot = $json->slot;
			$pon = $json->pon;
			$onu_ont = $json->onuOnt;
			$olt_id = $json->oltId; 
			$ont_type = $json->ontType;

			$gpon = "$frame/$slot";
			$fsp = "$frame/$slot/$pon";

			$sernoID = (isset($dataInput['conf']['oid'])) ? rtrim($dataInput['conf']['oid']) : "";
			$onuProfile = (isset($dataInput['conf']['onuProfile'])) ? $dataInput['conf']['onuProfile'] : "";

			$wan = ($mode==="router") ? $mode : "eth 1";
			$brType = "downlink";
			$brPatchModify = (isset($dataInput['conf']['brPatchModify'])) ? $dataInput['conf']['brPatchModify'] : "";
			$brPatch = (isset($dataInput['conf']['brPatch'])) ? $dataInput['conf']['brPatch'] : "";
			$vlanMode = "tagged";
			$desc = "to_$login_by_HelpFiber";

			$table = $dataInput['conf']['finTab'];
			$tySQL = ($table==="cliente") ? "login" : "username";
   			$sqlCli = mysqli_query($connection, "SELECT nome, porta_olt, onu_ont, switch, armario_olt, porta_splitter, caixa_herm, $tySQL, senha FROM sis_$table WHERE $tySQL='".$login."'");

		} // END if(!empty($dataInput['conf']))

		if (mysqli_num_rows($sqlCli)>0) {
			$client = mysqli_fetch_object($sqlCli);
			$ppp_login = $client->$tySQL;
			$ppp_pass = $client->senha;

			if ($client->porta_olt == null && $client->onu_ont == null && $client->switch == null ) {

					/* ---------------------  INICIO INCLUSAO ONT --------------------- */

         		$ssh->write("onu show gpon $pon\n");
				$result = $ssh->read('username@username:~$');
				$linhas = explode("\n",$result);
				for($h=0; $h<=count($linhas)-1; $h++){
					if(strpos($linhas[$h], "Free slots in GPON") !== FALSE){
						$l = explode(" ", trim($linhas[$h+2]));
						$oid = trim($l[0]);
					}
				}
				
				if ($oid) {
         			// ed. 2020-09-01 if ( substr($onu_ont, 0, 4) === "SU10" ) { $ssh->write("onu set gpon $pon onu $oid id $sernoID meprof $onuProfile\n"); }
					if ( preg_match("/[0-9]/", substr($onu_ont, 0, 4)) ) { $ssh->write("onu set gpon $pon onu $oid id $sernoID meprof $onuProfile\n"); }
					else { $ssh->write("onu set gpon $pon onu $oid serial-number $onu_ont meprof $onuProfile\n"); }
					Sleep(1);
         			$ssh->write("onu description add gpon $pon onu $oid text $desc\n");
					Sleep(1);
         			$ssh->write("bridge add gpon $pon onu $oid $brType vlan $srvVlan $vlanMode $wan\n");
					Sleep(1);
					if ($brPatchModify==="sim") {
						$p1 = "gpon $pon onu $oid gem";
						$p2 = "Mode: blockAuto Time: 300s";
         				$ssh->write("bridge-path show\n");
						$resultGem = $ssh->read('username@username:~$');
						$linhasGem = explode("\n",$resultGem);
						for($h=0; $h<=count($linhasGem)-1; $h++){
							if(strpos($linhasGem[$h], $p1) !== FALSE){
								$l = explode("_", trim(preg_replace("/[^0-9_]/", "", str_replace($p2, "", str_replace($p1, "_", $linhasGem[$h])))));
								$gemport = $l[1];
							}
						}
						Sleep(1);
         				$ssh->write("bridge-path modify gpon $pon onu $oid gem $gemport mode $brPatch\n");
						Sleep(1);
					}
            		$result=$ssh->read('username@username:~$');
				}
				else { $err=true; }
					/* ---------------------  FIM INCLUSAO ONT --------------------- */

				/* ---------------------  INICIO UPDATE MK-AUTH --------------------- */
				if ($err !== true) {
					$oidModel = "$oid;$ont_type";
        			$sqlCliUp = mysqli_query($connection, "UPDATE sis_$table SET porta_splitter = '" . $ctoPort . "', caixa_herm = '" . $ctoName . "', armario_olt = '" . $olt->name . "', porta_olt = '" . $fsp . "', switch = '" . $oidModel . "', onu_ont = '" . $onu_ont . "', interface = 'vlan" . $srvVlan . "', accesslist = 'sim' where $tySQL = '" . $login . "'");

					$reg_data = date("d/m/Y H:i:s");
					$nome = $client->nome;

					$reg_admin = "alterou dados do cliente: $nome <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";
					$reg_central = "$login_atend alterou dados do cliente: <b>registrou: ONU/ONT</b> $onu_ont (<b>$mode</b>) - IP: $ip_add";

					$sqlInAdm = mysqli_query($connection, "INSERT INTO sis_logs (registro, data, login, operacao) VALUES ( '".$reg_admin."', '".$reg_data."', '".$login_atend."', '690498EE')");
					$sqlInUsr = mysqli_query($connection, "INSERT INTO sis_logs (registro, data, login, tipo, operacao) VALUES ( '".$reg_central."', '".$reg_data."', '".$login."', 'central', '690498EE')");
				} // END if (!err)
				/* ---------------------  FIM UPDATE MK-AUTH --------------------- */

				$ret['messages']["msg"] = "ONU '$onu_ont' Habilitada com Sucesso!";
				$ret['messages']["login"] = $login;
				$ret['messages']["url"] = ($table==="adicional") ? "../../adicionais.php?acao=busca&busca=".$login."&campo=sis_adicional.username&enviar=Buscar" : "../../clientes.php?tipo=todos&busca=".$login."&campo=login&ordem=nenhum&enviar=Buscar";


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
   			$sql = mysqli_query($connection, "SELECT nome, porta_olt, onu_ont, switch, caixa_herm, porta_splitter, armario_olt, $tySQL FROM sis_$table WHERE onu_ont='$onu_ont'");

   			$client = mysqli_fetch_object($sql);
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

        	$ssh->write("enable\n config\n");
			/* ---------------------  INICIO EXCLUSAO ONT --------------------- */
			if ($dataInput["acao"]==="delOnt") {
         		$ssh->write("onu delete gpon $pon onu $oid\n");
				Sleep(1);
         		$ssh->write("yes\n");
         		$ssh->write("no\n");
         		$ssh->write("yes\n");
         		//$ssh->write("bridge delete gpon $pon onu $oid\n");
				Sleep(1);
			} // END if ($dataInput["acao"]==="delOnt") 
			/* ---------------------  FIM EXCLUSAO ONT --------------------- */
			/* ---------------------  INICIO REBOOT ONT --------------------- */
			if ($dataInput["acao"]==="resOnt") {
       	  		$ssh->write("onu reboot gpon $pon onu $oid \n");
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
        		$sqlUpCli = mysqli_query($connection, "UPDATE sis_$table SET porta_olt = null, armario_olt = null, switch = null, onu_ont = null, caixa_herm = null, porta_splitter = null, interface = null where $tySQL = '$login'");
				$reg_admin = "alterou dados do cliente: ".$nome." <b>removido: ONU/ONT</b> ".$onu_ont." - IP: $ip_add";
				$reg_central = "$login_atend alterou dados do cliente: <b>removido: ONU/ONT</b> ".$onu_ont." - IP: $ip_add";
				$sqlInAdm = mysqli_query($connection, "INSERT INTO sis_logs (registro, data, login, operacao) VALUES ('".$reg_admin."', '".$reg_data."', '".$login_atend."', '690498EE')");
				$sqlInUsr = mysqli_query($connection, "INSERT INTO sis_logs (registro, data, login, tipo, operacao) VALUES ('".$reg_central."', '".$reg_data."', '".$login."', 'central', '690498EE')");
        		$ret['info'][0]["title"] = utf8_encode("EXCLUS�O");
        		$ret['messages']["msg"] = "ONT $onu_ont EXCLUIDA COM SUCESSO!!!";
			} // END if ($dataInput["acao"]==="delOnt")

			if ($dataInput["acao"]==="resOnt") {
        		$ret['info'][0]["title"] = "REBOOT";
        		$ret['messages']["msg"] = "ONT $onu_ont REINICIADA COM SUCESSO !!!";
			} // END if ($dataInput["acao"]==="resOnt")

			if ($dataInput["acao"]==="unWan") {
        		$ret['info'][0]["title"] = "ACESSO REMOTO";
        		$ret['messages']["msg"] = "ACESSO WAN PARA ONT $onu_ont LIBERADO COM SUCESSO !!!";
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