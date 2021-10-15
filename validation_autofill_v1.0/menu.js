// Get all the parameters for the filling in the module
function buildData() {
        data = {};
        data["initials"] = document.getElementById("initials").value;
        data["redcap_version"] = document.getElementById("redcap_version").value;
        data["browser_version"] = document.getElementById("browser").value;
        data["os_version"] = document.getElementById("os").value;
        data["name"] = document.getElementById("name").value;
        data["notes"] = document.getElementById("notes").value;
        environment = document.getElementsByName("environment");
	data["environment"] = "";
        for(i=0;i<environment.length;i++){if(environment[i].checked==true){data["environment"] = environment[i].getAttribute("value");}}

        failed_list = document.getElementById("failed").childNodes;
        failed = [];
        for(i=0;i<failed_list.length;i++){
                failed_step = failed_list[i].childNodes[0].value;
                failed.push(parseInt(failed_step));
        }
        data["failed"] = failed;

        reference_list = document.getElementById("references").childNodes;
        reference_text = {};
        for(i=0;i<reference_list.length;i++){
                step_num = parseInt(reference_list[i].childNodes[0].value);
                text = reference_list[i].childNodes[3].value;
                reference_text[step_num] = text;
        }
        data["reference_text"] = reference_text;

        break_list = document.getElementById("break").childNodes;
        breaksteps = [];
        dates = [document.getElementById("startdate").value];
        for(i=0;i<break_list.length;i++){
                step_num = break_list[i].childNodes[0].value;
                date = break_list[i].childNodes[3].value;
                breaksteps.push(parseInt(step_num));
                dates.push(date);
        }
        data["breaksteps"] = breaksteps;
        data["dates"] = dates;
	return data;
}

async function makeDoc(up_url,out_url) {
	formData = new FormData()
	fileupload = document.getElementById("fileupload");
	formData.append("file",fileupload.files[0]);

	data = buildData();

	formData.append("data",JSON.stringify(data));
	formData.append("func","makeDoc");
	$.ajax({
		type:"POST",
		url:up_url,
		processData:false,
		contentType:false,
		data:formData,
		success:function(response) {
			if(response == "Document Created Successfully") { openDownloadMenu(out_url);}
			else{alert(response);}
		}
	});
}

// Validate the integer fields
function intValidation(elm) {
	elm.value=elm.value.toUpperCase().replace(/\D/g,"");
	if(parseInt(elm.value) < 1) {elm.value="";}
}

function addReferenceText(step_number_value="",text_value="") {
	reference_list = document.getElementById("references");
	reference = document.createElement("li");
	br = document.createElement("br");

	step_number = document.createElement("input");
	step_number.setAttribute("size","3");
	step_number.setAttribute("maxlength","3");
	step_number.setAttribute("class","integer_field");
	step_number.setAttribute("onkeyup","intValidation(this)");
	step_number.value = step_number_value;
	label = document.createElement("span");
	label.innerHTML = "Step Number";

	text = document.createElement("textarea");
	text.setAttribute("rows","6");
	text.setAttribute("cols","65");
	text.setAttribute("placeholder","Reference Text:");
	text.value=text_value;

	reference.appendChild(step_number);
	reference.appendChild(label);
	reference.appendChild(br);
	reference.appendChild(text);

	reference_list.appendChild(reference);
}

function removeElement(id) {
        element_list = document.getElementById(id);
        num_children = element_list.childNodes.length
	if (num_children>0){element_list.removeChild(element_list.childNodes[num_children-1]);}
}

function addFailedStep(value = "") {

        failed_list = document.getElementById("failed");
        failed = document.createElement("li");
        br = document.createElement("br");

        step_number = document.createElement("input");
        step_number.setAttribute("size","3");
        step_number.setAttribute("maxlength","3");
        step_number.setAttribute("class","integer_field");
        step_number.setAttribute("onkeyup","intValidation(this)");
	step_number.value = value;
        label = document.createElement("span");
        label.innerHTML = "Step Number";

        failed.appendChild(step_number);
        failed.appendChild(label);

        failed_list.appendChild(failed);

}

function addBreak(step_number_value="",date_value="") {

        break_list = document.getElementById("break");
        sectbreak = document.createElement("li");
        br = document.createElement("br");

        step_number = document.createElement("input");
        step_number.setAttribute("size","3");
        step_number.setAttribute("maxlength","3");
        step_number.setAttribute("class","integer_field");
        step_number.setAttribute("onkeyup","intValidation(this)");

	step_number.value = step_number_value;
        steplabel = document.createElement("span");
        steplabel.innerHTML = "Step Number";

        date = document.createElement("input");
	date.setAttribute("type","date");
	date.value = date_value;
        datelabel = document.createElement("span");
        datelabel.innerHTML = "New Section Date";

        sectbreak.appendChild(step_number);
        sectbreak.appendChild(steplabel);
        sectbreak.appendChild(br);
        sectbreak.appendChild(date);
	sectbreak.appendChild(datelabel)

        break_list.appendChild(sectbreak);
}

function openModal() {
        modal = document.getElementById("modal");
        modal.style.display = "block";
}

function closeModal() {
	modal = document.getElementById("modal");
	modal.style.display = "none";
	modal_content = document.getElementById("modal-content");
	children = modal_content.childNodes;
	let i=0;
	while(i<children.length) {
		if(children[i].getAttribute("id")!="close") {
			modal_content.removeChild(children[i]);
		}
		else {
			i++;
		}
	}
}

