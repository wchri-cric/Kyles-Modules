window.onload = function() {
	var page = document.getElementsByClassName("table table-no-top-row-border")[0].childNodes[3];
	var observer = new MutationObserver(function (mutationRecords) {
    		var tr = page.childNodes[1];
		if(tr) {
			var td = tr.childNodes[1];
			var inp = td.childNodes[0];

			inp.onchange = function(){
				var value = inp.value;
				if(value != parseInt(value)) {
					alert("The value you entered is not an integer. Please enter an integer or the module will not work correctly.");
				}
				else if(parseInt(value) < 0) {
					alert("The value you entered is a negative number. It is recommended that this should be changed as the module may not work as intended.");
				}

			};
		}
	});
	observer.observe(page,{childList: true});
}
