function intify(data_points) {
	for(i=0;i<data_points.length;i++) {
		data_point = data_points[i];
		data_point[1] = {v:i,f:data_point[1]};
		data_points[i] = data_point;
	}
	return data_points;
}

function getTicks(data_points) {
	var ticks = [];
	for(i=0;i<data_points.length;i++) {
		ticks.push(data_points[i][1]);
	}
	return ticks;
}

function getEvents() {
	var s = document.getElementById("settings");
	var setting = s.options[s.selectedIndex].value;
	$.ajax({
			type:"POST",
			url:"%s",
			data:{"func":"getEvents","setting":setting},
			success: function(events) {
				events = JSON.parse(events);
				var select_event = document.getElementById("events");
				select_event.innerHTML = "";
				for(var id in events) {
					var option = document.createElement("option");
					option.innerHTML = events[id];
					option.value = id;
					select_event.appendChild(option);
				}
			}
	});
}
google.charts.load("current", {"packages":["corechart", "scatter"]});
function drawPlot(field,event,data_points,color,first_index,last_index) {
	var data = new google.visualization.DataTable();
	data.addColumn("string", "Date");
	data.addColumn("number", "Record");
	data.addColumn({type:"string", role:"tooltip"});
	data_points = data_points.sort(function(a,b) {
		if(a[1] === b[1]) {return 0;}
		else {return (a[1] < b[1]) ? -1:1;}
	}); // Sorts the data points based on record string value
	data_points = intify(data_points); // Turns all string records into objects ordered by their place in the list
	ticks = getTicks(data_points.slice(first_index,last_index));
	data.addRows(data_points.slice(first_index,last_index)); // NEED TO DISPLAY THE FORMATTED VALUES NOW
	var plotOptions = {
			width:1000,
			height:700,
			legend:"none",
			colors:[color],
			chartArea: {top:5},
			hAxis: { title:"Date Recorded",
				slantedTextAngle:90
			},
			vAxis: {
				title:"Record ID",
				ticks:ticks
			}
		};
	var plotDiv = document.getElementById("plotdiv");
	var drawChart = new google.visualization.ScatterChart(plotDiv);
	drawChart.draw(data, plotOptions);
}

// Handles the pressing of the enter key
function handle_enter(inp) {
	if(event.key == "Enter") {
		navigate(mode="",num_data_points=0,search=inp.value); // Only search is not default
	}
}

function clearAutoComplete() { // Clears the autocomplete drop down list
	var searchbar = document.getElementById("autocomplete");
	var containers = searchbar.getElementsByTagName("div");
	for(j=0;j<containers.length;j++) {searchbar.removeChild(containers[j]);}
}

