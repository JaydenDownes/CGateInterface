<?php
/* Common C-Bus procedures.
Copyright 2010, 2011, 2012 Greig Sheridan
This file is part of C-ChangePHP
C-ChangePHP is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
C-ChangePHP is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with C-ChangePHP.  If not, see <http://www.gnu.org/licenses/>.
*/

$channel = array();
$ch_value  = "";

function read_tags(){
	global $tagsfile;
	global $comfortfile;
	global $project;
	global $network;
	$NetworkNumber = "";
	$AppName = "";
	$AppAddress = "";
	$AppList = array();
	$AppNames = array();
	$valid_app = 0	;

	if (!(isset($project))) {
		// Not set? Set it
		$project = "";
	}
	if (!(isset($network))) {
		// Not set? Set it
		$network = "254";
	}
	if (!(isset($tagsfile))) {
		// Not set? Set it to "Home" - the demo tag file that comes with C-Gate.
		$tagsfile = "C:\\Clipsal\\C-Gate2\\tag\\home.xml";
	}	
	if (strlen($tagsfile)){
		//Good.
	} else {
		// An empty string probably means the user's not set the value in config.php
		$tagsfile = "C:\\Clipsal\\C-Gate2\\tag\\home.xml";
	}
		
	if (file_exists($tagsfile)) {
		if (strlen($project)){
			// Good - we'll assume it's OK
		} else {
			// Bad - project is an empty string
			// Copy the tagsfile name across
			$value = preg_match("/\\w*\.xml$/i", $tagsfile, $matches);
			list($project, $extension) = explode('.', $matches[0]);
			//echo ($project . " <br>");
		}	
		$xml = simplexml_load_file($tagsfile);
		foreach($xml->xpath("//Network") as $xml_net) { // Read all of the NETWORKS in turn.
			$NetRow = simplexml_load_string($xml_net->asXML()); // Look at the first ROW - this is the first NETWORK in its entirety.
			$NetworkNumber = (string) $xml_net->NetworkNumber;
			//echo "<br><br>-- Found network number " . $NetworkNumber. "<br>";
			$NetList[]= $NetworkNumber;	//Add to the array of available networks
			foreach($NetRow->xpath('//Application') as $xml_app) { // Read all of the APPLICATIONS in turn.
				$AppRow = simplexml_load_string($xml_app->asXML()); // Look at the first ROW - this is the first APP in its entirety.
				$AppName = (string) $xml_app->TagName;
				$AppAddress = (string) $xml_app->Address;
				//echo $AppName . " is at address ". $AppAddress. "<br>";
				$valid_app = 0; // Reset the flag
				foreach($AppRow->xpath('//Group') as $xml_grp) { 
					$row = simplexml_load_string($xml_grp->asXML()); 
					$v = $row->xpath('//TagName'); 
					if($v[0]){ 
						$tempTag = (string) $xml_grp->TagName;
						$TagAddress = (string) $xml_grp->Address;
						if (($TagAddress == '255') && ($tempTag == "<Unused>")) {
							//echo ("DISCARDED Tagname is " . $tempTag . " and address is " . $TagAddress . "<br>");
						} else {
							// The above strips any Tags with address = 255 and tagname = "&lt;Unused&gt;"
							$valid_app = 1; // Sets a flag.
							$_SESSION["tag[$NetworkNumber][$AppAddress][$TagAddress]"] = $tempTag;
							//echo ("RETAINED  Tagname is " . $tempTag . " and address is " . $TagAddress . "<br>");
						}
					} 
				}
				//Did this App have any active groups? If not, discard it from the list
				if ($valid_app == 1) {
					$AppList[$NetworkNumber][] =  $AppAddress;	//Add Application addresses to an array
					$AppNames[$NetworkNumber][$AppAddress] = $AppName;  //Add Application names to an array
				}
			}
		}
	$_SESSION["Nets"] = $NetList;	//Copy the Application array to a Session variable
	$_SESSION["Apps"] = $AppList;	//Copy the Application array to a Session variable
	$_SESSION["AppNames"] = $AppNames; //Copy the Application array to a Session variable
	$_SESSION["Project"] = $project;
	$_SESSION["Network"] = $network;
	} else {
		echo('Failed to open tagsfile ' .$tagsfile . '<br>');
		// No tags file?? Yikes. check we have a Project, otherwise bomb.
		if (strlen($project)) {
			//OK, fingers crossed
		} else {
			echo("No tags file and no project. Check config.php");
			die;
		}
	}
	if (isset($comfortfile)){
		if (strlen($comfortfile)){
			//Just because the variable's set doesn't mean it's not null
			if (file_exists($comfortfile)) {
				$xml = simplexml_load_file($comfortfile);
				foreach ($xml->Zones[0]->Zone as $Zone) {
					$ZoneList[] = (string) $Zone['Name'];
				}
				$_SESSION["Zones"] = $ZoneList;
			} else {
			echo('Failed to open comfortfile ' .$comfortfile);
			}
		}
	}
}

