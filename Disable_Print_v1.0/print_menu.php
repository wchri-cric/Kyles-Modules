<?php

// Display REDCap header and connect it to REDCap's module object
require_once "/var/www/html/redcap".APP_PATH_WEBROOT."ProjectGeneral/header.php";
require_once "../../redcap_connect.php";

// creates a string that essentially functions as an html tag
function tag($type,$content,$args=Array()) {
	$out="<".$type;
	foreach($args as $param =>$value) {$out.=" ".$param.'="'.$value.'"';}
	$out.=">".$content."</".$type.">";
	return $out;
}


// Writes the currently selected surveys to their respective project file
function setSurveys($loc) {
        $surveys = $_POST["surveys"];
        $out = '';
        foreach($surveys as $survey_name => $checked) {
                if($checked=="true") {$out .= $survey_name."\n";} // =="true" is used because the values of the array are strings
        }
        $out = substr($out, 0, strlen($out)-1);
        $f = fopen($loc,"w");
        fwrite($f,$out);
        fclose($f);
}

// Gets a list of surveys in a current project
function getSurveys($loc, $pid) {

        if(!file_exists($loc)) {
                $f = fopen($loc,"w");
                fclose($f);
        }
        $f = fopen($loc,"r");

        $survey_string = fread($f,filesize($loc));
        fclose($f);
        $existing_survey_list = explode("\n",$survey_string);

        $project = new Project($pid);
        $surveys = $project->surveys;
        $survey_names = array();
        foreach($surveys as $s) {
                $s["checked"]=false;

                foreach($existing_survey_list as $e) {
                if ($s["form_name"]==$e) {
                        $s["checked"]=true;
                        $existing_survey_list = array_diff($existing_survey_list,array($e));
                        break;
                        }
                }
                array_push($survey_names, $s);
        }
        return $survey_names;
}

function setPage($module) {
	// Defines the basic html layout for the module
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	$css = tag("link","",Array("rel"=>"stylesheet","type"=>"text/css","href"=>$module->getUrl("fadeout.css")));
	print tag("head",$css);
	$br = "</br>";
	$h2 = tag("h2","Please select the surveys you would like to disable printing for.");
	$h4 = tag("h4","Select surveys will show up as blank when printed. Don't forget to click submit!");
	$rows = tag("tr",tag("th","Survey Name:").tag("th","Printing Disabled:"));

	$loc = __DIR__."/survey_lists_per_project/".REDCap::getProjectTitle()." Surveys.txt";
	$surveys = getSurveys($loc,$module->getProjectId()); // Get names of all surveys (from the text files the module generates) and adds them to the table
	foreach($surveys as $s) {
		$name = tag("td",$s["title"]);

		$params = Array("type"=>"checkbox","class"=>"surveyBox","name"=>$s["form_name"]);
		if($s["checked"]==1) $params["checked"]="";
		$checkbox = tag("td",tag("input","",$params));
		$rows .= tag("tr",$name.$checkbox);
		}
	$select_all = tag("input","",Array("type"=>"checkbox","name"=>"select_all","onclick"=>"selectAll()"));
	$rows .= tag("tr",tag("td","Select All").tag("td",$select_all));

	$table = tag("table",$rows,Array("id"=>"surveyTable"));
	$form = tag("form",$table,Array("id"=>"surveyForm"));
	$button = tag("input","",Array("type"=>"button","id"=>"SUBMIT","value"=>"Submit", "onclick"=>"setSurveys()"));
	$message = tag("span","",Array("id"=>"submit_message"));
	print tag("body",$h2.$h4.$br.$br.$form.$br.$br.$button.$br.$br.$message,Array("onload"=>"buildMessage('')"));
	// --------------------------------------------------------------------------------------------------------------------------------------------------------


	// Since a client-side language is needed, define the script tags
	// --------------------------------------------------------------------------------------------------------------------------------------------------------

	// Allows for the use of AJAX calls
	print tag("script","",Array("src"=>'"https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"'));

	// Print out all of the javascript

	$s = '
		// Creates a paragraph tag that displays a message (used for the green "Sumbitted!" text)
		function buildMessage(text) {
        		var message = document.createElement("p");
        		message.setAttribute("id","fadeout");
        		message.innerHTML=text;
        		submit_message.appendChild(message);
		}

		// Selects or deselects every survey checkbox depending on whether or not the "select all" checkbox is checked
		function selectAll() {
			var checkboxes = document.getElementsByClassName("surveyBox");
			var set = document.getElementsByName("select_all")[0].checked;
			for(i=0;i<checkboxes.length;i++) {checkboxes[i].checked = set;}
		}
		// Displays the "Submitted!" message, and sends an AJAX call to the setSurveys php function, with the currently selected surveys as a parameter
		function setSurveys(){
			submit_message.removeChild(document.getElementById("fadeout"));
        		buildMessage("Submitted!");
        		var boxes = document.getElementsByClassName("surveyBox");
        		var surveys = new Object();
        		for(i=0;i < boxes.length;i++) { surveys[boxes[i].name] = boxes[i].checked;}
        		$.ajax({
                		type:"POST",
                		url:"'.$module->getUrl("print_menu.php").'",
                		data:{"func":"setSurveys","surveys":surveys}
        		});
		}


        	// Handles the dialogue box that pops up when there are usaved changes on the screen
        	var formChanged=false;
        	$("#surveyForm").on("change",function() {formChanged=true;});

        	SUBMIT.addEventListener("click", () => formChanged=false);
        	window.addEventListener("beforeunload", (event) => {
        	if(formChanged) { event.returnValue = "Are you sure you want to leave? (Any unsaved changes will be lost!)";} // Chrome gives a generic message so this will$
        	});
	';

	print tag("script",$s);

	// --------------------------------------------------------------------------------------------------------------------------------------------------------

	// Display REDCap footer
	require_once "/var/www/html/redcap".APP_PATH_WEBROOT."ProjectGeneral/footer.php";
}


// Handles whether or not the file will write the webpage, or set the data for which surveys will have printing disabled
$func = $_POST["func"];
$loc = __DIR__."/survey_lists_per_project/".REDCap::getProjectTitle()." Surveys.txt";
        switch($func) {

        case 'setSurveys':
                setSurveys($loc);
                break;

        default:
                setPage($module);

}
