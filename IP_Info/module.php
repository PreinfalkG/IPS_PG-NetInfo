<?php
declare(strict_types=1);

// Modul IP Info | "prefix": "IPINFO"

require_once __DIR__ . '/../libs/COMMON.php'; 
require_once __DIR__ . '/../libs/SleekDB/SleekDB.php'; 

	class IPInfo extends IPSModule
	{

		const API_URL_TEMPLATE = "https://ipinfo.io/%%IP%%?token=%%TOKEN%%";
		const WEB_HOOK = "/hook/NetIpInfo";		// >> http://127.0.0.1:3777/hook/NetInfo

		private $logLevel = 3;
		private $logCnt = 0;
		private $enableIPSLogOutput = false;

		private $sleekDbDir;
		private $sleekDbConfig;
		private $sleekDBStore;

		public function __construct($InstanceID) {
		
			parent::__construct($InstanceID);		// Diese Zeile nicht löschen
		
			$this->logLevel = @$this->ReadPropertyInteger("LogLevel"); 
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel)); }

			$this->sleekDbDir = IPS_GetLogDir()."SleekDB";
			$this->sleekDbConfig = ["auto_cache" => true, "cache_lifetime" => null, "timeout" => false, "primary_key" => "_id", "search" => [ "min_length" => 2, "mode" => "or", "score_key" => "scoreKey"]];
			$this->sleekDBStore = \SleekDB\SleekDB::store('CacheIPInfo', $this->sleekDbDir, $this->sleekDbConfig);
		}


		public function Create() {

			parent::Create();	//Never delete this line!

			$logMsg = sprintf("Create Modul '%s [%s]'...", IPS_GetName($this->InstanceID), $this->InstanceID);
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, $logMsg); }
			IPS_LogMessage(__CLASS__."_".__FUNCTION__, $logMsg);
	
			$logMsg = sprintf("KernelRunlevel '%s'", IPS_GetKernelRunlevel());
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $logMsg); }

			$hostname = gethostname();
			if(strpos($hostname, "ADW20") !== false) {
				$this->RegisterPropertyBoolean('EnableAPI', true);
				$this->RegisterPropertyString('apiToken', "2600264f74a47a");	
			} else {
				$this->RegisterPropertyBoolean('EnableAPI', true);
				$this->RegisterPropertyString('apiToken', "");	
			}

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

			IPS_LogMessage(__CLASS__."_".__FUNCTION__, sprintf("Destroy Modul '%s' ...", $this->InstanceID));
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Destroy Modul '%s [%s']...", IPS_GetName($this->InstanceID), $this->InstanceID), 0); }

			if (!IPS_InstanceExists($this->InstanceID)) {	// Instanz wurde eben gelöscht und existiert nicht mehr
				//$this->UnregisterHook(self::WEB_HOOK);
				$this->AddLog(__FUNCTION__, sprintf("INFO :: Hook '%s' Konfiguration könnte jetzt ungültig sein!", self::WEB_HOOK), 0, true);
			}
			parent::Destroy();						//Never delete this line!
		}

		public function ApplyChanges() {

			parent::ApplyChanges(); //Never delete this line!

			$this->logLevel = $this->ReadPropertyInteger("LogLevel");
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel)); }

			$this->RegisterProfiles();
			$this->RegisterVariables();  

			if(IPS_GetKernelRunlevel() == KR_READY) {
				$this->RegisterHook(self::WEB_HOOK);
			}

			$this->SetStatus(102);
		}
		

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data)	{
			$logMsg = sprintf("TimeStamp: %s | SenderID: %s | Message: %s | Data: %s", $TimeStamp, $SenderID, $Message, json_encode($Data));
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $logMsg); }

			parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);
			if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) 	{
					if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("RegisterHook '%s'", self::WEB_HOOK)); }
					$this->RegisterHook(self::WEB_HOOK);
			}			
		}


		public function GetIPInfo(string $ipAddress) {

			$startTime = microtime(true);
			SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1); 
			$ipInfoArr = $this->sleekDBStore->findOneBy(["ip", "=", $ipAddress]);

			if(is_null($ipInfoArr)) {

				$enableAPI = $this->ReadPropertyBoolean("EnableAPI");
				if($enableAPI) {
					if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' not in Cache > Init API request ...", $ipAddress), 0); }
					$apiUrl = self::API_URL_TEMPLATE;
					$apiUrl = str_replace("%%IP%%", $ipAddress, $apiUrl);
					$apiUrl = str_replace("%%TOKEN%%",  $this->ReadPropertyString("apiToken"), $apiUrl);
					$ipInfoJson = $this->RequestJsonData($apiUrl);
					$ipInfoArr = json_decode($ipInfoJson, true);
					$ipInfoArr["TimeStamp"] = time();
					$ipInfoArr["DateTime"] = date('d.m.Y H:i:s',time());
					$ipInfoArr = $this->sleekDBStore->insert($ipInfoArr);
					$ipInfoArr["method"] = "API";
					SetValue($this->GetIDForIdent("requestCntAPI"), GetValue($this->GetIDForIdent("requestCntAPI")) + 1); 
				} else {
					$logMsg = "API calls are disabled. #Enable ipinfo.io API Requests# in Modul Settings";
					$this->AddLog(__FUNCTION__, "WARN :: " . $logMsg, 0, true);
					die(json_encode(array('WARN' => $logMsg)));
				}
			} else {
				if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("'%s' found in Cache > return cached data ...", $ipAddress), 0); }
				$ipInfoArr["method"] = "DbCache";
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


		private function mapped_implode($glue, $array, $symbol = '=') {
			return implode($glue, array_map(
					function($k, $v) use($symbol, $glue) {
						if(is_array($v)) {
							return "" . $this->mapped_implode($glue, $v, $symbol);
						}
						return $k . $symbol . $v;
					}, 
					array_keys($array),
					array_values($array)
					)
				);
		}

		private function mapped_implode_oneLevel($glue, $array, $symbol = '=') {
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

	    private function RegisterHook(string $webHook) {

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

		protected function UnregisterHook(string $webHook) {

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

			} else {
				echo 'n.a.';
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

			
			$scriptFileSource = sprintf("<?php echo 'InstanceID is %s' ?>", $this->InstanceID);
			$parentDirectory = dirname(__FILE__);
			$scriptFile = $parentDirectory . '\TestScript.txt';
			$file = @file_get_contents($scriptFile);
			if($file === false) {
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Register minimal Test Script. File '%s' NOT exists", $scriptFile)); }
				$scriptFileSource = sprintf('<?php $ipInfo = IPINFO_GetIPInfo(%s,"8.8.8.8"); var_dump($ipInfo); ?>', $this->InstanceID);				
			} else {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Register Test Script > Source File Template '%s'", $scriptFile)); }
				$scriptFileSource = str_replace("%%INSTANCE_ID%%", strval($this->InstanceID), $file);			
			}
			$this->RegisterScript("SampleRequests", "Sample Requests", $scriptFileSource, 990);

			//IPS_ApplyChanges($this->archivInstanzID);
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, "Variables registered", 0); }
		}

		protected function RequestJsonData(string $url) {

			$json = "";
	
			if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Request API '%s'", $url), 0); }

			$startTime = microtime(true);
			$streamContext = stream_context_create( array('https'=> array('timeout' => 5) ) ); //5 seconds
			$json = file_get_contents($url, false, $streamContext);
		
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

		protected function CalcDuration_ms(float $timeStart) {
			$duration =  microtime(true)- $timeStart;
			return round($duration*1000,2);
		}	


		protected function AddLog($name, $daten, $format=0, $enableIPSLogOutput=false) {
			$this->logCnt++;
			$logSender = "[".__CLASS__."] - " . $name;
			if($this->logLevel >= LogLevel::DEBUG) {
				$logSender = sprintf("%02d-T%2d [%s] - %s", $this->logCnt, $_IPS['THREAD'], __CLASS__, $name);
			} 
			$this->SendDebug($logSender, $daten, $format); 	
		
			if($enableIPSLogOutput or $this->enableIPSLogOutput) {
				if($format == 0) {
					IPS_LogMessage($logSender, $daten);	
				} else {
					IPS_LogMessage($logSender, $this->String2Hex($daten));			
				}
			}
		}

	}