function read_bus($passed_command)	{
	global $project;
	global $network;
	$transmit = "";
	
	if (!strlen ($passed_command)){
		//If an empty string is passed then we want the lot! Add each network & app number to the string and let the Switch statements pad it out.
		$appList = ($_SESSION["Apps"]);
		$Networks = $_SESSION["Nets"];		
		foreach ($Networks as &$NetNum) {	//Loop through all of the available networks
			foreach ($appList[$NetNum] as &$AppNum) {
				$passed_command .=  $NetNum. "/" . $AppNum . ",";
			}
		}
	}
	$one_command = strtok ($passed_command, ",");
	while ($one_command !== false) {
		// Add any required Project and Network values to each passed 'commandlet'.
		// The final commandlet sent to the bus must = "get //<project>/<network>/<app>/* level" 
		switch (substr_count ($one_command, "/")){
			case 0 :	// It's just an Application. Prepend "get //<project>/<network>/" 
				$one_command = "get //" . $project . "/" . $network . "/" . $one_command . "/* level";
			break;
			case 1 :	// It's the Network and App. Prepend "get //<project>/" 
				$one_command = "get //" . $project . "/" . $one_command . "/* level";
			break;
			case 2 :	// It's the lot. Prepend "get //" 
				$one_command = "get //" . $one_command . "/* level";
			break;
			case 3 :
				// Might be invalid. If the first char is a "/" and the second ISN'T, add a leading slash, otherwise discard.
				// TEMP: Assume the first char is a slash:
				$one_command = "get /" . $one_command . "/* level";
			break;
			case 4 :	// It's the lot - with slashes. Prepend "get " 
				$one_command = "get " . $one_command . "/* level";
			break;
			case 5 :	// It's the complete command.
				//Do nothing - it should be good to go
			break;
			default :
				//Discard. It didn't look acceptable.
				$one_command = "";
			break;
		}
		//At this point the command is OK to transmit.
		
		// Check the TRIGGER GROUP hasn't snuck in - and if so, delete it. (You can't query it).
		if (strpos($one_command, "/202/* level" )){
			//echo "Yes it fired<br>";
		} else {
			if (strpos ($one_command, "/203/* level")) {
				//echo "Enable Control Detected.";
				$one_command = "tree " . "//" . $project . "/" . $network;
			}
			$transmit .= $one_command . ",";
		}
		$one_command = strtok (",");
	}
	//echo ("Transmit = " . $transmit);
	//die;
	$response = send($transmit);	//The response from the bus will be parsed into session memory later...
}

	

