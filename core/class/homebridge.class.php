<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class homebridge extends eqLogic {
	/*     * *************************Attributs****************************** */

	//private static $_PLUGIN_COMPATIBILITY = array('openzwave', 'rfxcom', 'edisio', 'ipx800', 'mySensors', 'Zibasedom', 'virtual', 'camera','apcupsd', 'btsniffer', 'dsc', 'h801', 'rflink', 'mysensors', 'relaynet', 'remora', 'unipi', 'playbulb', 'doorbird','netatmoThermostat');

	/*     * ***********************Methode static*************************** */

	public static function Pluginsuported() {
		$Pluginsuported = ['openzwave','rfxcom','edisio','mpower', 'mySensors', 'Zibasedom', 'virtual', 'camera','weather','philipsHue','enocean','wifipower','alarm','mode','apcupsd', 'btsniffer','dsc','rflink','mysensors','relaynet','remora','unipi','eibd','thermostat','netatmoThermostat','espeasy','jeelink','teleinfo','tahoma','protexiom','lifx','wattlet','rfplayer','openenocean'];
		return $Pluginsuported;
	}
	
	public static function PluginWidget() {
		$PluginWidget = ['alarm','camera','thermostat','netatmoThermostat','weather','mode'];	
		return $PluginWidget;
	}
	
	public static function PluginMultiInEqLogic(){
		$PluginMulti = ['LIGHT_STATE','ENERGY_STATE','FLAP_STATE','HEATING_STATE','SIREN_STATE','LOCK_STATE'];
		return $PluginMulti;
	}
	
	public static function LienAWS() {
		return 'http://195.154.56.168:8000/notif/';
	}
	
	public static function DisallowedPIN() {
		$DisallowedPIN = ['000-00-000','111-11-111','222-22-222','333-33-333','444-44-444','555-55-555','666-66-666','777-77-777','888-88-888','999-99-999','123-45-678','876-54-321'];
		return $DisallowedPIN;
	}
	
	public static function PluginToSend() {
		$PluginToSend=[];
		$plugins = plugin::listPlugin(true);
		$plugin_compatible = homebridge::Pluginsuported();
		$plugin_widget = homebridge::PluginWidget();
		foreach ($plugins as $plugin){
			$plugId = $plugin->getId();
			if ($plugId == 'homebridge') {
				continue;
			} else if (in_array($plugId,$plugin_widget)) {
				array_push($PluginToSend, $plugId);
			} else if (in_array($plugId,$plugin_compatible) && !in_array($plugId,$plugin_widget) && config::byKey('sendToApp', $plugId, 1) == 1){
				array_push($PluginToSend, $plugId);
			} else if (!in_array($plugId,$plugin_compatible) && config::byKey('sendToApp', $plugId, 0) == 1){
				$subClasses = config::byKey('subClass', $plugId, '');
				if ($subClasses != ''){
					$subClassesList = explode(';',$subClasses);
					foreach ($subClassesList as $subClass){
						array_push($PluginToSend, $subClass);
					}
				}
				array_push($PluginToSend, $plugId);
			} else {
				continue;
			}
		}
		return $PluginToSend;
		
	}

	/**************************************************************************************/
	/*                                                                                    */
	/*                        Permet d'installer les dépendances                          */
	/*                                                                                    */
	/**************************************************************************************/
	public static function check_ios() {
		$ios = 0;
		foreach (eqLogic::byType('homebridge') as $homebridge){
			if($homebridge->getConfiguration('type_homebridge') == "ios"){
				$ios = 1;
			}
		}
		return $ios;
	}
	
	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'homebridge_update';
		//$return['progress_file'] = '/tmp/homebridge_in_progress';
		$return['progress_file'] = jeedom::getTmpFolder('homebridge') . '/dependance';
		$state = '';
		if(self::check_ios() == 0){
			$state = 'ok';
		}
		else {
			if (shell_exec('ls /usr/bin/homebridge 2>/dev/null | wc -l') == 1 || shell_exec('ls /usr/local/bin/homebridge 2>/dev/null | wc -l') == 1) {
				$state = 'ok';
			}else{
				$state = 'nok';
			}	
		}
		$return['state'] = $state;
		return $return;
	}
	
	public static function dependancy_install($fromRepair = false) {
		if (file_exists(jeedom::getTmpFolder('homebridge') . '/dependance')) {
		    return;
		}
		if(self::check_ios() == 0){
		    config::save('deamonAutoMode',0,'homebridge');
		    return;
		}
		log::remove('homebridge_update');
		self::generate_file();
		
        $returnArray = array('script' => dirname(__FILE__) . '/../../resources/install_homebridge.sh '.network::getNetworkAccess('internal','ip'),
							 'log' => log::getPathToLog(__CLASS__ . '_homebridge_update'));
							 
        if($fromRepair) {
			$cmd = 'sudo /bin/bash ' . $returnArray['script'];
			$cmd .= ' >> ' . $returnArray['log'] . ' 2>&1 &';
			exec($cmd);
		}
		return $returnArray;
	}
	public static function getJSON(){
		exec('sudo chown -R www-data:www-data ' . dirname(__FILE__) . '/../../data');
		exec('sudo chmod -R 775 ' . dirname(__FILE__) . '/../../data');
		exec('touch ' . dirname(__FILE__) . '/../../data/otherPlatform.json');
		exec('sudo chown -R www-data:www-data ' . dirname(__FILE__) . '/../../data');
		exec('sudo chmod -R 775 ' . dirname(__FILE__) . '/../../data');
		return file_get_contents(dirname(__FILE__) . '/../../data/otherPlatform.json');
	}
	public static function saveJSON($file){
		exec('sudo chown -R www-data:www-data ' . dirname(__FILE__) . '/../../data');
		exec('sudo chmod -R 775 ' . dirname(__FILE__) . '/../../data');
		$ret = file_put_contents(dirname(__FILE__) . '/../../data/otherPlatform.json',$file);
		return (($ret===false)?false:true);
	}
	public static function generate_file(){
		if(self::deamon_info()=="ok") self::deamon_stop();
		$user_homebridge = config::byKey('user_homebridge','homebridge',1,true);
		config::save('user_homebridge',$user_homebridge,'homebridge');
		$user = user::byId($user_homebridge);
		if(is_object($user)){
			$apikey = $user->getHash();
		}else{
			$apikey = config::byKey('api');
		}
		
		$pin_homebridge = config::byKey('pin_homebridge','homebridge','031-45-154',true);
		config::save('pin_homebridge',$pin_homebridge,'homebridge');
		$name_homebridge = config::byKey('name_homebridge','homebridge',config::byKey('name'),true);
		config::save('name_homebridge',$name_homebridge,'homebridge');
		$mac_homebridge = config::byKey('mac_homebridge','homebridge',self::generateRandomMac(),true);
		config::save('mac_homebridge',$mac_homebridge,'homebridge');
		
		if(in_array($pin_homebridge,self::DisallowedPIN())) {
			log::add('homebridge', 'error', 'Le PIN Homebridge n\'est pas autorisée par Apple : '.$pin_homebridge);	
		}
		
		$response = array();
		$response['bridge'] = array();
		$response['bridge']['name'] = $name_homebridge;
		$response['bridge']['username'] = $mac_homebridge;
		$response['bridge']['port'] = 51826;
		$response['bridge']['pin'] = $pin_homebridge;
		
		$response['bridge']['manufacturer'] = "Jeedom";
		$response['bridge']['model'] = "Homebridge";
		$response['bridge']['serialNumber'] = $mac_homebridge;

		
		$response['description'] = "Autogenerated config file by Jeedom";
		
		$plateform['platform'] = "Jeedom";
		$plateform['name'] = "Jeedom";
		$plateform['url'] = network::getNetworkAccess('internal');
		$plateform['apikey'] = $apikey;
		$plateform['pollerperiod'] = 0.5;
		$plateform['debugLevel'] = log::getLogLevel('homebridge');
		$response['platforms'] = array();
		$response['platforms'][] = $plateform;

		// get file and add it if it's valid
		exec('sudo chown -R www-data:www-data ' . dirname(__FILE__) . '/../../data');
		exec('sudo chmod -R 775 ' . dirname(__FILE__) . '/../../data');
		exec('touch ' . dirname(__FILE__) . '/../../data/otherPlatform.json');
		exec('sudo chown -R www-data:www-data ' . dirname(__FILE__) . '/../../data');
		exec('sudo chmod -R 775 ' . dirname(__FILE__) . '/../../data');
		$jsonFile = file_get_contents(dirname(__FILE__) . '/../../data/otherPlatform.json');
		$jsonPlatforms = explode('|',$jsonFile);
		if(!$jsonPlatforms)
			$jsonPlatforms = array($jsonFile);
		foreach ($jsonPlatforms as $jsonPlatform) {
			$jsonArr = json_decode($jsonPlatform);
			if($jsonArr !== null)
				$response['platforms'][] = $jsonArr;
		}
		
		exec('sudo chown -R www-data:www-data ' . dirname(__FILE__) . '/../../resources');
		$fp = fopen(dirname(__FILE__) . '/../../resources/homebridge/config.json', 'w');
		fwrite($fp, json_encode($response));
		fclose($fp);
	}
	
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'homebridge';
		$return['state'] = 'nok';
		if(self::check_ios() == 0){
			$return['state'] = 'ok';
			$return['launchable'] = 'ok';
			return $return;
		}
		$result = exec("ps -eo pid,command | grep ' homebridge' | grep -v grep | awk '{print $1}'");
		if ($result <> 0) {
            $return['state'] = 'ok';
        }
		$return['launchable'] = 'ok';
		return $return;
	}
	public static function deamon_start($_debug = false) {
		if(log::getLogLevel('homebridge')==100) $_debug=true;
		log::add('homebridge', 'info', 'Mode debug : ' . $_debug);
		self::deamon_stop();
		self::generate_file();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			if(self::check_ios() == 0){
				return false;
			}else{
				throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
			}
		}else{
			if(self::check_ios() == 0){
				return false;
			}
		}

		// check avahi-daemon started, if not, start
		$cmd = 'if [ $(ps -ef | grep -v grep | grep "avahi-daemon" | wc -l) -eq 0 ]; then sudo systemctl start avahi-daemon;echo "Démarrage avahi-daemon";sleep 1; fi';
		exec($cmd . ' >> ' . log::getPathToLog('homebridge') . ' 2>&1 &');
		
		$cmd = 'export AVAHI_COMPAT_NOWARN=1;'. (($_debug) ? 'DEBUG=* ':'') .'homebridge '. (($_debug) ? '-D ':'') .'-U '.dirname(__FILE__) . '/../../resources/homebridge';
		exec($cmd . ' >> ' . log::getPathToLog('homebridge') . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('homebridge', 'error', 'Impossible de lancer le démon homebridge, relancer le démon en debug et vérifiez la log', 'unableStartDeamon');
			return false;
		}
		message::removeAll('homebridge', 'unableStartDeamon');
		log::add('homebridge', 'info', 'Démon homebridge lancé');
		
		// Check if multiple IP's -> warning because could cause problems with mdns https://github.com/nfarina/homebridge/issues/1351
		$cmd = 'if [ $(sudo ip addr | grep "inet " | grep -v " tun" | grep -v " lo" | wc -l) -gt 1 ]; then echo "WARNING : Vous avez plusieurs IP de configurées, cela peut poser problème avec Homebridge et mDNS"; fi';
		exec($cmd . ' >> ' . log::getPathToLog('homebridge') . ' 2>&1 &');
		return true;
	}
	public static function deamon_stop() {
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] <> 'ok') {
            return true;
        }
        
		$pid = exec("ps -eo pid,command | grep ' homebridge' | grep -v grep | awk '{print $1}'");
        if ($pid) {
            system::kill($pid);
        }
        system::kill('homebridge');
		system::fuserk(51826);
		//exec('sudo killall homebridge');		
		
        $check = self::deamon_info();
        $retry = 0;
        while ($deamon_info['state'] == 'ok') {
           $retry++;
            if ($retry > 10) {
                return;
            } else {
                sleep(1);
            }
        }
        return self::deamon_info();
	}
	
	/**************************************************************************************/
	/*                                                                                    */
	/*            Permet de supprimer le cache Homebridge            		      */
	/*                                                                                    */
	/**************************************************************************************/
	/*
	public static function eraseHomebridgeCache() {
		self::deamon_stop();
		$cmd = 'sudo rm -Rf '.dirname(__FILE__) . '/../../resources/homebridge/accessories';
		exec($cmd);
		$cmd = 'sudo rm -Rf '.dirname(__FILE__) . '/../../resources/homebridge/persist';
		exec($cmd);
		self::deamon_start();
	}
	*/
	/**************************************************************************************/
	/*                                                                                    */
	/*            Permet de supprimer tout Homebridge                		      */
	/*                                                                                    */
	/**************************************************************************************/
	
	public static function repairHomebridge($reinstall=true) {
		log::add('homebridge', 'info', 'Procedure de réparation');
		homebridge::deamon_stop();
		log::add('homebridge', 'info', 'suppression des accessoires et du persist');
		$cmd = 'sudo rm -Rf '.dirname(__FILE__) . '/../../resources/homebridge/accessories';
		exec($cmd);
		$cmd = 'sudo rm -Rf '.dirname(__FILE__) . '/../../resources/homebridge/persist';
		exec($cmd);
		if($reinstall) {
			log::add('homebridge', 'info', 'suppression homebridge-jeedom');
			$cmd = 'npm uninstall homebridge-jeedom --save';
			exec($cmd);
			log::add('homebridge', 'info', 'suppression homebridge');
			$cmd = 'npm uninstall homebridge --save';
			exec($cmd);
		}
		$mac_homebridge = self::generateRandomMac();
		log::add('homebridge', 'info', 'création d\'une nouvelle MAC adress : '.$mac_homebridge);
		config::save('mac_homebridge',$mac_homebridge,'homebridge');
		$name_homebridge = config::byKey('name').'_Repaired_'.base_convert(mt_rand(0,255),10,16);
		config::save('name_homebridge',$name_homebridge,'homebridge');
		if($reinstall) {
			log::add('homebridge', 'info', 'réinstallation des dependances');
			homebridge::dependancy_install(true);
		}
		
		exec('sudo systemctl restart avahi-daemon');
		sleep(1);
		homebridge::deamon_start();
		$return['mac_homebridge']=$mac_homebridge;
		$return['name_homebridge']=$name_homebridge;
		return $return;
	}

	public static function generateRandomMac() {
		return strtoupper(implode(':', str_split(substr(md5(mt_rand()), 0, 12), 2)));
	}
	
	/**************************************************************************************/
	/*                                                                                    */
	/*            Permet de decouvrir tout les modules de la Jeedom compatible            */
	/*                                                                                    */
	/**************************************************************************************/

	public static function discovery_eqLogic($plugin = array(),$hash = null){
		$return = array();
		foreach ($plugin as $plugin_type) {
			$eqLogics = eqLogic::byType($plugin_type, true);
			if (is_array($eqLogics)) {
				foreach ($eqLogics as $eqLogic) {
					if($eqLogic->getObject_id() !== null && object::byId($eqLogic->getObject_id())->getDisplay('sendToApp', 1) == 1 && $eqLogic->getIsEnable() == 1 && ($eqLogic->getIsVisible() == 1 || in_array($eqLogic->getEqType_name(), self::PluginWidget()))){
						$eqLogic_array = utils::o2a($eqLogic);
						if(isset($eqLogic_array["configuration"]["sendToHomebridge"])){
							$eqLogic_array["sendToHomebridge"] = $eqLogic_array["configuration"]["sendToHomebridge"];
						}
						unset($eqLogic_array['eqReal_id'],$eqLogic_array['configuration'], $eqLogic_array['specificCapatibilities'],$eqLogic_array['timeout'],$eqLogic_array['category'],$eqLogic_array['display']);
						$return[] = $eqLogic_array;
					}
				}
			}
		}
		return $return;
	}
	
	public static function discovery_cmd($plugin = array()){
		$return = array();
		$genericisvisible = array();
		foreach (jeedom::getConfiguration('cmd::generic_type') as $key => $info) {
		        if ($info['family'] !== 'Generic') {
		            array_push($genericisvisible, $key);
		        }
		}
		foreach ($plugin as $plugin_type) {
			$eqLogics = eqLogic::byType($plugin_type, true);
			if (is_array($eqLogics)) {
				foreach ($eqLogics as $eqLogic) {
                  	$i = 0;
                  if($eqLogic->getObject_id() !== null && object::byId($eqLogic->getObject_id())->getDisplay('sendToApp', 1) == 1 && $eqLogic->getIsEnable() == 1 && ($eqLogic->getIsVisible() == 1 || in_array($eqLogic->getEqType_name(), self::PluginWidget()))){
					foreach ($eqLogic->getCmd() as $cmd) {
                    	if($cmd->getDisplay('generic_type') != null && !in_array($cmd->getDisplay('generic_type'),['GENERIC_ERROR','DONT']) && ($cmd->getIsVisible() == 1 || in_array($cmd->getDisplay('generic_type'), $genericisvisible) || in_array($eqLogic->getEqType_name(), self::PluginWidget()))){
                      		$cmd_array = $cmd->exportApi();
                      					
							//Variables
							$maxValue = null;
							$minValue = null;
							$actionCodeAccess = null;
							$actionConfirm = null;
							$generic_type = null;
							$icon = null;
							$invertBinary = null;
							$title_disable = null;
							$title_placeholder = null;
							$message_placeholder = null;
								
							if(isset($cmd_array['configuration'])){
								$configuration = $cmd_array['configuration'];
								if(isset($configuration['maxValue'])){
									$maxValue = $configuration['maxValue'];
								}
								if(isset($configuration['minValue'])){
									$minValue = $configuration['minValue'];
								}
								if(isset($configuration['actionCodeAccess'])){
									$actionCodeAccess = $configuration['actionCodeAccess'];
								}
								if(isset($configuration['actionConfirm'])){
									$actionConfirm = $configuration['actionConfirm'];
								}
							}
							if(isset($cmd_array['display'])){
								$display = $cmd_array['display'];
								if(isset($display['generic_type'])){
									$generic_type = $display['generic_type'];
								}
								if(isset($display['icon'])){
									$icon = $display['icon'];
								}
								if(isset($display['invertBinary'])){
									$invertBinary = $display['invertBinary'];
								}
								if(isset($display['title_disable'])){
									$title_disable = $display['title_disable'];
								}
								if(isset($display['title_placeholder'])){
									$title_placeholder = $display['title_placeholder'];
								}
								if(isset($display['message_placeholder'])){
									$message_placeholder = $display['message_placeholder'];
								}
							}
							unset($cmd_array['isHistorized'],$cmd_array['configuration'], $cmd_array['template'], $cmd_array['display'], $cmd_array['html']);
							$cmd_array['configuration']['maxValue'] = $maxValue;
							if ($minValue != null) {
								$cmd_array['configuration']['minValue'] = $minValue;
							}
							$cmd_array['display']['generic_type'] = $generic_type;
							if ($icon != null) {
								$cmd_array['display']['icon'] = $icon;
							}
							if(isset($invertBinary)){
								if ($invertBinary != null) {
									$cmd_array['display']['invertBinary'] = $invertBinary;
								}
							}
							if(isset($title_disable)){
								if ($title_disable != null) {
									$cmd_array['display']['title_disable'] = $title_disable;
								}
							}
							if(isset($title_placeholder)){
								if ($title_placeholder != null) {
									$cmd_array['display']['title_placeholder'] = $title_placeholder;
								}
							}
							if(isset($message_placeholder)){
								if ($message_placeholder != null) {
									$cmd_array['display']['message_placeholder'] = $message_placeholder;
								}
							}
							if(isset($actionCodeAccess)){
								if($actionCodeAccess !== null ){
									if($actionCodeAccess !== ''){
										$cmd_array['configuration']['actionCodeAccess'] = true;
									}
								}
							}
							if(isset($actionConfirm)){
								if($actionConfirm !== null){
									if($actionConfirm == 1){
										$cmd_array['configuration']['actionConfirm'] = true;
									}
								}
							}
							if ($cmd_array['type'] == 'action'){
								unset($cmd_array['currentValue']);
							}
							if ($cmd_array['value'] == null || $cmd_array['value'] == ""){
								//unset($cmd_array['value']);
								$cmd_array['value'] == "0";
							}else{
								$cmd_array['value'] = str_replace("#","",$cmd_array['value']);	
							}
							if ($cmd_array['unite'] == null || $cmd_array['unite'] == ""){
								unset($cmd_array['unite']);
							}
							$cmds_array[] = $cmd_array;
                      		$i++;
                      	}
					}
                  	if($i > 0){
                    	$return = $cmds_array;
                    }
				}
                }
			}
		}
		return $return;
	}
	
	public static function discovery_multi($cmds) {
		$array_final = array();
		$tableData = homebridge::PluginMultiInEqLogic();
		foreach ($cmds as &$cmd) {
			if(in_array($cmd['generic_type'], $tableData)){
				$keys = array_keys(array_column($cmds,'eqLogic_id'), $cmd['eqLogic_id']);
				$trueKeys = array_keys(array_column($cmds,'generic_type'), $cmd['generic_type']);
				//if(count($keys) > 1 && count($trueKeys) > 1){
					$result =  array_intersect($keys, $trueKeys);
					if(count($result) > 1){
						$array_final = array_merge_recursive($array_final, $result);
					}
				//}
				
			}
		}
		$dif = array();
		$array_cmd_multi = array();
		foreach ($array_final as &$array_fi){
			if(!in_array($array_fi, $dif)){
				array_push($dif, $array_fi);
				array_push($array_cmd_multi,$array_fi);
			}
		}
		
		return $array_cmd_multi;
	}
	
	public static function change_cmdAndeqLogic($cmds,$eqLogics){
		$plage_cmd = homebridge::discovery_multi($cmds);
		$eqLogic_array = array();
		$nbr_cmd = count($plage_cmd);
		log::add('homebridge', 'debug', 'plage cmd > '.json_encode($plage_cmd).' // nombre > '.$nbr_cmd);
		if($nbr_cmd != 0){
			$i = 0;
			while($i < $nbr_cmd){
				log::add('homebridge', 'info', 'nbr cmd > '.$i.' // id > '.$plage_cmd[$i]);
				$eqLogic_id = $cmds[$plage_cmd[$i]]['eqLogic_id'];
				$name_cmd = $cmds[$plage_cmd[$i]]['name'];
				foreach ($eqLogics as &$eqLogic){
					if($eqLogic['id'] == $eqLogic_id){
						$eqLogic_name = $eqLogic['name'].' / '.$name_cmd;
					}
				}
				log::add('homebridge', 'debug', 'nouveau nom > '.$eqLogic_name);
				$id = $cmds[$plage_cmd[$i]]['id'];
				$new_eqLogic_id = '999'.$eqLogic_id.''.$id;
				$cmds[$plage_cmd[$i]]['eqLogic_id'] = $new_eqLogic_id;
				$keys = array_keys(array_column($cmds,'eqLogic_id'),$eqLogic_id);
				$nbr_keys = count($keys);
				$j = 0;
				while($j < $nbr_keys){
					if($cmds[$keys[$j]]['value'] == $cmds[$plage_cmd[$i]]['id'] && $cmds[$keys[$j]]['type'] == 'action'){
						log::add('homebridge', 'debug', 'Changement de l\'action > '.$cmds[$keys[$j]]['id']);
						$cmds[$keys[$j]]['eqLogic_id'] = $new_eqLogic_id;
					}
					$j++;
				}
				array_push($eqLogic_array,array($eqLogic_id, $new_eqLogic_id, $eqLogic_name));
				$i++;
			}
			
			$column_eqlogic = array_column($eqLogics,'id');
			foreach ($eqLogic_array as &$eqlogic_array_one) {
				$keys = array_keys($column_eqlogic, $eqlogic_array_one[0]);
				$new_eqLogic = $eqLogics[$keys[0]];
				$new_eqLogic['id'] = $eqlogic_array_one[1];
				$new_eqLogic['name'] = $eqlogic_array_one[2];
				array_push($eqLogics, $new_eqLogic);
			}		
		}
		$new_cmds = array('cmds' => $cmds);
		$new_eqLogic = array('eqLogics' => $eqLogics);
		$news = array($new_cmds,$new_eqLogic);
		return $news;
	}
	
	public static function discovery_object() {
		$all = utils::o2a(object::all());
		$return = array();
		foreach ($all as &$object){
			if (isset($object['display']['sendToApp']) && $object['display']['sendToApp'] == "0") {
				continue;
			} else {
				unset($object['configuration'],$object['display']['tagColor'], $object['display']['tagTextColor']);
				$return[]=$object;
			}
		}
		return $return;
	}
	 
	public static function discovery_scenario() {
		$all = utils::o2a(scenario::all());
		$return = array();
		foreach ($all as &$scenario){
			if (isset($scenario['display']['sendToApp']) && $scenario['display']['sendToApp'] == "0") {
				continue;
			} else {
				if ($scenario['display']['name'] != ''){
					$scenario['name'] = $scenario['display']['name'];
				}
				unset($scenario['mode'],$scenario['schedule'], $scenario['scenarioElement'],$scenario['trigger'],$scenario['timeout'],$scenario['description'],$scenario['configuration'],$scenario['type'],$scenario['display']['name']);
				if ($scenario['display'] == [] || $scenario['display']['icon'] == ''){
					unset($scenario['display']);
				}
				$return[]=$scenario;
			}	
		}
		return $return;
	}
	
	public static function discovery_message() {
		$all = utils::o2a(message::all());
		$return = array();
		foreach ($all as &$message){
				$return[]=$message;	
		}
		return $return;
	}
	
	public static function discovery_plan() {
		$all = utils::o2a(planHeader::all());
		$return = array();
		foreach ($all as &$plan){
				$return[]=$plan;	
		}
		return $return;
	}


	public static function delete_object_eqlogic_null($objectsATraiter,$eqlogicsATraiter){
		$retour = array();
		foreach ($objectsATraiter as &$objectATraiter){
			$id_object = $objectATraiter['id'];
			foreach ($eqlogicsATraiter as &$eqlogicATraiter){
				if ($id_object == $eqlogicATraiter['object_id']){
					array_push($retour,$objectATraiter);
					break;
				}
			}
		}
		return $retour;
	}
	/**************************************************************************************/
	/*                                                                                    */
	/*                         Permet de creer le Json du QRCode                          */
	/*                                                                                    */
	/**************************************************************************************/

	public function getQrCode() {
		$interne = network::getNetworkAccess('internal');
		$externe = network::getNetworkAccess('external');
		$user = $this->getConfiguration('affect_user');
		
		if($interne == null || $interne == 'http://:80' || $interne == 'https://:80'){
			$retour = 'internalError';
		}else if($externe == null || $externe == 'http://:80' || $externe == 'https://:80'){
			$retour = 'externalError';
		}else if($user == ''){
			$retour = 'UserError';
		}else{
			$key = $this->getLogicalId();
			$request_qrcode = array(
			'eqLogic_id' => $this->getId(),
				'url_internal' => $interne,
				'url_external' => $externe,
				'Iq' => $key
			);
			if ($user != '') {
				$username = user::byId($this->getConfiguration('affect_user'));
				if (is_object($username)) {
					$request_qrcode['username'] = $username->getLogin();
					$request_qrcode['apikey'] = $username->getHash();
				}
			}
			$retour = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.json_encode($request_qrcode);
		}
		return $retour;
	}
	
	/**************************************************************************************/
	/*                                                                                    */
	/*                                 Pour les notifications                             */
	/*                                                                                    */
	/**************************************************************************************/
	
	public static function jsonPublish($os,$titre,$message,$badge = 'null'){
		if($os == 'ios'){
			if($badge == 'null'){
				$publish = '{"default": "Erreur de texte de notification","APNS": "{\"aps\":{\"alert\": {\"title\":\"'.$titre.'\",\"body\":\"'.$message.'\"},\"badge\":'.$badge.',\"sound\":\"silence.caf\"}}"}';
			}else{
				$publish = '{"default": "test", "APNS": "{\"aps\":{\"alert\": {\"title\":\"'.$titre.'\",\"body\":\"'.$message.'\"},\"sound\":\"silence.caf\"}}"}';
			}
		}else if($os == 'android'){
			$publish = '{"default": "Erreur de texte de notification", "GCM": "{ \"data\": {\"notificationId\":\"'.rand(3, 5).'\",\"title\":\"'.$titre.'\",\"text\":\"'.$message.'\",\"vibrate\":\"true\",\"lights\":\"true\" } }"}';
		}else if($os == 'microsoft'){
			
		}
		return $publish;
	}
	
	public static function notification($arn,$os,$titre,$message,$badge = 'null'){
		log::add('homebridge', 'debug', 'notification en cours !');
		if($badge == 'null'){
			$publish = homebridge::jsonPublish($os,$titre,$message,$badge);
		}else{
			$publish = homebridge::jsonPublish($os,$titre,$message);
		}
		log::add('homebridge', 'debug', 'JSON envoyé : '.$publish);
		$post = [
			'id' => '1',
			'type' => $os,
			'arn' => $arn,
			'publish' => $publish 
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,homebridge::LienAWS());
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);            
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        log::add('homebridge', 'debug', 'notification resultat > '.$server_output);
	}
	
	/**************************************************************************************/
	/*                                                                                    */
	/*                         Permet de creer l'ID Unique du téléphone                   */
	/*                                                                                    */
	/**************************************************************************************/
	
	public function postInsert() {
		$key = config::genKey(32);
		$this->setLogicalId($key);
		$this->save();
	}
	
	public function postSave() {
		$this->crea_cmd();
	}
    
    function crea_cmd() {
    	$cmd = $this->getCmd(null, 'notif');
        if (!is_object($cmd)) {
			$cmd = new homebridgeCmd();
			$cmd->setLogicalId('notif');
			$cmd->setName(__('Notif', __FILE__));
			$cmd->setIsVisible(1);
			$cmd->setDisplay('generic_type', 'GENERIC_ACTION');
		}
		$cmd->setOrder(0);
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

    }
	

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */
}

class homebridgeCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	/*
											 * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
											public function dontRemoveCmd() {
											return true;
											}
											 */

	public function execute($_options = array()) {
		$eqLogic = $this->getEqLogic();
		$arn = $eqLogic->getConfiguration('notificationArn', null);
		$os = $eqLogic->getConfiguration('type_homebridge', null);
        if ($this->getType() != 'action') {
			return;
		}
		log::add('homebridge', 'debug', 'Notif > '.json_encode($_options).' / '.$eqLogic->getId().' / '.$this->getLogicalId(), 'config');
		if($this->getLogicalId() == 'notif') {
			log::add('homebridge', 'debug', 'Commande de notification ', 'config');
			if($arn != null && $os != null){
				homebridge::notification($arn,$os,$_options['title'],$_options['message']);
				log::add('homebridge', 'debug', 'Action : Envoi d\'une configuration ', 'config');
			}else{
				log::add('homebridge', 'debug', 'ARN non configuré ', 'config');	
			}
		};
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
