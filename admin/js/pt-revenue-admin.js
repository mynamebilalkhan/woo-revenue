(function( $ ) {
	'use strict';
	// In your Javascript (external .js resource or <script> tag)
	
	// Preserve selected options when form is submitted
	document.getElementById('filter-form').addEventListener('submit', function() {
		var selectedCountries = Array.from(document.getElementById('country').selectedOptions).map(option => option.value);
		document.getElementById('selected-countries').value = JSON.stringify(selectedCountries);

		var existingSelectedStates = {};
		try {
			existingSelectedStates = JSON.parse(document.getElementById('selected-states').value);
		} catch (e) {}

		// Update the selected states for each country, preserving existing selections
		selectedCountries.forEach(country => {
			var selectedStates = Array.from(document.querySelectorAll('select[name="state[]"][data-country="' + country + '"] option:checked')).map(option => option.value);
			// Preserve existing selected states for the country
			existingSelectedStates[country] = existingSelectedStates[country] ? existingSelectedStates[country].concat(selectedStates) : selectedStates;
		});

		document.getElementById('selected-states').value = JSON.stringify(existingSelectedStates);
	});

	// Restore the selected states when the page is loaded
	document.addEventListener('DOMContentLoaded', function() {
		var selectedStatesInput = document.getElementById('selected-states');
		var existingSelectedStates = {};
		try {
			existingSelectedStates = JSON.parse(selectedStatesInput.value);
		} catch (e) {}

		// Restore the selected states for each country
		Object.keys(existingSelectedStates).forEach(country => {
			var selectedStates = existingSelectedStates[country];
			if (selectedStates.length > 0) {
				selectedStates.forEach(state => {
					var stateOption = document.querySelector('select[name="state[]"][data-country="' + country + '"] option[value="' + state + '"]');
					if (stateOption) {
					stateOption.selected = true;
					}
				});
			}
		});
	});

	$(document).ready(function() {
		$('.select_country_multiple').select2({
			multiple: true,
            	width: '50%',
            	placeholder: 'Search Country...'
		});
		
		$('.select_state_multiple').select2({
			multiple: true,
            	width: '50%',
            	placeholder: 'Search State...'
		});
	});

})( jQuery );
