<?php

// Set the namespace defined in your config file
namespace covid_entry_logNamespace\covid_entry_log;

require_once "../../redcap_connect.php";


class covid_entry_log extends \ExternalModules\AbstractExternalModule {

	function checkCapacity($cronAttributes){

		$projects = $this->query('SELECT s.project_id
                                                        FROM redcap_external_modules m
                                                        JOIN redcap_external_module_settings s
                                                                ON m.external_module_id = s.external_module_id
                                                        JOIN redcap_projects p
                                                                ON s.project_id = p.project_id
                                                        WHERE m.directory_prefix = ?
                                                                and p.date_deleted IS NULL
                                                                and `key` = ?
                                                                and value = "true"', ["covid_entry_log","enabled"]);

		$pids = Array();
		while($project = $projects->fetch_assoc()) {array_push($pids, $project["project_id"]);}

		foreach($pids as $pid) {
			$settings = $this->getProjectSettings($pid);
			$params = Array("project_id"=>$pid,"return_format"=>"array","filterLogic"=>'([entry_log_complete] = "2") AND ([exit_log_complete]
			<> "2") AND (datediff( [entry_time], "today", "d", "mdy", true )=0)');
			$data = \REDCap::getData($params);

			$lab_name = $settings["lab_name"]["value"];
			$mngr_email = $settings["mngr_email"]["value"];

			$mailing_list = Array();
			$subject = $lab_name." Social Distancing Alert";
			$mail_str = $lab_name. ' appears to have too many occupants present (Capacity has been exceeded by %d %s). '.
			'This may be because too many people are in the lab/office or because someone has left and failed to log their exit.'.
			'Please take appropriate action to remedy this situation.';

			if($settings["alert_selected"]["value"] && !in_array($mngr_email,$settings["excluded_users"]["value"])) {
				array_push($mailing_list,$mngr_email);
			}

			// Get number of people in lab
			$people = 0;

			foreach($data as $record => $sub_data) {
				$fields = array_values($sub_data)[0];
				$email = $fields["email"];

				if(!in_array($email,$settings["excluded_users"]["value"])){
					$people += 1;
					if($settings["alert_all"]["value"] && !in_array($email,$mailing_list)) {array_push($mailing_list,$email);}
				}
			}

			$capacity = intval($settings["capacity"]["value"]);
			if($people > $capacity) {
				$excess = $people - $capacity;
				switch($excess) {
					case 1:
						$option = "person";
						break;
					default:
						$option = "people";
				}

				foreach($mailing_list as $email) {
					\REDCap::email($email,$mngr_email,$subject,sprintf($mail_str,$excess,$option));
				}
			}

		}

	}
}
