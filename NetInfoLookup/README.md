# NetInfo Lookup Module
Beschreibung des Moduls.


### 1a. ipinfo.io :: Funktionsumfang

echo "\n# Get hostname and country for IP as String\n";
echo NETINFO_GetIPInfoAsString(INSTANCE_ID, "8.8.8.8", "hostname,country", "\n");

echo "\n\n# Get all IP Infos as String\n";
echo NETINFO_GetIPInfoAsString(INSTANCE_ID, "8.8.8.8", "ALL", "\n");
echo "\n - - - - - - - - - -\n";

echo "\n\n# Get all IP Infos as Json String\n";
echo NETINFO_GetIPInfoAsString(INSTANCE_ID, "8.8.8.8", "json", "\n");

echo "\n\n# Get all IP Infos as Array\n";
$ipInfo = NETINFO_GetIPInfo(INSTANCE_ID,"8.8.8.8"); var_dump($ipInfo);


/* Web Hook Requests

IPv4
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=ALL
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=ALL&delimiter=;%20
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=country,org
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=country,region,city,org,hostname&delimiter=%20
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=ValueOnly
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=org,ValueOnly
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=country,region,city,org,hostname,ValueOnly&delimiter=|
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=8.8.8.8&format=json

IPv6
* http://127.0.0.1:3777/hook/NetInfoLookup?GetIpInfo=2001:4860:4860::8888&format=ALL&delimiter=;%20

*/

Rate Limits
Free usage of our API is limited to 50,000 API requests per month. If you exceed that limit, we'll return a 429 HTTP status code to you.


### 1b. ipstack.com :: Funktionsumfang

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


### 1c. macaddress.io :: Funktionsumfang

echo "\n# Get countryCode and companyName for MAC Adress Vendor as String\n";
echo NETINFO_GetMACadressInfoAsString(INSTANCE_ID, "44:38:39:ff:ef:57", "countryCode,companyName", "\n");

echo "\n\n# Get  countryCode and companyName 'ValueOnly' for MAC Adress Vendor as String\n";
echo NETINFO_GetMACadressInfoAsString(INSTANCE_ID, "44:38:39:ff:ef:57", "ValueOnly,countryCode,companyName", " - ");

echo "\n\n# Get all MAC Adress Infos as String\n";
echo NETINFO_GetMACadressInfoAsString(INSTANCE_ID, "44:38:39:ff:ef:57", "ALL", "\n");

echo "\n\n# Get all MAC Adress Infos as Json String\n";
echo NETINFO_GetMACadressInfoAsString(INSTANCE_ID, "44:38:39:ff:ef:57", "json", "x");

echo "\n\n# Get all MAC Adress Infos as Array\n";
$macAdressInfo = NETINFO_GetMACadressInfo(INSTANCE_ID,"44:38:39:ff:ef:57"); var_dump($macAdressInfo);


/* Web Hook Requests

* http://127.0.0.1:3777/hook/NetInfoLookup?GetMacVendor=44:38:39:ff:ef:57
* http://127.0.0.1:3777/hook/NetInfoLookup?GetMacVendor=44:38:39:ff:ef:57&format=json

*/




Notes:
Other API's >> https://ipstack.com/other
https://ipapi.com/                 :: Real-time Geolocation & Reverse IP Lookup REST API
https://userstack.com/             :: Detect any Browser, Device & OS in Real-Time
https://positionstack.com/         :: Accurate Forward & Reverse Batch Geocoding REST API
https://countrylayer.com/          :: Reliable real-time country data API for your business.
https://languagelayer.com/         :: Powerful Language Detection JSON API for Developers

