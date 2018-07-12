<?php
/* Copyright 2010, 2011, 2012 Greig Sheridan
This file is part of C-ChangePHP
C-ChangePHP is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
C-ChangePHP is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with C-ChangePHP.  If not, see <http://www.gnu.org/licenses/>.
*/
session_start();
require_once "PHPTelnet.php";
require_once "config.php";
require_once "c-bus_common.php";

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name = "viewport" content = "width=device-width">
<meta name="apple-mobile-web-app-capable" content="yes"/>
<title>C-Bus Control</title>

<link rel="stylesheet" type="text/css" href="assets/style.css" />
<link rel="stylesheet" type="text/css" href="_resource/cbus.css">
<link rel="shortcut icon" type="image/x-icon" href="_resource/favicon.ico" />
<script type="text/javascript" charset="utf-8" src="_resource/js/jquery.min.js"/></script>
<script type="text/javascript">  


// remap jQuery to $
(function($){})(window.jQuery);

function selectApp(targetRadio) {



	// fake the toggling of checkboxes when clicking on status bar buttons or shed map hotspots
	if($('#'+targetRadio).is(':checked')) {


		$('#'+targetRadio).prop('checked', false);
	} else {




		$('#'+targetRadio).prop('checked', true);
	}
	$(this).click(function(event) {
		// event.preventDefault(); 
		// prevent the default behaviour of clicking on the link (eg jumping to the top of the page because the href is "#" )
	});
}

/* trigger when page is ready */
$(document).ready(function (){

	// your functions go here
	$("#status .statusbutton").toggle( // find all buttons on the status bar and toggle the classes when clicked 
		function() {
			$(this).addClass('checked');
		}, 
		function() {
			$(this).removeClass('checked');
		}
	);
	
	$("#building .toggle").toggle( // find all hotspots on the shed map and toggle the classes when clicked 
		function() {
			$(this).addClass('checked');
		}, 
		function() {
			$(this).removeClass('checked');
		}
	);
	

});
</script>
</head>
<body>
<noscript><h3>Your browser does not support JavaScript! This page will not work correctly.</h3></noscript>
<?php 
if(isset($_GET["ramp"])) {
	$ramp = ($_GET["ramp"]);
	//echo "<br />We got ramp = " . $ramp . "<br />";
} else {
	$ramp = "4";
}

if(isset($_GET["level"])) {
	$level = ($_GET["level"]);
	//echo "<br />We got level = " . $level . "<br />";
} else {
	$level = "60";
}

if(isset($_GET["gettags"])) {	// Force a reload of the tags and session variables. (For easier debugging)
	session_unset();	
	read_tags();
}

if((isset($_SESSION['got_tags'])) && (isset($_SESSION['Project'])) && (isset($_SESSION['network']))) {
	// Then we're good to go
} else {
	read_tags();
	$_SESSION['got_tags'] = 1;
}

if(isset($_GET["page"])) {
	$page = ($_GET["page"]);
	//echo "<br />We saw Page, so must be on the Table View"<br />";
} else {
	$page = 0;
}

if ($page<>0) {
	$page = 1;
}

$errcode = "";	//We'll populate this if there are problems accessing C-bus or the decoding of the RxD
$temp = 0;
$channel = array();
$ch_value  = "";
if(isset($_POST["page"]))
	{
	// Now redirect back to itself:
	$self = $_SERVER['SCRIPT_NAME'];
	if ($page == 1) {
		header("Location: $self?ramp=" . $ramp . "&level=" . $level . "&page=0");
	} else {
		header("Location: $self?ramp=" . $ramp . "&level=" . $level . "&page=1");
	}
}