function openDownloadMenu(out_url) {
	openModal();
        modal_content = document.getElementById("modal-content");
        desc = document.createElement("p");
        desc.innerHTML = "Your document is ready! Click the image below to download it.";
	img = document.createElement("img");
	img.setAttribute("src","https://cdn.windowsfileviewer.com/images/types/docx.png");
	img.setAttribute("width","100");
	img.setAttribute("height","100");
	link = document.createElement("a");
	link.setAttribute("href",out_url);
	link.appendChild(img);
	modal_content.appendChild(desc);
	modal_content.appendChild(link);
}

function openSaveMenu(up_url) {
	openModal();
	modal_content = document.getElementById("modal-content");
	desc = document.createElement("p");
	desc.innerHTML = "Enter the name of the module, and click save.";
	textbox = document.createElement("input");
	textbox.setAttribute("id","savename");
	savebutton = document.createElement("input");
	savebutton.setAttribute("type","button");
	savebutton.setAttribute("value","Save");
	savebutton.setAttribute("onclick","saveModule('"+up_url+"')");
	modal_content.appendChild(desc);
	modal_content.appendChild(textbox);
	modal_content.appendChild(document.createElement("br"));
	modal_content.appendChild(savebutton);
}


function openLoadMenu(up_url) {
	$.ajax({
		type:"POST",
		url:up_url,
		data:{"func":"loadModule"},
		success:function(response) {
			data = JSON.parse(response);
        		openModal();
        		modal_content = document.getElementById("modal-content");
        		desc = document.createElement("p");
        		desc.innerHTML = "Choose the name of the module, and click load.";
        		dropdown = document.createElement("select");
        		dropdown.setAttribute("id","loadname");
        		for(var m in data) {
				option = document.createElement("option");
				option.innerHTML = m;
				option.setAttribute("value",JSON.stringify(data[m]));
				dropdown.appendChild(option);
			}

			buttons = document.createElement("span");

        		loadbutton = document.createElement("input");
        		loadbutton.setAttribute("type","button");
        		loadbutton.setAttribute("value","Load");
        		loadbutton.setAttribute("onclick","loadModule()");

			deletebutton = document.createElement("input");
			deletebutton.setAttribute("type","button");
			deletebutton.setAttribute("value","Delete");
			deletebutton.setAttribute("onclick","deleteModule('"+up_url+"')");

			buttons.appendChild(loadbutton);
                        buttons.appendChild(deletebutton);

        		modal_content.appendChild(desc);
        		modal_content.appendChild(dropdown);
        		modal_content.appendChild(document.createElement("br"));
        		modal_content.appendChild(buttons);
                }
        });
}

function saveModule(up_url) {
	savename = document.getElementById("savename").value;
	if (savename != "") {
		closeModal();
		data = JSON.stringify(buildData());
		$.ajax({
                	type:"POST",
                	url:up_url,
                	data:{"func":"saveModule","savename":savename,"data":data},
                	success:function(response) {
				if(response == "success") {
					alert("Saved Module: "+savename);
				}
				else {alert(response);}
                	}
        	});

	}
	else {alert("Please enter a name for the module to save.");}
}


function loadModule(elm) {
	loadname = document.getElementById("loadname");
	if (loadname.value != "") {
		closeModal();
		data = JSON.parse(loadname.value);

		// First and last page fields
		document.getElementById("initials").value = data["initials"];
        	document.getElementById("redcap_version").value = data["redcap_version"];
        	document.getElementById("browser").value = data["browser_version"];
        	document.getElementById("os").value = data["os_version"];
        	document.getElementById("name").value = data["name"];
        	document.getElementById("notes").value = data["notes"];
		document.getElementById("startdate").value = data["dates"][0];
        	environment = document.getElementsByName("environment");
        	for(i=0;i<environment.length;i++){
			if(environment[i].value == data["environment"]){environment[i].checked=true;}
			if(environment[i].value != data["environment"] && environment[i].checked==true) {environment[i].checked=false;}
		}

		// Failed steps
        	failed_list = document.getElementById("failed").childNodes;
		while(failed_list.length>0) {removeElement("failed");}
        	for(i=0;i<data["failed"].length;i++){addFailedStep(data["failed"][i]);}

		// Reference text
        	reference_list = document.getElementById("references").childNodes;
		while(reference_list.length>0) {removeElement("references");}
		for(var n in data["reference_text"]){addReferenceText(n,data["reference_text"][n]);}

		// Break steps
        	break_list = document.getElementById("break").childNodes;
                while(break_list.length>0) {removeElement("break");}
		// index of dates is one higher because first date is starting date (there will always be always 1 more date than breaksteps)
		for(i=0;i<data["breaksteps"].length;i++) {addBreak(data["breaksteps"][i],data["dates"][i+1]);}
		alert("Loaded module: "+ loadname.options[loadname.selectedIndex].text);

	}
	else{alert("Please enter a name for the module to load.");}
}

function deleteModule(up_url) {
        dropdown = document.getElementById("loadname");
        if (dropdown.innerHTML != "") {
		deletename = dropdown.options[dropdown.selectedIndex].text;
		c = confirm("Are you sure you want to delete the module: "+deletename+"?");
		if (c==true) {
                	closeModal();
                	$.ajax({
                        	type:"POST",
                        	url:up_url,
                        	data:{"func":"deleteModule","deletename":deletename},
                        	success:function(response) {
                                	if(response == "success") {
                                        	alert("Deleted Module: "+deletename);
                                	}
                                	else {alert(response);}
                        	}
                	});
		}

        }
        else {alert("Please enter a name for the module to delete.");}
}


window.onclick = function(event) {if(event.target==modal){closeModal();}}