// Displays the navigation options
function displayNav(data_points) {
	var indices = document.getElementById("nav");
	var first_index = parseInt(indices.getAttribute("first_index"));
	var last_index = parseInt(indices.getAttribute("last_index")); // Get the first and last indices of data points to display
	var num_data_points = data_points.length;
	// Display the navigation options
	var center = indices.childNodes[0];
	center.innerHTML="";
	if(num_data_points > 25) {
		var first_button = document.createElement("input"); // First 25 Button
		first_button.setAttribute("type","button");
		first_button.setAttribute("value","First 25");
		first_button.setAttribute("onclick","navigate()");
		first_button.style.marginRight = "50px";
		center.appendChild(first_button);

		if (first_index>0) { // Previous 25 Button
			var prev_button = document.createElement("input");
			prev_button.setAttribute("type","button");
			prev_button.setAttribute("value","Previous 25");
			prev_button.setAttribute("onclick","navigate(mode=\"-25\")");
			center.appendChild(prev_button);
		}

		if(last_index < num_data_points) { // Next 25 button
			var next_button = document.createElement("input");
			next_button.setAttribute("type","button");
			next_button.setAttribute("value","Next 25");
			next_button.setAttribute("onclick","navigate(mode=\"25\")");
			center.appendChild(next_button);
		}

		var last_button = document.createElement("input"); // Last 25 Button
		last_button.setAttribute("type","button");
		last_button.setAttribute("value","Last 25");
		last_button.setAttribute("onclick","navigate(mode=\"last\",num_data_points=\""+num_data_points+"\")");
		last_button.style.marginLeft = "50px";
		center.appendChild(last_button);
	}


	indices.appendChild(center);

	// Add the record search bar:
	var search = document.getElementById("search");
	center = search.childNodes[0];
	center.innerHTML = "";
	var search_label = document.createElement("label");
	search_label.innerHTML = "Show records starting with record ID: ";
	search_label.setAttribute("for","searchbar");
	var searchbar = document.createElement("input");
	searchbar.setAttribute("id","searchbar");
	searchbar.setAttribute("type","text");
	searchbar.setAttribute("onkeyup","handle_enter(this)");

	// Autocomplete
	searchbar.addEventListener("input",function(e) {
		var value = this.value;
		var container = document.createElement("div");
		container.setAttribute("class","autocomplete-items");
		clearAutoComplete();
		this.parentNode.appendChild(container);
		var counter = 0;
		for(i=0;i<num_data_points;i++) {
			if(counter>=10) {break;}
			if(data_points[i][1]["f"].substr(0,value.length).toUpperCase() == value.toUpperCase() && value!="") {
				var item = document.createElement("div");
				item.innerHTML += data_points[i][1]["f"];
				item.innerHTML += "<input type=\"hidden\" value=\"" + data_points[i][1]["f"] + "\">";
				item.addEventListener("click",function(e) {
					searchbar.value = this.getElementsByTagName("input")[0].value;
					clearAutoComplete();
				});
				container.appendChild(item);
				counter ++;
			}
		}

	});

	center.appendChild(search_label);
	center.appendChild(searchbar);
	document.addEventListener("click",function(e) {
		clearAutoComplete();
	});

}


// Displays the stats
function getStats(search) {
	var s = document.getElementById("settings");
	var t = document.getElementById("events");
	var setting = s.options[s.selectedIndex].value;
	var color = s.options[s.selectedIndex].className;
	var event = "";
	if(t){var event = t.options[t.selectedIndex].value;}
	$.ajax({
		type:"POST",
		url:"%s",
		data:{"func":"getStats","setting":setting,"event":event},
		success: function(data_points) {
			console.log(data_points);
			var first_index;
			var last_index;
			var DATA_POINTS = JSON.parse(data_points);
			if(search) { // Set starting index to the value of the value being searched for (and last to that plus 25)
				for(i=0;i<DATA_POINTS.length;i++) {
					if(search.toUpperCase()==DATA_POINTS[i][1].toUpperCase()) {
						first_index = i;
						last_index = first_index + 25;
						break;
					}
				}
			}
			else {
				var indices = document.getElementById("nav");
				first_index = indices.getAttribute("first_index");
				last_index = indices.getAttribute("last_index");
			}
			// Draw the graph
			drawPlot(setting,event,DATA_POINTS,color,parseInt(first_index),parseInt(last_index));
			displayNav(DATA_POINTS);
		}
	});
}

// Navigates through the data points by updating the indices to display and drawing a new plot
// DEFAULT: initially clicked on the getStats button
// Navigation path: navigate -> getStats -> (drawPlot & displayNav)
function navigate(mode="",num_data_points = 0,search="") {
	var indices = document.getElementById("nav");
	var first_index = parseInt(indices.getAttribute("first_index"));
	var last_index = parseInt(indices.getAttribute("last_index")); // Get the first and last indices of data points to display

	if(mode=="25") { // Increment indices by 25
		indices.setAttribute("first_index",first_index + 25);
		indices.setAttribute("last_index",last_index + 25);
	}
	else if(mode=="-25") { // Decrement indices by 25
		indices.setAttribute("first_index",(first_index - 25)*((first_index - 25)>=0) ); // product will always be >=0
		indices.setAttribute("last_index",last_index - 25);
	}
	else if (mode=="last") { // Set indices to display last 25 (using optional argument "num_data_points")
		indices.setAttribute("first_index",num_data_points - 25);
		indices.setAttribute("last_index",num_data_points);
	}
	else { // Submit button or "First 25" button is clicked: display first 25 data points
		indices.setAttribute("first_index",0);
		indices.setAttribute("last_index",25);
	}
	// Display the stats
	getStats(search);
}