if(isset($_POST["state"])) {

	// "state" WAS set. The user submitted the form on the page - so we have a change to send to the bus.
	// Let's find out what button was pressed and what (if any) of the other fields were completed (e.g. check-boxes, "specify", and 
	// the "ramp (time)" or "intensity (percentage)" selections.
	
	if (isset($_POST["ramp"])) {
		$ramp = ($_POST["ramp"]);   // This is to be echoed back to the next page
		$tx_ramp = ($_POST["ramp"]);// ... whilst this is what we transmit
	}
	if ($ramp == "") {
		$ramp= '0';
		$tx_ramp = '0';
	}
	
	$specify = ""; 
	
	switch ($_POST['state']) {
			case "on" :
			case "On" :
				$tx_level = '100';
				$tx_ramp = '0';
			break;	
			case "off" :
			case "Off" :
				$tx_level = '0';
				$tx_ramp = '0';
			break;	
			case "ramp" :
			case "Ramp" :
				if (isset($_POST["level"])) {
					$level = ($_POST["level"]);	// Like above for 'ramp' - we need to remember this to pass back to the next page
					$tx_level = ($_POST["level"]);	// ... and this is the value we transmit to the bus.
				}
				if ($level == "") {
					$level = '0';
					$tx_level = '0';
				}
				// "Specify" overrides the value you might have selected on the drop-down
				if (strlen ($_POST["specify"])) {
					$specify = ($_POST["specify"]);
					//$level = ($_POST["specify"]);	// Like above for 'ramp' - we need to remember this to pass back to the next page
					if (ctype_digit($specify) ) {
						// So the value contains only digits
						if ($specify < 0) {
							$specify = 0;  //Set to 0 if value smaller than 0
						} else {
							if ($specify > 255) {
								$specify = 255;  // Set to 255 if value higher than 255
							}
						}
					} else {
						// Specify is invalid
						$specify = "";
					}
				}
			break;
			//case "redraw" :
			//	We actually handle redraw later, emptying the "$transmit" string.
			//break;
			default :
				// Only here for debugging:
				//echo ("default: " . $_POST['state']);
				//die;
			break;
	}

	// This is where we read through the channels (checkboxes) array to see what buttons were pressed
	if (isset($_POST['channel'])){ 
			$channel = implode(",", $_POST['channel']); // format your array for use 
		} else {
			// flush the array echo "text";
			$channel = "";
		}

	// This is where we decode the channels (checkboxes) back into their command strings for transmission:
	$transmit = "";	// flush and declare the variable
	$each_ch  = "";
	if (strlen($specify)){
		//Then we use the level already in $specify (0-255)
		$binary = $specify;
	} else {
		$binary = round($tx_level * 255 / 100);
	}
	if ($binary < 1) {
		$binary = 0;
		// (Just in case - sets a minimum level)
	}
	$each_ch = strtok ($channel, ",");
	while ($each_ch !== false) {
		if (!(strpos ($each_ch, "202/") === false )){
            // Then it's the trigger group - use correct syntax:
            $transmit = $transmit . "TRIGGER EVENT //" . $project . "/" . $network . "/" . $each_ch . " " . $binary . ",";
        } else if (!(strpos ($each_ch, "203/") === false )) {
            //    echo "Enable Control command sent.";
            $transmit = $transmit . "ENABLE SET //" . $project . "/" . $network . "/" . $each_ch . " " . $binary . ",";
            //    echo $transmit;
            //    die;
        } else {
            //Everything else is OK with "ramp".
            $transmit = $transmit . "ramp //" . $project . "/" . $network . "/" . $each_ch . " " . $binary . " " . $tx_ramp . ",";
		}
        $each_ch = strtok (",");
	}
	
	if (($_POST['state']) == "redraw"){
		$transmit = ""; //Empty out anything that might be in the transmit string - causes TX to abort and we re-draw!
	}
	
	if ($transmit == ""){
		// Do nothing - no command selected
	} else {
		$RetVal = send($transmit);
		if ((strpos($RetVal, 'OK') === false)) {
			if (!(strpos($RetVal, '408 Operation failed:') === false)) {
				//C-gate isn't ready.
				echo '[C-ChangePHP] Command failed: C-Gate not sync\'d to the network. Wait and repeat';
				die;
			} else {
				echo ("<br /><br />ERROR. Return value = " . $RetVal . "<br />");
				die;
			}
		}
	}
	
	// Now redirect back to itself:
	$self = $_SERVER['SCRIPT_NAME'];
	header("Location: $self?ramp=" . $ramp . "&level=" . $level . "&page=" . $page);

} else {

	// "state" was NOT set. The user did NOT submit the form to get to this point.
	// This section runs when the page was loaded 'cleanly', from either a Refresh, a Re-draw, or anew
	// It queries the bus (immediately beneath this comment), then draws the table to the page, then ends, waiting for
	// the user to press a button.

	read_bus("56");	// This returns after having read from the 'bus with all of the Group values ready for the code below to format and display. 
					// If an empty string (e.g. "") is passed, func read_bus will query *ALL* networks and apps from the tags.XML file
					// If only an App (e.g. "56") is specified, func read_bus will pad with <Project> and <Network> from config.php
					// If Network and App (e.g. "254/56") are specified, func read_bus will pad with <Project> from config.php
					// If Project, Net and App (e.g. "19P/254/56") are specified, func read_bus will not pad
					// If an entire command string is specified (e.g. "get 19P/254/56/* level"), func read_bus will transmit it verbatim

					// Multiple commands (e.g. "56,254/202,get 19P/254/56/* level") can be passed here, each comma-separated. 
					// They will be processed within the same Telnet session to the bus, but treated individually and subject to the formatting as outlined above.
	?>

	<div id="container">
	<form action="" method="post">
		<div id="maincontent">
			<?php
			if ($page == 0) { //page 0 is graphical view, page 1 is table view
				?>
			<div id="status">
                <?php // buttons(254,56,38,"Fan",1)?>
				<?php // buttons(254,56,50,"Alarm",1)?>
				<?php // buttons(254,56,42,"Porch Light",1)?>
				<?php // buttons(254,56,30,"Floor Heat",1)?>
				<?php // buttons(254,56,20,"Darkness",0)?>
				<div class="secondary_tools">
					<input type="submit" name="page" value="<?php if ($page == "1")  {echo "Picture View";} else {echo "Table View";} ?>">
					<input type="submit" name="state" value="Redraw">
				</div> <!-- secondary_tools -->
			</div> <!-- status -->
				<div id="building">
					<div id="image-container">
						
<!--  <img draggable="false" src="assets/walls.svg" id="walls" /> -->

   

				
						<!-- SYNTAX display(Network,Group address, room name) -->
						<?php display(52,56,33,"node1")?>
                        <?php display(52,56,34,"node2")?>
                        <?php display(52,56,49,"node3")?>
                        <?php display(52,56,35,"node4")?>
                        <?php display(52,56,17,"node5")?>
                        <?php display(52,56,19,"node6")?>
                        <?php display(52,56,24,"node7")?>
                        <?php display(52,56,16,"node8")?>
                        <?php display(52,56,16,"node9")?>
                        <?php display(52,56,7,"node10")?>
                        <?php display(52,56,55,"node11")?>
                        <?php display(52,56,55,"node12")?>
                        <?php display(52,56,55,"node13")?>
                        <?php display(52,56,80,"node14")?>
                        <?php display(52,56,8,"node15")?>
                        <?php display(52,56,25,"node16")?>
                        <?php display(52,56,29,"node17")?>
                        <?php display(52,56,81,"node18")?>
                        <?php display(52,56,81,"node19")?>
                        <?php display(52,56,30,"node20")?>
                        <?php display(52,56,30,"node21")?>
                        <?php display(52,56,57,"node22")?>
                        <?php display(52,56,32,"node23")?>
                        <?php display(52,56,57,"node24")?>
                        <?php display(52,56,58,"node25")?>
                        <?php display(52,56,58,"node26")?>
                        <?php display(52,56,58,"node27")?>
                        <?php // display(52,56,57,"node28") // Outside Lights North - Removed ?>
                        <?php display(52,56,90,"node29")?>
                        <?php display(52,56,27,"node30")?>
                        <?php display(52,56,27,"node31")?>
                        <?php display(52,56,47,"node32")?>
                        <?php display(52,56,47,"node33")?>
                        <?php display(52,56,28,"node34")?>
                        <?php display(52,56,41,"node35")?>
                        <?php display(52,56,57,"node145")?>
                        <?php display(52,56,57,"node146")?>
				

					</div> <!-- image-container -->
				</div> <!-- building -->
	
			<div style="display:none">
				<p><input type="checkbox" name="channel[]" value="56/33" id="select_33">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/34" id="select_34">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/49" id="select_49">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/35" id="select_35">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/17" id="select_17">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/19" id="select_19">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/24" id="select_24">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/16" id="select_16">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/7" id="select_7">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/55" id="select_55">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/80" id="select_80">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/8" id="select_8">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/25" id="select_25">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/29" id="select_29">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/81" id="select_81">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/30" id="select_30">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/57" id="select_57">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/32" id="select_32">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/58" id="select_58">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/90" id="select_90">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/27" id="select_27">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/47" id="select_47">Study LED Lights 2</p>
                <p><input type="checkbox" name="channel[]" value="56/28" id="select_28">Study LED Lights 2</p>
				<p><input type="checkbox" name="channel[]" value="56/41" id="select_41">Study LED Lights 2</p>
				
			</div> <!-- Invisible section of the menu -->
		
			<?php } else {
				//echo ("This is the table view");
				?>
				<div id="status">
				<div class="secondary_tools">
					<input type="submit" name="page" value="<?php if ($page == "1")  {echo "Picture View";} else {echo "Table View";} ?>">
					<input type="submit" name="state" value="Redraw">
				</div> <!-- secondary_tools -->
			</div> <!-- status -->
				<table id="all_channels" cellpadding="0" cellspacing="0">
					<tr>
						<th >Network</td>
						<th width=15%>App</td>
						<th width=15%>App Name</td>
						<th width=15%>Group Address</td>
						<th width=35%>Label</td>
						<th width=10%>Value</td>
						<th width=10%>Selected</td>
					</tr>
					<?php
					// This is where we run through the array and populate the table
					$Networks = $_SESSION["Nets"];
					$Apps = $_SESSION["Apps"];
					$Names = $_SESSION["AppNames"];
					if (isset($_SESSION["Zones"])){
						$Zones = $_SESSION["Zones"];
					}
					foreach ($Networks as &$NetNum) {	//Loop through all of the available networks
						//if ($NetNum == 253) continue; // UN-comment this line to SKIP an unwanted network (e.g. a Bridge mirror)
						foreach ($Apps[$NetNum] as &$AppNum) {	//Loop through all of the available applications
							//echo ("Appnum Value is " . $AppNum . " and name = ". $Names[$AppNum] . "</br>");
							for ($loop = 0; $loop < 255; $loop++) {	// Read all of the groups in this App
								if (isset ($_SESSION["tag[$NetNum][$AppNum][$loop]"])) {	// Only print those that have a name attached
								?>
									<tr>
										<td class="numbers"><?php echo $NetNum ?></td>
										<td class="numbers"><?php echo $AppNum ?></td>
										<td><?php echo $Names[$NetNum][$AppNum] ?></td>
										<td class="numbers"><?php echo $loop ?></td>
										<td>
										<?php
									// Use tag names from Comfortfile if AppNum = 1
									if (($_SESSION["tag[$NetNum][$AppNum][$loop]"] == "Group " . $loop) && ($AppNum == "1") && isset($Zones)) {
										echo ($Zones[($loop-1)] . "*");
									} else {
										echo ($_SESSION["tag[$NetNum][$AppNum][$loop]"]);
									}
									?>
									</td>
								<td class="numbers">
								<?php
								if (isset ($status[$NetNum][$AppNum][$loop])){
									echo ($status[$NetNum][$AppNum][$loop]);					
								}
								?>
								</td>
						
								<td class="selectors">
								<?php
								//Remove 'select' check-box from AppNum 1 (Security) Groups:
								if ($AppNum != "1") {
								?>
									<input type="checkbox" name="channel[]" value="<?php echo $AppNum ?>/<?php echo $loop ?>" />
									<?php
								}
								?>
								</td>
							</tr>
						<?php
							}
						}
					}
				}
		?>
		</table>
		<?php
	}
}
?>
		</div><!-- maincontent -->
		<?php if ($page == 0){
			echo '<div id="menu">'; //Picture view - menu floats
		} else {
			echo '<div id="menu>'; //Table view - menu fixed
		}
		?>
			<ul>
				<li class="power">
					<h2>Power</h2>
					<input type="submit" name="state" value="On">
					<input type="submit" name="state" value="Off">
				</li>
				<li class="ramp_to">
					<h2>Ramp to</h2>
					<p>
						<select name="level">
							<option value="00"  <?php if ($level == "0")   {echo "selected=\'selected\'";}?>  >OFF</option>
							<option value="10"  <?php if ($level == "10")  {echo "selected=\'selected\'";}?>  >10%</option>
							<option value="20"  <?php if ($level == "20")  {echo "selected=\'selected\'";}?>  >20%</option>
							<option value="30"  <?php if ($level == "30")  {echo "selected=\'selected\'";}?>  >30%</option>
							<option value="40"  <?php if ($level == "40")  {echo "selected=\'selected\'";}?>  >40%</option>
							<option value="50"  <?php if ($level == "50")  {echo "selected=\'selected\'";}?>  >50%</option>
							<option value="60"  <?php if ($level == "60")  {echo "selected=\'selected\'";}?>  >60%</option>
							<option value="70"  <?php if ($level == "70")  {echo "selected=\'selected\'";}?>  >70%</option>
							<option value="80"  <?php if ($level == "80")  {echo "selected=\'selected\'";}?>  >80%</option>
							<option value="90"  <?php if ($level == "90")  {echo "selected=\'selected\'";}?>  >90%</option>
							<option value="100" <?php if ($level == "100") {echo "selected=\'selected\'";}?>  >100%</option>
						</select><br />
						&nbsp;or specify 0-255&nbsp;<br />
						<!-- <input type="text" name="specify" value="" maxlength="3" size="3"><br /> -->
						<select name="specify">
							<option value="" selected='selected'></option>
							<?php 
							for ($loop = 0; $loop <= 255; $loop++) {
								echo "<option value=$loop>$loop</option>";
							} 
							?>
						</select><br />
						&nbsp;over&nbsp;<br />
						<select name="ramp">
							<option value="0"   <?php if ($ramp == "0")   {echo "selected=\'selected\'";}?>   >Instant</option>
							<option value="4"   <?php if ($ramp == "4")   {echo "selected=\'selected\'";}?>   >4 s</option>
							<option value="8"   <?php if ($ramp == "8")   {echo "selected=\'selected\'";}?>   >8 s</option>
							<option value="12"  <?php if ($ramp == "12")  {echo "selected=\'selected\'";}?>  >12 s</option>
							<option value="16"  <?php if ($ramp == "16")  {echo "selected=\'selected\'";}?>  >16 s</option>				
							<option value="20"  <?php if ($ramp == "20")  {echo "selected=\'selected\'";}?>  >20 s</option>
							<option value="40"  <?php if ($ramp == "40")  {echo "selected=\'selected\'";}?>  >40 s</option>
							<option value="60"  <?php if ($ramp == "60")  {echo "selected=\'selected\'";}?>  >60 s</option>
							<option value="120" <?php if ($ramp == "120") {echo "selected=\'selected\'";}?> >120 s</option>								
						</select>
						<input type="submit" name="state" value="Ramp">
					</p>
				</li>
			</ul>
		</div> <!-- Menu -->

	</form>
