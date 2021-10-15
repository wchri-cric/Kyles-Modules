<?php
  namespace validation_autofill_env\validation_autofill;
  require_once "../../redcap_connect.php";
  class validation_autofill extends \ExternalModules\AbstractExternalModule {

        function redcap_module_link_check_display($project_id, $link) {
                return $link;
        }

	// Reset the file when the module is removed from the system
	function redcap_module_system_disable($version) {
		$loc = __DIR__."/saved_modules.json";
		$f = fopen($loc,"w");
		fwrite($f,"{}");
		fclose($f);
	}


  }

