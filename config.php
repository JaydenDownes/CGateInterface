<?php
/* Copyright 2011 Greig Sheridan
This file is part of C-ChangePHP
C-ChangePHP is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
C-ChangePHP is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with C-ChangePHP.  If not, see <http://www.gnu.org/licenses/>.
*/



// If you're working off-line (aka "demo mode"), set this to '1' to skip all attempts at communicating with C-Gate.
// Change to a 0 to attempt to communicate with the C-Gate server as defined below.
// e.g. $demo = 1; 
$demo = 0;



// Place the IP address or domain name of your C-Gate server in between the quotation marks.
// Leave the string empty if your C-Gate server is the same as the WAMP host.
// e.g. $cgate = "";
// e.g. $cgate = "192.168.1.12";
// e.g. $cgate = "c-gate.mydomain.com";
$cgate = "";



// This is the file and path of your C-Gate tags file.
// replace my "home.xml" with your own project name
// e.g. $tagsfile = "C:\\Clipsal\\C-Gate2\\tag\\home.xml";
$tagsfile = "C:\\Clipsal\\C-Gate2\\tag\STANFOR2.xml";


// This is the file and path to a Comfort Alarm Panel configuration file. 
// If one is attached to Cbus it can be used to populate the Zone Names until Comfort supports the 'Zone Name Query' command.
// e.g. $comfortfile = "C:\\Comfort\\ComfortFile.cclx";
$comfortfile = "";



// This is the name of your C-Bus project file - the top level node in Toolbox.
// (If left blank, we'll assume the Project Name is the same as the filename of the tags file)
// e.g. $project = "19P";
$project = "STANFOR2";



// This is the name of your C-Bus Network. (The default value for networks is 254 - most installations won't need to change this).
// e.g. $network = "254";
$network = "52";

?>