function send($msg){
	global $demo;
	global $cgate;
	global $Enable_Control;
	$send_str = array();
	$demo_proj = ""; // Used to fake up a response in the same Project as the received command.
	$demo_net = "";  // Used to fake up a response in the same Network as the received command.
	$demo_app = "";  // Used to fake up a response in the same 	 App   as the received command.
	
	if (!isset($demo)){
		$demo = 1; // If $demo is not set, force it to 1
	}
	if (!isset($cgate)){
		$cgate = ""; // If $cgate is not set, force it to locahost (blank)
	}
	$retval = "";
	//----------------------------------------------------------------------------------------
	If ($demo == 1) {	//Then we're in DEMO MODE. Fake up a response - do not talk to C-Gate.
		//Demo mode
		if ((strpos($msg, " level") !== false)) {
			//This is a "get" command to read from the  bus: "get //19P/254/56/* level,"
			//Decode the message to determine the Network and Application
			//First, determine the proj, net & app from the incoming string: We will *always* receive "300 //<Project>/<Network>/<App>..." in the string
			$msg = substr ($msg, (strpos ($msg, "get //"))); 	// Strip anything that might come before "get //" - although there shouldn't be.
			$msg = str_replace (",", "", $msg);					//Strip "," if there happens to be one/any
			$msg = str_replace ("get //", "", $msg);			// Strip the "get //"
			$demo_proj = substr ($msg, 0, (strpos ($msg, "/")));		
			$msg = substr ($msg, (strlen($demo_proj)+1));
			$demo_net = substr ($msg, 0, (strpos ($msg, "/")));		
			$msg = substr ($msg, (strlen($demo_net)+1));
			$demo_app = substr ($msg, 0, (strpos ($msg, "/")));		
			//$msg = substr ($msg, (strlen($demo_proj)+1));

			// Now use these values to fake up a response for ALL Groups in the XML file:
			$result = "400 Syntax Error. ";
			for ($loop = 0; $loop < 255; $loop++) {	// Read all of the groups in this App
				if (isset ($_SESSION["tag[$demo_net][$demo_app][$loop]"])) {	// Only populate those that have a name attached
					$random = rand (0,2); // Generate a random number between 0 and 2.
					if ($random == 1) $random = 128;
					if ($random == 2) $random = 255;
					$result .= "300-//" . $demo_proj . "/" . $demo_net . "/" . $demo_app . "/" . $loop . ": level=" . $random . "\r";
				}
			} 
			$result .= "300 //" . $demo_proj . "/" . $demo_net . "/" . $demo_app . "/254: level=0\r";	//Final value is used by parse_result
			//echo ("We're about to pass this | " . $result . "|<br>");
			//die;
			parse_response($result);	//This sends the result to a function to populate session variables.
		} else {
			// All other commands from the calling code we'll ignore - assuming them to be change requests. Just return "OK"
			$retval = "OK";
		}
	//----------------------------------------------------------------------------------------
	} else {
		//Live (online) mode
		$telnet = new PHPTelnet();
		$telnet->show_connect_error=0;
		// if the first argument to Connect is blank,
		// PHPTelnet will connect to the local host via 127.0.0.1
		$result = $telnet->Connect($cgate,'','');
		switch ($result) {
		case 0: 
			//echo 'We connected OK' . '<br>';
			
			// I previously used "strtok" here but it stopped working, presumably because "parse_response()" now uses it too.
			//So now I convert $msg to an array and then step through each element therein.
			$send_str = preg_split('/,/', $msg , 0, PREG_SPLIT_NO_EMPTY);
			foreach ($send_str as &$each_msg) {
				//echo ("Each message is |" . $each_msg. "|<br>");
				if (preg_match('/^tree/', $each_msg)) {
					//echo ("Enable Control Detected" . "<br>");
					$Enable_Control = TRUE;
				} else {
					$Enable_Control = FALSE;
				}
				$telnet->DoCommand($each_msg, $result);
				parse_response($result);	//This sends the result to a function to populate session variables.
			}
			// say Disconnect(0); to break the connection without explicitly logging out
			$telnet->Disconnect(0);
			//echo '<br>OK, disconnected now<br>';
			return $result;
		break; 
		case 1:
			echo '[C-ChangePHP] Connect failed: Unable to open network connection';
		break; 
		case 2:
			echo '[C-ChangePHP] Connect failed: Unknown host';
		break; 
		case 3:
			echo '[C-ChangePHP] Connect failed: Login failed';
		break; 
		case 4:
			echo '[C-ChangePHP] Connect failed: Your PHP version does not support PHP Telnet';
		break; 
		}
	}
		//echo 'WE GOT TO HERE';
		return $retval;
}


