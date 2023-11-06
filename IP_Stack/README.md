# IP_Stack
Beschreibung des Moduls.

### 1. Funktionsumfang

const INSTANCE_ID = 49012;

echo "\n# Get country and continent for IP as String\n";
echo NETINFO_GetIPStackAsString(INSTANCE_ID, "8.8.8.8", "country_name,continent_name", "\n");

echo "\n\n# Get all IP Infos as String\n";
echo NETINFO_GetIPStackAsString(INSTANCE_ID, "8.8.8.8", "ALL", "\n");

echo "\n\n# Get all IP Infos as Json String\n";
echo NETINFO_GetIPStackAsString(INSTANCE_ID, "8.8.8.8", "json", "x");

echo "\n\n# Get all IP Infos as Array\n";
$ipInfo = NETINFO_GetIPStack(INSTANCE_ID,"8.8.8.8"); var_dump($ipInfo);


/* Web Hook Requests

* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpStack=8.8.8.8&format=region_name&delimiter=;%20
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpStack=8.8.8.8&format=country_code,country_name,continent_name&delimiter=;%20 
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpStack=8.8.8.8&format=latitude,longitude&delimiter=;%20 
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpStack=8.8.8.8&format=json&delimiter=;%20
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpStack=8.8.8.8&format=location&delimiter=;%20

*/

API Usage: 100 per Month