# IP Address Informations

Folgende Module beinhaltet das IP Address Informations Repository:

- __IP_Info__ 
	Kurze Beschreibung des Moduls.

- __IP_Stack__
	Kurze Beschreibung des Moduls.

- __Whois__  
	Kurze Beschreibung des Moduls.

- __NetInfoLookup__ 
	Kurze Beschreibung des Moduls.




Reload Module
MC_ReloadModule($moduleControlID, "BibliotheksName");
MC_ReloadModule("{D4AAC276-97DB-E833-6C3A-A5B908436016}","Network Tools and Infos");

https://gist.github.com/paresy/387b18441058cb68d448016540e3e28e
function MC_CreateModule(int $InstanceID, string $ModuleURL) { return true; }
function MC_DeleteModule(int $InstanceID, string $Module) { return true; }
function MC_GetModule(int $InstanceID, string $Module) { return Array(); }
function MC_GetModuleList(int $InstanceID) { return Array(); }
function MC_GetModuleRepositoryInfo(int $InstanceID, string $Module) { return Array(); }
function MC_GetModuleRepositoryLocalBranchList(int $InstanceID, string $Module) { return Array(); }
function MC_GetModuleRepositoryRemoteBranchList(int $InstanceID, string $Module) { return Array(); }
function MC_IsModuleClean(int $InstanceID, string $Module) { return true; }
function MC_IsModuleUpdateAvailable(int $InstanceID, string $Module) { return true; }
function MC_IsModuleValid(int $InstanceID, string $Module) { return true; }
function MC_ReloadModule(int $InstanceID, string $Module) { return true; }
function MC_RevertModule(int $InstanceID, string $Module) { return true; }
function MC_UpdateModule(int $InstanceID, string $Module) { return true; }
function MC_UpdateModuleRepositoryBranch(int $InstanceID, string $Module, string $Branch) { return true; }
.