function parse_response ($received){
	global $status;
	global $Enable_Control;
	$project = "";	//LOCAL project  variable - as decoded from incoming string. *NOT* the global one from the config.php file!
	$network = "";	//LOCAL network  variable - as decoded from incoming string. *NOT* the global one from the config.php file!
	
	if (strpos($received, "401 Bad object or device ID")) {
		//We queried a bad (spare) application. Disregard
		return;
	}
	if (strpos($received, "200 OK")) {
		//We sent a command and this was the total response. Nothing to do here this time.
		return;
	}	
	if ((strpos($received, "level")) === 0) {
		//The absence of "level" indicates an error.
		//echo ("<br><br>ERROR. Return value = |" . $received . "|<br>");
		//die;
		return;
	} else {
		if (!$Enable_Control) {
			//echo ("<br>Not an error. Return value: |" . $received . "|<br>");
			//die;
			//First, determine the project and network from the incoming string: We will *always* receive "300 //<Project>/<Network>/..." in the string
			$network = substr ($received, (strpos ($received, "300 //")));
			$network = str_replace ("300 //", "", $network);
			$project = substr ($network, 0, (strpos ($network, "/")));
			$network = substr ($network, (strlen($project)+1));
			$network = substr ($network, 0, strpos ($network, "/"));
			//Now strip the project and network from the response, to leave just the raw
			$received = str_replace (("//". $project . "/". $network . "/"), "", $received);//    Strip the "//<Project>/<Network>/" to leave just "56/87: level=0"
			$received = str_replace ("300", "", $received);					//The FINAL channel returns "300 //<Project>/<Network>/56/88: level=0"
			$received = str_replace ("-", "", $received);					//
			$received = str_replace (": level", "", $received);
			$received = str_replace ("400 Syntax Error.", "", $received);
			$received = str_replace ("\n", "", $received);
			//Echo ("<br>Not an error. Return value: |" . $received . "|<br>");
			$each_ch = strtok ($received, "\r");

		} else {
			//echo ("Do the Enable Control Parsing here." . "<br>");
            $network = substr ($received, (strpos ($received, $project . "320-//")));
            $network = str_replace ("320-//", "", $network);
            $project = substr ($network, 0, (strpos ($network, "/")));
            $network = substr ($network, (strlen($project)+1));
            $network = substr ($network, 0, strpos ($network, "/"));
            $local_value = preg_match_all("/203\/.*level=[0-9]{1,3}/i", $received, $matches);
            $received = implode(" ", $matches[0]);
            $received = preg_replace('/ \(\$[0-F]{1,3}\) level/i', "", $received);
            $received = str_replace ("\n", "", $received);
            //Echo ("<br>Last exit string. Return value: |" . $received . "|<br>");
            $each_ch = strtok ($received, " ");
		}
		while ($each_ch !== false) {
			//Explode here.
			$each_ch = str_replace (" ", "" , $each_ch);	//The final one has a space in it!			
			$intensity = substr ($each_ch, (strpos ($each_ch, "=")));
			$each_ch = str_replace ($intensity, "", $each_ch); // Strip the "=<intensity>" off the end of the string, leaving just "56/77"
			$intensity = str_replace ("=", "" , $intensity);	//Strip the "="
			$group = substr ($each_ch, (strpos ($each_ch, "/")));
			$each_ch = str_replace ($group, "", $each_ch); // Strip the "/<group>" off the end of the string, leaving just "56"
			$group = str_replace ("/", "" , $group);	//Strip the "/"
			$application = $each_ch;
			//echo ("App " . $application . " G " . $group . " @ I of " . $intensity . "<br>");			
			$status[$network][$application][$group] = $intensity;
			//echo ('|' . $each_ch . '| delivers Group ' . $group . ' & a value of ' . $status[$group] . '<br>');
			$each_ch = strtok (",\r");
		}
	}
}



/*
FORMAT OF XML IN C-GATE TAGS FILE:
<?xml version="1.0" encoding="utf-8"? >
<Installation>
	<DBVersion>2.2</DBVersion>
	<Version>1.0</Version>
	<Modified>2006-02-01T16:55:44.953+11:00</Modified>
	<Project>
		<TagName>19P</TagName>
		<Address>19P</Address>
		<Description>C-Bus project generated from live network by C-Gate v2.3.44 (build 2077)</Description>
		<Network>
			<TagName>Location</TagName>
			<Address>254</Address>
			<NetworkNumber>254</NetworkNumber>
			<Interface>
				<InterfaceType>CNI</InterfaceType>
				<InterfaceAddress>192.168.019.104:14000</InterfaceAddress>
			</Interface>
			<Application>
				<TagName>Lighting</TagName>
				<Address>56</Address>
				<Group>
					<TagName>&lt;Unused&gt;</TagName>
					<Address>255</Address>
				</Group>
				## ... etc ##
				<Group>
					<TagName>Bedroom LX</TagName>
					<Address>32</Address>
				</Group>
			</Application>
		</Network>
	</Project>
	<InstallationDetail>
		<SystemLocation>[unknown]</SystemLocation>
		<HardwarePlatform>[unknown]</HardwarePlatform>
		<Hostname>nibbler</Hostname>
		<OSName>Windows XP</OSName>
		<OSVersion>5.1</OSVersion>
		<HardwareLocation>[unknown]</HardwareLocation>
		<MaintenanceEmail>[unknown]</MaintenanceEmail>
		<Installer>
			<Name>[unknown]</Name>
		</Installer>
	</InstallationDetail>
</Installation>
*/
?>




