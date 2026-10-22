// script for jobs.html

// please put this code in head: <script type="text/javascript" src="jobs_validator.js"></script>

/* This prog checks 4 input: (1) name, (2) e-mail, (3) start date, (4) experience

– The name field contains alphabet characters and character spaces. 
– The email field contains a user name part follows by “@” and a domain name part. 
    The user name contains word characters including hyphen (“-”) and period (“.”). 
    The domain name contains two to four address extensions. Each extension is string of word characters and separated from the others by a period (“.”). 
    The last extension must have two to three characters. 
– The start date cannot be from today and the past. 
– The experience field cannot be empty (This can be done in HTML5)

*/

// Steps: (1) Get elements (2) Create function for each element validation

function validateName () {

	j_name = document.getElementById("CustName");
					/* The name field contains alphabet characters 
					and character spaces.
						So, 
						I assume 'Jack Lee' is valid
						I assume 'Jack Lee 12' is invalid
						'Jack Lee_' is invalid
						Only can use alphabet & spaces
						'Jack   			Lee' is invalid
						' Jack Lee' is invalid   */
	var pos = j_name.value.search(/^[a-z]+( [a-z]+){0,10}$/i);
	
	if (pos == -1) {
		alert("The name field must contains alphabet characters and spaces only.");
		j_name.focus();
		//j_name.select();
		//return false;
	}
}

function validateEmail () {
	var j_email = document.getElementById("CustEmail");
	var pos = j_email.value.search(/^[A-Z0-9\.\-]+\@[A-Z]+\.[A-Z]+(\.[A-Z]+){0,2}$/i);
	var end = j_email.value.search(/\.[A-Z]{2,3}$/i);

	if (pos == -1 || end == -1) {
		alert("The email field contains a user name part follows by “@” and a " +
			"domain name part. The user name contains word characters " +
			"including hyphen (“-”) and period (“.”). The domain name contains " +
			"two to four address extensions. Each extension is string of word " +
			"characters and separated from the others by a period (“.”). The last " +
			"extension must have two to three characters.");
		j_email.focus();
		//return false;
	}
}

function validateStartdate () {
	var j_startdate = document.getElementById("StartDate");

	var today = new Date();
	var j_date = today.getDay();
	var j_year = today.getFullYear();
	var j_month = today.getMonth() + 1;
	
	nextDay = j_year+"-"+j_month+"-"+(j_date+1);
	
	var input_year = Number(j_startdate.value[0]+j_startdate.value[1]+j_startdate.value[2]+j_startdate.value[3]);
	var input_month = Number(j_startdate.value[5]+j_startdate.value[6]);
	var input_date = Number(j_startdate.value[8]+j_startdate.value[9]);
	
	if (input_year < j_year) {
		alert("The date selected cannot be from today or the past.");
		//j_startdate.focus();
		//return false;
	}
	else if (input_year == j_year) {
		if (input_month < j_month) {
			alert("The date selected cannot be from today or the past.");
			//j_startdate.focus();
			//return false;
		}

		else if (input_month == j_month) {
			if (input_date <= j_date) {
				alert("The date selected cannot be from today or the past.");
				//j_startdate.focus();
				//return false;
			}
		}
	}

}

function validateExperience () {
	var j_exp = document.getElementById("Experience");
	if (j_experience.value == "") {
		alert("The field cannot be left blank");
		//j_experience.focus();
		//return false;
	}

}
function validateForm() {

	var j_name = document.getElementById("CustName");
	var j_email = document.getElementById("CustEmail");
	var j_startdate = document.getElementById("StartDate");
	var j_exp = document.getElementById("Experience");
	
	/* The name field contains alphabet characters 
	and character spaces.
		So, 
		I assume 'Jack Lee' is valid
		I assume 'Jack Lee 12' is invalid
		'Jack Lee_' is invalid
		Only can use alphabet & spaces
		'Jack   			Lee' is invalid
		' Jack Lee' is invalid
	*/
	var pos = j_name.value.search(/^[a-z]+( [a-z]+){0,10}$/i);
	
	if (pos == -1) {
		alert("The name field must contains alphabet characters and spaces only.");
		//j_name.focus();
		j_name.focus();
		return false;
	}

	var pos = j_email.value.search(/^[A-Z0-9\.\-]+\@[A-Z]+\.[A-Z]+(\.[A-Z]+){0,2}$/i);
	var end = j_email.value.search(/\.[A-Z]{2,3}$/i);

	if (pos == -1 || end == -1) {
		alert("The email field contains a user name part follows by “@” and a " +
			"domain name part. The user name contains word characters " +
			"including hyphen (“-”) and period (“.”). The domain name contains " +
			"two to four address extensions. Each extension is string of word " +
			"characters and separated from the others by a period (“.”). The last " +
			"extension must have two to three characters.");
		j_email.focus();
		return false;


	}
	
	var today = new Date();
	var j_date = today.getDay();
	var j_year = today.getFullYear();
	var j_month = today.getMonth() + 1;
	
	nextDay = j_year+"-"+j_month+"-"+(j_date+1);
	
	var input_year = Number(j_startdate.value[0]+j_startdate.value[1]+j_startdate.value[2]+j_startdate.value[3]);
	var input_month = Number(j_startdate.value[5]+j_startdate.value[6]);
	var input_date = Number(j_startdate.value[8]+j_startdate.value[9]);
	
	if (input_year < j_year) {
		alert("The date selected cannot be from today or the past.");
		j_startdate.focus();
		return false;
	}
	else if (input_year == j_year) {
		if (input_month < j_month) {
			alert("The date selected cannot be from today or the past.");
			j_startdate.focus();
			return false;
		}

		else if (input_month == j_month) {
			if (input_date <= j_date) {
				alert("The date selected cannot be from today or the past.");
				j_startdate.focus();
				return false;
			}
		}
	}

	if (j_experience.value == "") {
		alert("The field cannot be left blank");
		j_experience.focus();
		return false;
	}
	return true;
}