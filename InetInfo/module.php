<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/COMMON.php'; 
require_once __DIR__ . '/../libs/SleekDB/SleekDB.php'; 

	class NetInfo extends IPSModule {

		const IPINFO_API_URL_TEMPLATE = "https://ipinfo.io/%%IP%%?token=%%TOKEN%%";
		const IPSTACK_API_URL_TEMPLATE = "http://api.ipstack.com/%%IP%%?access_key=%%accessKey%%";
		const MACADDRESS_API_URL_TEMPLATE = "https://api.macaddress.io/v1?apiKey=%%apiKey%%&output=json&search=%%mac%%";
		const WEB_HOOK = "/hook/NetInfo";		// >> http://127.0.0.1:3777/hook/NetInfo

		private $logLevel = 3;
		private $parentRootId;

		private $apiEnabled_ipInfo;
		private $apiEnabled_ipStack;
		private $apiEnabled_macAddress;		

		private $sleekDbDir;
		private $sleekDbConfig;
		private $sleekDBStore;

		public function __construct($InstanceID) {
		
			parent::__construct($InstanceID);		// Diese Zeile nicht löschen
		
			if(IPS_InstanceExists($InstanceID)) {

				$this->parentRootId = IPS_GetParent($InstanceID);
				//$this->archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];

				$currentStatus = $this->GetStatus();
				if($currentStatus == 102) {				//Instanz ist aktiv
					$this->logLevel = $this->ReadPropertyInteger("LogLevel");
					$this->apiEnabled_ipInfo = $this->ReadPropertyBoolean("ipInfo_EnableAPI");
					$this->apiEnabled_ipStack = $this->ReadPropertyBoolean("ipStack_EnableAPI");		
					$this->apiEnabled_macAddress = $this->ReadPropertyBoolean("macAddress_EnableAPI");		
	
					if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel), 0); }
				} else {
					if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Current Status is '%s'", $currentStatus), 0); }	
				}
				
				$this->sleekDbDir = IPS_GetLogDir()."SleekDB";
				$this->configuration = ["auto_cache" => true, "cache_lifetime" => null, "timeout" => false, "primary_key" => "_id", "search" => [ "min_length" => 2, "mode" => "or", "score_key" => "scoreKey"]];
			


			} else {
				IPS_LogMessage("[" . __CLASS__ . "] - " . __FUNCTION__, sprintf("INFO: Instance '%s' not exists", $InstanceID));
			}
		}



		public function Create() {
			//Never delete this line!
			parent::Create();

			IPS_LogMessage("[" . __CLASS__ . "] - " . __FUNCTION__, sprintf("Create Modul '%s' ...", $this->InstanceID));
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Create Modul '%s [%s']...", IPS_GetName($this->InstanceID), $this->InstanceID), 0); }

			$this->RegisterPropertyBoolean('ipInfo_EnableAPI', false);
			$this->RegisterPropertyString('ipInfo_apiToken', "2600264f74a47a");	

			$this->RegisterPropertyBoolean('ipStack_EnableAPI', false);
			$this->RegisterPropertyString('ipStack_accessKey', "d927aadacc55aaa0770785bb9195f31c");	

			$this->RegisterPropertyBoolean('macAddress_EnableAPI', false);
			$this->RegisterPropertyString('macAddress_accessKey', "at_8pSPwjVt5ZFUPdpDkAqQK4Thdzhpk");	


			$this->RegisterPropertyInteger('LogLevel', 3);

			$runlevel = IPS_GetKernelRunlevel();
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("KernelRunlevel '%s'", $runlevel), 0); }	
			if ( $runlevel == KR_READY ) {
				$this->RegisterHook(self::WEB_HOOK);
			} else {
				$this->RegisterMessage(0, IPS_KERNELMESSAGE);
			}
		}

		public function Destroy() {

			IPS_LogMessage("[" . __CLASS__ . "] - " . __FUNCTION__, sprintf("Destroy Modul '%s' ...", $this->InstanceID));
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Destroy Modul '%s [%s']...", IPS_GetName($this->InstanceID), $this->InstanceID), 0); }

			if (!IPS_InstanceExists($this->InstanceID)) {	// Instanz wurde eben gelöscht und existiert nicht mehr
				$this->UnregisterHook(self::WEB_HOOK);
			}
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()	{
			//Never delete this line!
			parent::ApplyChanges();

			$this->logLevel = $this->ReadPropertyInteger("LogLevel");
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel), 0); }

			$this->RegisterProfiles();
			$this->RegisterVariables();  

			if(IPS_GetKernelRunlevel() == KR_READY) {
				$this->RegisterHook(self::WEB_HOOK);
			}

			$this->SetStatus(102);
		}
		
		public function MessageSink($TimeStamp, $SenderID, $Message, $Data)	{

			$logMsg = sprintf("TimeStamp: %s | SenderID: %s | Message: %s | Data: %s", $TimeStamp, $SenderID, $Message, print_r($Data,true));
			IPS_LogMessage("[" . __CLASS__ . "] - " . __FUNCTION__, $logMsg);
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, $logMsg, 0); }

			parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);
			if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) 	{
					$this->RegisterHook(self::WEB_HOOK);
			}
		}


		public function GetIPInfo(string $ipAddress) {

			$startTime = microtime(true);
			SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1); 

			$this->sleekDBStore = \SleekDB\SleekDB::store('CacheIPInfo', $this->sleekDbDir, $this->configuration);
			$ipInfoArr = $this->sleekDBStore->findOneBy(["ip", "=", $ipAddress]);

			if(is_null($ipInfoArr)) {

				$specificIpAddress = false;
				if(strpos($ipAddress,"10.") === 0) {  
					$specificIpAddress = true; 
					$usage = "IPv4 private Address";
				} else if (strpos($ipAddress,"172.") === 0) { 
					$specificIpAddress = true; 
					$usage = "IPv4 private Address";
				} else if (strpos($ipAddress,"192.") === 0) { 
					$specificIpAddress = true; 
					$usage = "IPv4 private Address";
				} else if (strpos($ipAddress,"224.") === 0) { 
					$specificIpAddress = true; 
					$usage = "IPv4 multicast address";
				} else if (strpos($ipAddress,"255.") === 0) { 
					$specificIpAddress = true; 
					$usage = "IPv4 Broadcast address";
				} else if (strpos($ipAddress,"0.0.0.0") === 0) { 
					$specificIpAddress = true; 
					$usage = "IPv4 0.0.0.0";
				}							

				if($specificIpAddress) {
					$ipInfoArr["ip"] = $ipAddress;
					$ipInfoArr["hostname"] = "n.a.";
					$ipInfoArr["anycast"] = "n.a.";
					$ipInfoArr["city"] = "n.a.";
					$ipInfoArr["region"] = "n.a.";
					$ipInfoArr["country"] = $usage;
					$ipInfoArr["loc"] = "n.a.";
					$ipInfoArr["org"] = "n.a.";
					$ipInfoArr["postal"] = "n.a.";
					$ipInfoArr["timezone"] = "n.a.";
					$ipInfoArr["TimeStamp"] = time();
					$ipInfoArr["DateTime"] = date('d.m.Y H:i:s',time());
					$ipInfoArr = $this->sleekDBStore->insert($ipInfoArr);
					$ipInfoArr["source"] = "API";

				} else {

					if($this->apiEnabled_ipInfo) {
						if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' not in Cache > Init 'ipinfo.io' API request ...", $ipAddress), 0); }
						$apiUrl = self::IPINFO_API_URL_TEMPLATE;
						$apiUrl = str_replace("%%IP%%", $ipAddress, $apiUrl);
						$apiUrl = str_replace("%%TOKEN%%",  $this->ReadPropertyString("ipInfo_apiToken"), $apiUrl);
						$ipInfoJson = $this->RequestJsonData($apiUrl);
						$ipInfoArr = json_decode($ipInfoJson, true);
						$ipInfoArr["TimeStamp"] = time();
						$ipInfoArr["DateTime"] = date('d.m.Y H:i:s',time());
						$ipInfoArr = $this->sleekDBStore->insert($ipInfoArr);
						$ipInfoArr["source"] = "API";
						SetValue($this->GetIDForIdent("requestCntAPI"), GetValue($this->GetIDForIdent("requestCntAPI")) + 1); 
					} else {
						$logMsg = "API calls are disabled. #Enable ipinfo.io API Requests# in Modul Settings";
						$this->AddLog(__FUNCTION__, "WARN :: " . $logMsg, 0, true);
						die(json_encode(array('WARN' => $logMsg)));
					}
				}
			} else {
				if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' found in Cache > return cached data ...", $ipAddress), 0); }
				$ipInfoArr["source"] = "DbCache";
				SetValue($this->GetIDForIdent("requestCntCache"), GetValue($this->GetIDForIdent("requestCntCache")) + 1); 
			}

			$ipInfoArr["duration_ms"] = $this->CalcDuration_ms($startTime);
			return $ipInfoArr;
		}

		public function GetIPInfoAsString(string $ipAddress, string $format, string $delimiter) {
			$ipInfoArr = $this->GetIPInfo($ipAddress);
			$ipInfoStr = $delimiter;
			if(($format == "ALL") or ($format =="")) {
				$ipInfoStr = $this->mapped_implode($delimiter, $ipInfoArr, ': ');
			} else if($format == "json") {
					$ipInfoStr = json_encode($ipInfoArr);
			} else {
				if($format == "ValueOnly") {
					$ipInfoStr = implode($delimiter, $ipInfoArr);
				} else {
					$formatArr = explode(',', $format);
					if(is_array($formatArr)) {
						$whithKeys = !in_array("ValueOnly", $formatArr);
						foreach($formatArr as $key) {
							if(array_key_exists($key, $ipInfoArr)) {
								//$ipInfoStr .= $key . ": " . $formatArr[$key] . " | ";
								if($whithKeys) {
									$ipInfoStr .= sprintf("%s: %s%s", $key, $ipInfoArr[$key], $delimiter);
								} else {
									$ipInfoStr .= sprintf("%s%s", $ipInfoArr[$key], $delimiter);
								}
							}
						}
					} else {
						$ipInfoStr = "Key(s) not found in IP-Infos!";
					}
					//$ipInfoData = array_intersect_key($ipInfoData, array_flip($keys));
				}
			}		
			$ipInfoStr = trim($ipInfoStr);
			$ipInfoStr = trim($ipInfoStr,$delimiter);
			return $ipInfoStr;
		}


		public function GetIPStack(string $ipAddress) {

			$startTime = microtime(true);
			SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1); 

			$this->sleekDBStore = \SleekDB\SleekDB::store('CacheIPStack', $this->sleekDbDir, $this->configuration);
			$ipStackArr = $this->sleekDBStore->findOneBy(["ip", "=", $ipAddress]);

			if(is_null($ipStackArr)) {

				if($this->apiEnabled_ipStack) {
					if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' not in Cache > Init 'ipstack.com' API request ...", $ipAddress), 0); }
					$apiUrl = self::IPSTACK_API_URL_TEMPLATE;
					$apiUrl = str_replace("%%IP%%", $ipAddress, $apiUrl);
					$apiUrl = str_replace("%%accessKey%%",  $this->ReadPropertyString("ipStack_accessKey"), $apiUrl);
					$ipStackJson = $this->RequestJsonData($apiUrl);
					$ipStackArr = json_decode($ipStackJson, true);
					$ipStackArr["TimeStamp"] = time();
					$ipStackArr["DateTime"] = date('d.m.Y H:i:s',time());
					$ipStackArr = $this->sleekDBStore->insert($ipStackArr);
					$ipStackArr["source"] = "API";
					SetValue($this->GetIDForIdent("requestCntAPI"), GetValue($this->GetIDForIdent("requestCntAPI")) + 1); 
				} else {
					$logMsg = "API calls are disabled. #Enable ipstack.com API Requests# in Modul Settings";
					$this->AddLog(__FUNCTION__, "WARN :: " . $logMsg, 0, true);
					die(json_encode(array('WARN' => $logMsg)));
				}
			} else {
				if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' found in Cache > return cached data ...", $ipAddress), 0); }
				$ipStackArr["source"] = "DbCache";
				SetValue($this->GetIDForIdent("requestCntCache"), GetValue($this->GetIDForIdent("requestCntCache")) + 1); 
			}

			$ipStackArr["duration_ms"] = $this->CalcDuration_ms($startTime);
			return $ipStackArr;
		}

		public function GetIPStackAsString(string $ipAddress, string $format, string $delimiter) {
			$ipStackArr = $this->GetIPStack($ipAddress);
			$ipStackStr = $delimiter;
			if(($format == "ALL") or ($format =="")) {
				$ipStackStr = $this->mapped_implode($delimiter, $ipStackArr, ': ');
			} else if($format == "json") {
					$ipStackStr = json_encode($ipStackArr);
			} else {
				if($format == "ValueOnly") {
					$ipStackStr = implode($delimiter, $ipStackArr);
				} else {
					$formatArr = explode(',', $format);
					if(is_array($formatArr)) {
						$whithKeys = !in_array("ValueOnly", $formatArr);
						foreach($formatArr as $key) {
							if(array_key_exists($key, $ipStackArr)) {
								
								$arrValue = $ipStackArr[$key];
								if(is_array($arrValue)) {
									$ipStackStr = print_r($arrValue, true);
								} else {

									if($whithKeys) {
										$ipStackStr .= sprintf("%s: %s%s", $key, $arrValue, $delimiter);
									} else {
										$ipStackStr .= sprintf("%s%s", $arrValue, $delimiter);
									}
								}
							}
						}
					} else {
						$ipStackStr = "Key(s) not found in IP-Stack!";
					}
				}
			}		
			$ipStackStr = trim($ipStackStr);
			$ipStackStr = trim($ipStackStr,$delimiter);
			return $ipStackStr;
		}



		public function GetMACadressInfo(string $macAddress) {

			$startTime = microtime(true);
			SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1); 

			$this->sleekDBStore = \SleekDB\SleekDB::store('CacheMACaddress', $this->sleekDbDir, $this->configuration);
			$macArr = $this->sleekDBStore->findOneBy(["mac", "=", $macAddress]);

			if(is_null($macArr)) {

				if($this->apiEnabled_macAddress) {
					if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' not in Cache > Init 'macaddress.io' API request ...", $macAddress), 0); }
					$apiUrl = self::MACADDRESS_API_URL_TEMPLATE;
					$apiUrl = str_replace("%%mac%%", $macAddress, $apiUrl);
					$apiUrl = str_replace("%%apiKey%%",  $this->ReadPropertyString("macAddress_accessKey"), $apiUrl);
					$macAddressJson = $this->RequestJsonData($apiUrl);
					$macArr = json_decode($macAddressJson, true);
					$macArr["TimeStamp"] = time();
					$macArr["DateTime"] = date('d.m.Y H:i:s',time());
					$macArr = $this->sleekDBStore->insert($macArr);
					$macArr["source"] = "API";
					SetValue($this->GetIDForIdent("requestCntAPI"), GetValue($this->GetIDForIdent("requestCntAPI")) + 1); 
				} else {
					$logMsg = "API calls are disabled. #Enable macaddress.io API Requests# in Modul Settings";
					$this->AddLog(__FUNCTION__, "WARN :: " . $logMsg, 0, true);
					die(json_encode(array('WARN' => $logMsg)));
				}
			} else {
				if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' found in Cache > return cached data ...", $macAddress), 0); }
				$macArr["source"] = "DbCache";
				SetValue($this->GetIDForIdent("requestCntCache"), GetValue($this->GetIDForIdent("requestCntCache")) + 1); 
			}

			$macArr["duration_ms"] = $this->CalcDuration_ms($startTime);
			return $macArr;
		}

		public function GetMACadressInfoAsString(string $macAddress, string $format, string $delimiter) {
			$macInfoArr = $this->GetMACadressInfo($macAddress);
			$macInfoStr = $delimiter;

			if(array_key_exists("vendorDetails", $macInfoArr)) {
				
				$vendorData = $macInfoArr["vendorDetails"];
				$macVendorInfo = sprintf("%s | %s - %s", $vendorData["oui"], $vendorData["countryCode"], $vendorData["companyName"]);
			} else {
				$macVendorInfo = "'vendorDetails' not found in Data  -> " . print_r($macInfoArr,true);
			}
			$macVendorInfo = trim($macVendorInfo);
			$macVendorInfo = trim($macVendorInfo,$delimiter);
			return $macVendorInfo;
		}


		private function mapped_implode($glue, $array, $symbol = '=') {
			return implode($glue, array_map(
					function($k, $v) use($symbol) {
						return $k . $symbol . $v;
					}, 
					array_keys($array),
					array_values($array)
					)
				);
		}

		public function ResetCounterVariables() {
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, 'RESET Counter Variables', 0); }
            
			SetValue($this->GetIDForIdent("requestCnt"), 0);
			SetValue($this->GetIDForIdent("requestCntAPI"), 0);
			SetValue($this->GetIDForIdent("requestCntCache"), 0);
			SetValue($this->GetIDForIdent("errorCnt"), 0); 
			SetValue($this->GetIDForIdent("lastError"), "-"); 
			SetValue($this->GetIDForIdent("lastAPIRequestDuration"), 0); 
		}

	    private function RegisterHook($webHook) {

			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Register Hook '%s'", $webHook), 0); }

			$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
			if(sizeof($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
				$found = false;
				foreach($hooks as $index => $hook) {
					if($hook['Hook'] == $webHook) {
						if($hook['TargetID'] == $this->InstanceID) {
							if($this->logLevel >= LogLevel::INFO) { 
								$logMsg = sprintf("Hook '%s bereits vorhaden | TargetID is %s", $webHook, $hook['TargetID']);
								$this->AddLog(__FUNCTION__, $logMsg, 0); 
							}
							return;
						}
						$hooks[$index]['TargetID'] = $this->InstanceID;
						$found = true;
					}
				}
				if(!$found) {
					if($this->logLevel >= LogLevel::INFO) { 
						$logMsg = sprintf("Hook '%s wird erstellt | TargetID is %s", $webHook, $this->InstanceID);
						$this->AddLog(__FUNCTION__, $logMsg, 0); 
					}					
					$hooks[] = Array("Hook" => $webHook, "TargetID" => $this->InstanceID);					
				}
				IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}

		protected function UnregisterHook($webHook) {

			$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
			if (count($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
				$found = false;
				foreach ($hooks as $index => $hook)	{
					if ($hook['Hook'] == $webHook) {
						$found = $index;
						break;
					}
				}
		
				if ($found !== false) {
					if($this->logLevel >= LogLevel::INFO) { 
						$logMsg = sprintf("Hook '%s' gefunden mit Index %s > Hook wird gelöscht ...", $webHook, $index);
						$this->AddLog(__FUNCTION__, $logMsg, 0); 
					}	
					array_splice($hooks, $index, 1);
					IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
					IPS_ApplyChanges($ids[0]);
				} else {
					if($this->logLevel >= LogLevel::INFO) { 
						$logMsg = sprintf("Hook '%s' nicht gefunden. Löschen nicht nötig/möglich!", $webHook);
						$this->AddLog(__FUNCTION__, $logMsg, 0); 
					}
				}
			}
		}

		protected function ProcessHookData() {

			if($this->logLevel >= LogLevel::COMMUNICATION) { 
				$logMsg = sprintf("Hook Query Parameter: %s",  print_r($_GET, true));
				$this->AddLog(__FUNCTION__, $logMsg, 0); 
			}

			if(isset($_GET['GetIpInfo'])) {

				$ip = $_GET['GetIpInfo'];
				$format = "";
				$delimiter = "|";

				if(isset($_GET['format'])) { $format = $_GET['format']; }
				if(isset($_GET['delimiter'])) { $delimiter = $_GET['delimiter']; }

				echo $this->GetIPInfoAsString($ip, $format, $delimiter);

			} else if(isset($_GET['GetIpStack'])) {

				$ip = $_GET['GetIpStack'];
				$format = "";
				$delimiter = "|";

				if(isset($_GET['format'])) { $format = $_GET['format']; }
				if(isset($_GET['delimiter'])) { $delimiter = $_GET['delimiter']; }

				echo $this->GetIPStackAsString($ip, $format, $delimiter);

			} else if(isset($_GET['GetMacVendor'])) {

				$mac = $_GET['GetMacVendor'];
				$format = "";
				$delimiter = "|";

				if(isset($_GET['format'])) { $format = $_GET['format']; }
				if(isset($_GET['delimiter'])) { $delimiter = $_GET['delimiter']; }

				echo $this->GetMACadressInfoAsString($mac, $format, $delimiter);
				

			} else {
				echo 'WARN :: Request Parameter not defined!';
			}

			/* I N F O
				https://github.com/1007/Symcon1007_Grafana/blob/master/Symcon1007%20Grafana/module.php
				https://github.com/symcon/SymconMisc/blob/master/libs/WebHookModule.php

				http://127.0.0.1:3777/hook/NetInfo?GetIpInfo=8.8.8.8&format=country,city,org&delimiter=|
			*/
	
		}

		protected function RegisterProfiles() {


			//if ( !IPS_VariableProfileExists('GEN24.Percent') ) {
			//	IPS_CreateVariableProfile('GEN24.Percent', VARIABLE::TYPE_INTEGER );
			//	IPS_SetVariableProfileDigits('GEN24.Percent', 0 );
			//	IPS_SetVariableProfileText('GEN24.Percent', "", " %" );
			//	//IPS_SetVariableProfileValues('GEN24.Prozent', 0, 0, 0);
			//} 
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, "Profiles registered", 0); }
		}

		protected function RegisterVariables() {
			
			$this->RegisterVariableInteger("requestCnt", "Request Cnt", "", 900);
			$this->RegisterVariableInteger("requestCntAPI", "Request Cnt API", "", 910);
			$this->RegisterVariableInteger("requestCntCache", "Request Cnt Cache", "", 910);
			$this->RegisterVariableInteger("errorCnt", "Error Cnt", "", 920);
			$this->RegisterVariableString("lastError", "Last Error", "", 920);
			$this->RegisterVariableFloat("lastAPIRequestDuration", "Last API Request Duration [ms]", "", 940);	

			$scriptScr = sprintf('<?php $ipInfo = NETINFO_GetIPInfo(%s,"8.8.8.8"); var_dump($ipInfo); ?>',$this->InstanceID);
			$this->RegisterScript("sampleRequestIpInfo", "Sample Request - ipinfo.io", $scriptScr, 990);

			$scriptScr = sprintf('<?php $ipStack = NETINFO_GetIPStack(%s,"8.8.8.8"); var_dump($ipStack); ?>',$this->InstanceID);
			$this->RegisterScript("sampleRequestIpStack", "Sample Request - ipstack.com", $scriptScr, 991);

			$scriptScr = sprintf('<?php $macAddress = NETINFO_GetMACadressInfo(%s,"d8:29:18:6c:23:b1"); var_dump($macAddress); ?>',$this->InstanceID);
			$this->RegisterScript("sampleRequestMacAdressInfo", "Sample Request - macaddress.io", $scriptScr, 991);

			

			//IPS_ApplyChanges($this->archivInstanzID);
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, "Variables registered", 0); }

		}

		protected function RequestJsonData($url) {

			$json = "";
	
			if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Request API '%s'", $url), 0); }

			$startTime = microtime(true);
			$streamContext = stream_context_create( array('https'=> array('timeout' => 5) ) ); //5 seconds
			$json = file_get_contents($url, false, $streamContext);		// mayby use https://github.com/guzzle/guzzle
		
			if ($json === false) {
				$error = error_get_last();
				$errorMsg = implode (" | ", $error);
				SetValue($this->GetIDForIdent("errorCnt"), GetValue($this->GetIDForIdent("errorCnt")) + 1); 
				SetValue($this->GetIDForIdent("lastError"), $errorMsg);
		
				if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("API Response '%s'", $json), 0); }
				$logMsg =  sprintf("ERROR %s", $errorMsg);
				if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, $logMsg, 0); }
				
				//header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
				$this->AddLog(__FUNCTION__, "ERROR :: " . $errorMsg, 0, true);
				die(json_encode(array('ERROR' => $errorMsg)));
			} else {
				if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("API Response '%s'", $json), 0); }
				//SetValue($this->GetIDForIdent("receiveCnt"), GetValue($this->GetIDForIdent("receiveCnt")) + 1);  											
				//SetValue($this->GetIDForIdent("LastDataReceived"), time()); 
			}

			$duration_ms = $this->CalcDuration_ms($startTime);
			SetValue($this->GetIDForIdent("lastAPIRequestDuration"), $duration_ms);

			return $json;
		}

		public function CalcDuration_ms(float $timeStart) {
			$duration =  microtime(true)- $timeStart;
			return round($duration*1000,2);
		}	

		protected function AddLog($name, $daten, $format, $enableIPSLogOutput=false) {
			$this->SendDebug("[" . __CLASS__ . "] - " . $name, $daten, $format); 	
	
			if($enableIPSLogOutput) {
				if($format == 0) {
					IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $daten);	
				} else {
					IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $this->String2Hex($daten));			
				}
			}
		}


	}