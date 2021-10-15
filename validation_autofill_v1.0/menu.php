<?php
require_once "../../redcap_connect.php";

// creates a string that essentially functions as an html tag
function tag($type,$content,$args=Array()) {
        $out="<".$type;
        foreach($args as $param =>$value) {$out.=" ".$param.'="'.$value.'"';}
        $out.=">".$content."</".$type.">";
        return $out;
}




function makeDoc() {

	$loc = __DIR__."/in.docx";
	move_uploaded_file($_FILES["file"]["tmp_name"],$loc);
	$data = $_POST["data"];
	$data = explode("'",$data);
	$cmd = "python3 ".__DIR__."/validation.py %s 2>&1";
	$args = "";
	$single_quote = "'%s'";
	$double_quote = ' "\'" ';
	$size = sizeof($data)-1;

	foreach($data as $n=>$s) {
        	$args .= sprintf($single_quote,$s);
        	if($n<$size) { $args .= $double_quote; }
	}
	$cmd = sprintf($cmd,$args);
	print(exec($cmd));
}

function saveModule($module) {
	$loc = __DIR__."/saved_modules.json";
	$saved_modules = json_decode(file_get_contents($loc), true);
	if(!isset($saved_modules[USERID])) {
		$saved_modules[USERID] = Array();
	}
	$saved_modules[USERID][$_POST["savename"]] = json_decode($_POST["data"]);
	$f = fopen($loc,"w");
	fwrite($f, json_encode($saved_modules,JSON_PRETTY_PRINT));
	fclose($f);
	print("success");
}

function loadModule($module) {
        $loc = __DIR__."/saved_modules.json";
        $saved_modules = json_decode(file_get_contents($loc), true);
	print(json_encode($saved_modules[USERID]));
}

function deleteModule($module) {
	$loc = __DIR__."/saved_modules.json";
        $saved_modules = json_decode(file_get_contents($loc), true);
	unset($saved_modules[USERID][$_POST["deletename"]]);
        $f = fopen($loc,"w");
        fwrite($f, json_encode($saved_modules,JSON_PRETTY_PRINT));
        fclose($f);
        print("success");
}

function setPage($module) {
	$br = "</br>";
	$br4 = "</br></br></br></br>";

        $css = tag("link","",Array("rel"=>"stylesheet","type"=>"text/css","href"=>$module->getUrl("menu.css")));
        print tag("head",$css);

	// First page input fields
	$fileupload = tag("input","",Array("type"=>"file","id"=>"fileupload"));
	$firstpage_title = tag("h3","Enter Data For First Page");
	$initials = tag("input","Initials",Array("id"=>"initials","size"=>"2","maxlength"=>"2"));
	$redcap_version = tag("input","REDCap Version",Array("id"=>"redcap_version"));
	$development = tag("input","Development",Array("id"=>"development","type"=>"radio","name"=>"environment","value"=>"Development"));
	$production = tag("input","Production",Array("id"=>"production","type"=>"radio","name"=>"environment","value"=>"Production"));
	$browser = tag("input","Browser & Version",Array("id"=>"browser"));
	$os = tag("input","OS & Version",Array("id"=>"os"));
	$startdate = tag("input","Start Date",Array("id"=>"startdate","type"=>"date"));

	$firstpage = $firstpage_title.$br.$initials.$br.$redcap_version.$br.$browser.$br.$os.$br.$startdate.$br.$development.$production;

	// Reference text
	$reference_title = tag("h3","Reference Text");
	$add_reference = tag("input","",Array("id"=>"add_reference","type"=>"button","value"=>"+","onclick"=>"addReferenceText()"));
	$remove_reference = tag("input","",Array("id"=>"remove_reference","type"=>"button","value"=>"-","onclick"=>"removeElement('references')"));
	$references = tag("ul","",Array("id"=>"references"));
	$referencepage = $reference_title.$br.$references.$br.$add_reference.$remove_reference;

	// Failed steps
	$failed_title = tag("h3","Failed Steps");
	$add_failed = tag("input","",Array("id"=>"add_failed","type"=>"button","value"=>"+","onclick"=>"addFailedStep()"));
	$remove_failed = tag("input","",Array("id"=>"remove_failed","type"=>"button","value"=>"-","onclick"=>"removeElement('failed')"));
	$failed = tag("ul","",Array("id"=>"failed"));
	$failedpage = $failed_title.$br.$add_failed.$remove_failed.$br.$failed;
	// Continous breaks
	$break_title = tag("h3","Section Breaks & Dates");
	$add_break = tag("input","",Array("id"=>"add_break","type"=>"button","value"=>"+","onclick"=>"addBreak()"));
	$remove_break = tag("input","",Array("id"=>"remove_break","type"=>"button","value"=>"-","onclick"=>"removeElement('break')"));
	$break = tag("ul","",Array("id"=>"break"));
	$breakpage = $break_title.$br.$add_break.$remove_break.$br.$break;

	// Modal Menu

	$close = tag("span","&times;",Array("id"=>"close","onclick"=>"closeModal()"));
	$modal_content = tag("div",$close,Array("id"=>"modal-content"));
	$modal = tag("div",$modal_content,Array("id"=>"modal"));

	// Last page input fields
	$lastpage_title = tag("h3","Enter Data For Last Page");
	$name = tag("input","Name",Array("id"=>"name"));
	$notes = tag("textarea","",Array("id"=>"notes","rows"=>"6","cols"=>"65","placeholder"=>"Notes:"));
	$lastpage = $lastpage_title.$br.$name.$br.$notes;

	// Save and Submit
	$save = tag("input","",Array("type"=>"button","value"=>"Save Module","onclick"=>"openSaveMenu('".$module->getUrl("menu.php")."')"));
	$load = tag("input","",Array("type"=>"button","value"=>"Load Module","onclick"=>"openLoadMenu('".$module->getUrl("menu.php")."')"));
	$submit = tag("input","",Array("type"=>"button","value"=>"Make Document","onclick"=>"makeDoc('".$module->getUrl("menu.php")."','".$module->getUrl("out.docx")."')"));

	print(tag("h3","Choose a Validation Script to Fill in"));
	$form = tag("form",$br.$fileupload.$br4.$firstpage.$br4.$referencepage.$br4.$failedpage.$br4.$breakpage.$modal.$br4.$lastpage.$br4.$save.$br.$load.$br.$submit,Array("id"=>"upload"));
	print($form);

	$ajaxscript = tag("script","",Array("src"=>'"https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"'));
	//print($ajaxscript);

	$f = fopen(__DIR__."/menu.js","r");
	$script = tag("script",fread($f,filesize(__DIR__."/menu.js")));
	fclose($f);
	print($script);
}


$func = $_POST["func"];
switch($func) {
	case 'makeDoc':
		makeDoc();
		break;
	case 'saveModule':
		saveModule($module);
		break;
	case 'loadModule':
		loadModule($module);
		break;
	case 'deleteModule':
		deleteModule($module);
		break;
	default:
		require_once "/var/www/html/redcap".APP_PATH_WEBROOT."ProjectGeneral/header.php";
		setPage($module);
		require_once "/var/www/html/redcap".APP_PATH_WEBROOT."ProjectGeneral/footer.php";
}
