<?php

require_once "../../redcap_connect.php";

// creates a string that essentially functions as an html tag
function tag($type,$content,$args=Array()) {
	$out="<".$type;
	foreach($args as $param =>$value) {$out.=" ".$param.'="'.$value.'"';}
	$out.=">".$content."</".$type.">";
	return $out;
}

// Helper function that tests whether the value for a given data point has a value in it (ie !="" or an array of zero strings)
function notEmpty($value) {
	if(is_array($value)) {
		foreach($value as $v) {
			if($v != "0") {return true;} // Found not empty value
		}
		return false;
	}
	else {
		if($value == ""){return false;}
		return true;
	}
}

// Helper function that returns a string that represents which values in a checkbox have been selected (NOTE assumes $value is an array)
function getChoiceLabels($choice_str,$indices) {
	$out = "";
	$choices = explode("|",$choice_str);
	foreach($indices as $i) {
		preg_match("~(?<=, )(.*)~",$choices[$i],$matches);
		$out .= $matches[0] . ", ";
	}
	$out = substr($out,0,-2);
	return $out;
}

// Helper function that returns whether a field is a completeion field
function isComplete($dict,$field) {
	$forms = Array();
	foreach($dict as $i=>$settings) {array_push($forms,$settings["form_name"]);}
	$forms = array_values(array_unique($forms));
	foreach($forms as $form) {
        	if($form."_complete" == $field) {
			return \REDCap::getInstrumentNames($form) . " - Complete?";
                }
        }
	return false;
}

// helper function that get a list of events that contain the field
function getEvents($module,$setting) {
	$events = \REDCap::getEventNames(false, true);
	$valid_events = Array();
	foreach($events as $id=>$event) {
		$valid_fields = \REDCap::getValidFieldsByEvents($module->getProjectId(),$id);
		if(in_array($module->getProjectSetting($setting),$valid_fields)) {$valid_events[$id] = $event;}
	}
	return json_encode($valid_events);
}

// Get an array of data points to go into the graph
function getStats($module,$setting,$event) {
	$fname = __DIR__."/field_data/".$module->getProjectId()."_field_data.json";
	$f = fopen($fname,"r");
	$field_data = json_decode(fread($f,filesize($fname)),true);
	fclose($f);
	$data_points = Array();
	$dict = \REDCap::getDataDictionary($module->getProjectID(),"array");
	foreach($field_data[$setting] as $data_point) {
		$field = $data_point["field_name"];
		$value = $data_point["value"];
		if( ( ($data_point["event"] == $event and $event != "") || $event=="") && notEmpty($value) ) {
			$dict = \REDCap::getDataDictionary($module->getProjectID(),"array");
			$choices = $dict[$field]["select_choices_or_calculations"];

			if($choices) { // Test to see if there is a choice or selection value to change the value of the data point to
				$value = is_array($value) ? array_keys($value,"1") : [intval($value)];
				$value = getChoiceLabels($choices,$value);
			}


			// Check to see if the field is a completion variable
			if(isComplete($dict,$field)){
				$value = $value=="0" ? "Incomplete" : ($value=="1" ? "Unverified" : "Complete");
			}
			$form_name = isComplete($dict,$field);
			// If field = "Complete?" field, set label to [form_name] + "- Complete?" else set label to the field's form_name (from data dict)
                	$label = $form_name ? $form_name : $dict[$field]["field_label"];

			$value = $label . ":\n" . $value; // Apend Field Label to value of data point
			array_push($data_points,Array($data_point["time"],$data_point["record"],$value));
		}
	}

	return json_encode($data_points);
}

function setPage($module) {

	$style = tag("style",'
		#autocomplete {
			margin-right:400px;
			margin-left:400px;
		}
		.autocomplete-items {
  			position: absolute;
  			border: 1px solid #d4d4d4;
  			border-bottom: none;
  			border-top: none;
  			z-index: 99;
  			top: 100%;
  			left: 0;
  			right: 0;
		}

		.autocomplete-items div {
  			padding: 10px;
  			cursor: pointer;
  			background-color: #fff;
  			border-bottom: 1px solid #d4d4d4;
		}

		/*when hovering an item:*/
		.autocomplete-items div:hover {
  			background-color: #e9e9e9;
		}

	');

	print tag("head",$style);
	$h2 = tag("h2","Please select the variable you would like to see statistics for:");
	$br = "</br>";

	$setting_colour = Array("enrollment_date"=>"red",
			"site_location"=>"blue",
			"project_completion"=>"yellow",
			"withdrawal_reason"=>"green");

	$setting_label = Array("enrollment_date" => "Enrollment Date",
				"site_location"=>"Site Location",
				"project_completion" => "Project Completion",
				"withdrawal_reason"=>"Withdrawal Reason");
	$options = "";
	foreach($setting_colour as $setting=>$colour) {
		$options .= tag("option",$setting_label[$setting],Array("value"=>$setting,"class"=>$colour));
	}

	$select_label = tag("label","Choose a field:",Array("for"=>"settings"));
        $button_label = tag("label","Get Statistics:",Array("for"=>"submit"));

	$select_properties = Array("id"=>"settings");

	$event_label = "";
	$event = "";
	$ebr = "";
	$body_options = Array();

	if(\REDCap::isLongitudinal()) {
		$event_label = tag("label","Choose an event:",Array("for","events"));
		$event = tag("select","",Array("id"=>"events"));
		$ebr = $br;
		$select_properties["onchange"] = "getEvents()";
		$body_options["onload"] = "getEvents()";
	}

	$select = tag("select",$options,$select_properties);
        $button = tag("input","",Array("id"=>"submit","type"=>"button","value"=>"Submit","onclick"=>"navigate()"));

	$form = tag("form",$select_label.$select.$br.$event_label.$event.$ebr.$button_label.$button,Array("id"=>"statsform"));
	$plotdiv = tag("div","",Array("id"=>"plotdiv","style"=>"width: 900px; height: 500px; margin-bottom:175px;"));
	$c1 = tag("center","");
	$c2 = tag("center","",Array("id"=>"autocomplete","style"=>"position:relative"));
	$nav = tag("span",$c1,Array("id"=>"nav","first_index"=>"0","last_index"=>"25"));
	$search = tag("span",$c2,Array("id"=>"search",));
	print tag("body",$h2.$br.$form.$nav.$br.$search.$br.$br.$plotdiv,$body_options);
	print tag("script","",Array("src"=>'"https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"'));
	print tag("script","",Array("src"=>"https://www.gstatic.com/charts/loader.js"));

	$f = fopen(__DIR__."/tracker_menu.js","r");
	$s = fread($f,filesize(__DIR__."/tracker_menu.js"));
	fclose($f);
	$script = sprintf($s,$module->getUrl("tracker_menu.php"),$module->getUrl("tracker_menu.php"));
	print tag("script",$script);
	require_once "/var/www/html/redcap".APP_PATH_WEBROOT."ProjectGeneral/footer.php";
}

// Handles whether or not the file will write the webpage
$func = $_POST["func"];
        switch($func) {
	case "getEvents":
		print getEvents($module,$_POST["setting"]);
		break;

	case "getStats":
		print getStats($module,$_POST["setting"],$_POST["event"]);
		break;
        default:
		require_once "/var/www/html/redcap".APP_PATH_WEBROOT."ProjectGeneral/header.php";
                setPage($module);
}