</div> <!-- container -->

</body>
</html>


<?php

// PHP Functions

function display($net, $app, $gp, $room){
	global $status;

	if (isset ($status[$net][$app][$gp])){
		//Then well and good
	} else {
		$status[$net][$app][$gp] = 0;
		$errcode = "Undefined group errors trapped";
	}
	
	if ($status[$net][$app][$gp] < 1) {
		// It's OFF (more or less)
		$lampBrightness = "off";
	} elseif (($status[$net][$app][$gp] > 1) && ($status[$net][$app][$gp] < 255)) {
		//It's 'dimmed'
		$lampBrightness = "dim";
	} else {
		// It must be full on
		$lampBrightness = "on";
	}
	
?>
	<div id="<?php echo$room ?>" class="toggle <?php echo$lampBrightness ?>" onclick="selectApp('select_<?php echo$gp ?>')"></div> <!-- <?php echo$room ?> -->

<?php
}

function buttons($net, $app, $gp, $room, $enabled) {
	global $status;
	
	if (isset ($status[$net][$app][$gp])){
		//Then well and good
	} else {
		$status[$net][$app][$gp] = 0;
		$errcode = "Undefined group errors trapped";
	}
	if ($status[$net][$app][$gp] < 1) {
		// It's OFF (more or less)
		$light_status = "off";
	} elseif (($status[$net][$app][$gp] > 0) && ($status[$net][$app][$gp] < 255)){
		//It's 'dimmed'
		$light_status = "dim";		
	} else {
		// It must be full on
		$light_status = "on";
	}

?>	
	<div id="<?php echo$room ?>" class="statusbutton">
		<div class="statuslight <?php echo$light_status ?>"></div>
		<a href="#" onclick="selectApp('select_<?php echo$gp ?>')" ><?php echo$room ?></a>
	</div> <!-- <?php echo$room ?> -->
<?php
}
?> 