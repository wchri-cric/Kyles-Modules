<?php

// Set the namespace defined in your config file
namespace field_trackerNamespace\field_tracker;

require_once "../../redcap_connect.php";

// Declare your module class, which must extend AbstractExternalModule
class field_tracker extends \ExternalModules\AbstractExternalModule {
	// Helper functions that determines if the data being entered is duplicate of the immediatley previous data
	function record_event_equal($old_data,$new_data) {
		foreach(array_reverse($old_data) as $i => $data) {
			if($data["record"] == $new_data["record"] && $data["event"] == $new_data["event"]) {
				if($data["field_name"] == $new_data["field_name"] && $data["value"] == $new_data["value"]) {return true;}
				return false;
			}
		}
		return false;
	}

	// Looks through all the data in $fields (array) and if they match the project settings, write out to the project's .json file
	// If previous data is supplied, check that. Otherwise, check previous data in .json file
	function save_data($project_id,$fields,$record,$event_id) {
		$loc = __DIR__."/field_data/".$project_id."_field_data.json";
		$field_data = json_decode(file_get_contents($loc), true);

		$project_settings = Array("enrollment_date","site_location","project_completion","withdrawal_reason");
                foreach($fields as $field) {
                        foreach($project_settings as $p){
                                if($this->getProjectSetting($p) == $field) {
					$data = \REDCap::getData("array",$record,$field);
                			$push = Array(  "record"=>strval($record),
                                			"field_name"=>$field,
                                			"value"=>$data[$record][$event_id][$field],
                                			"time" => date("Y-m-d")."\n".date("h:ma"));

                			if(\REDCap::isLongitudinal()){$push["event"] = strval($event_id);}
					if(is_null($push["value"])) {$push["value"] = "";}
					if(!$this->record_event_equal($field_data[$p],$push)){array_push($field_data[$p],$push); }
				}
			}
		}

		$f = fopen($loc,"w");
                fwrite($f,json_encode($field_data,JSON_PRETTY_PRINT));
                fclose($f);

	}


	function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
		$fields = \REDCap::getFieldNames($instrument);
		$this->save_data($project_id,$fields,$record,$event_id);
	}

	// TODO:
	//	1. Fixes strnage points on chart that appear sometimes when data is empty when imported (seems to happen only for classical, but look into further)
	//	2. Test graphs and see if they look okay when lots have data has been entered (utilize data import for this)
	function redcap_every_page_top($project_id) {
		$flag_loc = __DIR__."/import.flag"; // Location of the flag file
		$prevd_loc = __DIR__."/previous.data"; // Location of the previous data file
		$f = fopen($flag_loc,"r");
		$flag = fread($f,filesize($flag_loc));
		fclose($f);

		if(PAGE=="DataImportController:index" && !$flag) { // If the page is the data import page
			$keys = array_keys($_REQUEST);
			if(in_array("updaterecs",$keys)) { // If the data has been imported
				$f = fopen($flag_loc,"w");
				fwrite($f,1);
				fclose($f); // Set flag to true - next page load will update the data with all the newly data imported data
				$f = fopen($prevd_loc,"w"); // Since new data cannot be obtained until the page is refreshed, save the current data
                        	fwrite($f,json_encode(\REDCap::getData("array"),JSON_PRETTY_PRINT));
                        	fclose($f);
			}
		}
		elseif($flag && PAGE!="DataImportController:index") { // Sets all imported data when user navigates away from data import page
		// This else if clause ensures that refreshing the data import page does not unnecessarily reset the newly imported data
			$f = fopen($flag_loc,"w");
			fwrite($f,"0"); // Reset flag
			fclose($f);

			$f = fopen($prevd_loc,"r");
			$previous_data = json_decode(fread($f,filesize($prevd_loc)),true); // Get the previous data to compare to the current data
			fclose($f);

			$current_data = \REDCap::getData("array");

			$previous_keys = array_keys($previous_data);
			$current_keys = array_keys($current_data);
			$diff_keys = array_diff($current_keys,$previous_keys); // All the new records
			foreach($diff_keys as $record) { // Adds all records to the field data
				$record_data = $current_data[$record];
				$record_keys = array_keys($record_data);
				foreach($record_keys as $event) {
					$event_data = $record_data[$event];
					$fields = array_keys($event_data);
					$this->save_data($project_id,$fields,$record,$event);
				}
			}

			$common_keys = array_intersect($current_keys,$previous_keys); // All the records that MAY have differences in data

			foreach($common_keys as $record) {
				$current_record_data = $current_data[$record];
				$previous_record_data = $previous_data[$record];
				$current_record_keys = array_keys($current_record_data);
				$previous_record_keys = array_keys($previous_record_data);

				$diff_record_keys = array_diff($current_record_keys, $previous_record_keys); // All new events
				foreach($diff_record_keys as $event) {
					$event_data = $current_record_data[$event];
					$fields = array_keys($event_data);
					$this->save_data($project_id,$fields,$record,$event);
				}

				// Old events that MAY have differences in data
				$common_record_keys = array_intersect($current_record_keys,$previous_record_keys);
				foreach($common_record_keys as $event) {
					// if data (module settings) in current record is different than previous record
						// ADDTOFIELDDATA
					$event_data = $current_record_data[$event];
					$fields = array_keys($event_data);
					if($event_data !== $previous_record_data[$event]){$this->save_data($project_id,$fields,$record,$event);}
				}
			}
		}

	}

        // Writes a blank .json file with the project's id when the module is initialized for a project
        function redcap_module_project_enable($version, $project_id) {
		$g = fopen(__DIR__."/template_field_data.json","r");
		$template_data = fread($g,filesize(__DIR__."/template_field_data.json"));
		fclose($g);

                $f = fopen(__DIR__."/field_data/".$project_id."_field_data.json","w");
                fwrite($f,$template_data);
                fclose($f);
        }
        // Deletes the project's .json file when the module is disabled for a project
        function redcap_module_project_disable($version,$project_id) {
                $f = __DIR__."/field_data/".$project_id."_field_data.json";
                if(file_exists($f)) {
                        unlink($f);
                }
        }
        // Clears every project's .json file when the module is disabled from the system
        function  redcap_module_system_disable($version) {
		$g = fopen(__DIR__."/template_field_data.json","r");
                $template_data = fread($g,filesize(__DIR__."/template_field_data.json"));
                fclose($g);

                $files = glob(__DIR__."/field_data/*");
                foreach($files as $file) {
                	$f = fopen($file,"w");
			fwrite($f,$template_data);
			fclose($f);
                }
        }



}
