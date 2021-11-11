# IP Address Informations

Folgende Module beinhaltet das IP Address Informations Repository:

- __IP_Info__ ([Dokumentation](IP_Info))  
	Kurze Beschreibung des Moduls.

- __IP_Stack__ ([Dokumentation](IP_Stack))  
	Kurze Beschreibung des Moduls.

- __Whois__ ([Dokumentation](Whois))  
	Kurze Beschreibung des Moduls.


	Reload Module

	
MC_ReloadModule("{D4AAC276-97DB-E833-6C3A-A5B908436016}","InetInfo");


var_dump(MC_GetModuleList(45598));
//var_dump(MC_GetModuleRepositoryInfo(45598));


/* https://www.symcon.de/service/dokumentation/installation/migration-v40-v41/

PHP Module
Auswahl von Branches im Module Control
Anzeige der Repository URL im Module Control
Übersetzung von PHP-Module (Beispiel auf GitHub)
Dynamische Konfigurationsformulare (Beispiel auf GitHub)
Liste der verfügbaren Nachrichten im MessageSink
$this->MessageSink($TimeStamp, $SenderID, $Message, $Data); (Beispiel auf GitHub)
$this->SetSummary($Summary);
$this->RegisterMessage($SenderID, $Message); (Beispiel auf GitHub)
$this->UnregisterMessage($SenderID, $Message); (Beispiel auf GitHub)
$this->SetBuffer($Name, $Data); (Beispiel auf GitHub)
$this->GetBuffer($Name); (Beispiel auf GitHub)
$this->SetReceiveDataFilter($RequiredRegexMatch); (Beispiel auf GitHub)
$this->SetForwardDataFilter($RequiredRegexMatch);
$this->Destroy();
MC_ReloadModule($module_control_id, "ModulName"); (Lädt das PHP Modul neu und erstellt auch die Instanzen neu)

*/