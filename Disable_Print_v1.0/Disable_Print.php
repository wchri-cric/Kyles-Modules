<?php

namespace Disable_Print_Namespace\Disable_Print;

require_once "../../redcap_connect.php";

// The main class that handles all of the REDCap hooks in the modules
class Disable_Print extends \ExternalModules\AbstractExternalModule {
	// Writes the config.json file - will trigger on every page before it's loaded, and when the modules is enabled
	function write_config() {
		// Replaces whatever is in the "icon" section of the links with the path out of the Resources folder to the current directory - where the icon is
		$loc = __DIR__."/config.json";
		$image_str = APP_PATH_IMAGES;
		$version = APP_PATH_WEBROOT;
		$app_path = APP_PATH_DOCROOT;
		$image_path = $app_path.str_replace($version,"",$image_str);
		$index = strspn($image_path ^ __DIR__,"\0");
		$icon_path = str_repeat("../",substr_count($image_path,"/",$index)).substr(__DIR__,$index)."/Disable_Print_Logo";
		$configs = json_decode(file_get_contents($loc), true);
		$configs["links"]["project"][0]["icon"] = $icon_path;
                $f = fopen($loc,"w");
                fwrite($f, json_encode($configs,JSON_PRETTY_PRINT));
                fclose($f);

	}
	// Writes the config.json file before every page is loaded
	function redcap_every_page_before_render($project_id) {
		$this->write_config();
	}
	// Looks through a particular project's file to see which surveys have been activated, and inserts print.css into those pages (to disable printing)
	function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
		$loc = __DIR__."/survey_lists_per_project/".\REDCap::getProjectTitle()." Surveys.txt";

                if(!file_exists($loc)) {
                        $f = fopen($loc,"w");
                        fclose($f);
                }

		$f = fopen($loc,"r");
		$survey_string = fread($f,filesize($loc));
		fclose($f);
		$survey_list = explode("\n",$survey_string);
		foreach($survey_list as $s) {
			if($s == $instrument) {
    				$css_filepath = $this->getUrl("print.css");
				print '<link rel="stylesheet" type "text/css" href = '.$css_filepath.' media="print"/>';
				break;
			}
		}
	}
	// Writes the config.json file when the module is enabled on the system
	function redcap_module_system_enable($version) {
		$this->write_config();
	}
	// Writes a blank .txt file with the project's name when the module is initialized for a project
	function redcap_module_project_enable($version, $project_id) {
		$f = fopen(__DIR__."/survey_lists_per_project/".\REDCap::getProjectTitle()." Surveys.txt","w");
		fwrite($f,"");
		fclose($f);
	}
	// Deletes the project's .txt file when the module is disabled for a project
	function redcap_module_project_disable($version,$project_id) {
		$f = __DIR__."/survey_lists_per_project/".\REDCap::getProjectTitle()." Surveys.txt";
		if(file_exists($f)) {
			unlink($f);
		}
	}
	// Deletes every project's .txt file when the module is disabled from the system
	function  redcap_module_system_disable($version) {
		$files = glob(__DIR__."/survey_lists_per_project/*");
		foreach($files as $f) {
			unlink($f);
		}
	}

}
