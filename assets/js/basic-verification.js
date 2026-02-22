jQuery(document).ready(function($) {
	$('#basic-verify-form').on('submit', function(e) {
		e.preventDefault();
		
		const plugin = $('#basic-plugin-select').val();
		if (!plugin) {
			alert('Please select a plugin');
			return;
		}
		
		const types = [];
		$('input[name="types[]"]:checked').each(function() {
			types.push($(this).val());
		});
		
		$('#basic-spinner').show();
		$('#basic-results').hide();
		
		// Get checks to run first
		const getChecksPayload = new FormData();
		getChecksPayload.append('nonce', PLUGIN_CHECK.nonce);
		getChecksPayload.append('action', 'plugin_check_get_checks_to_run');
		getChecksPayload.append('plugin', plugin);
		getChecksPayload.append('categories[]', 'plugin_repo');
		
		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: getChecksPayload
		})
		.then(response => response.json())
		.then(checksData => {
			if (!checksData.success || !checksData.data.checks || checksData.data.checks.length === 0) {
				$('#basic-spinner').hide();
				$('#basic-results').show();
				$('#basic-output').text(JSON.stringify(checksData, null, 2));
				return;
			}
			
			// Run first check only
			const payload = new FormData();
			payload.append('nonce', PLUGIN_CHECK.nonce);
			payload.append('action', 'plugin_check_basic_check');
			payload.append('plugin', plugin);
			payload.append('checks[]', checksData.data.checks[0]);
			types.forEach(t => payload.append('types[]', t));
			
			return fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			});
		})
		.then(response => response ? response.json() : null)
		.then(data => {
			if (data) {
				$('#basic-spinner').hide();
				$('#basic-results').show();
				$('#basic-output').text(JSON.stringify(data, null, 2));
			}
		})
		.catch(error => {
			$('#basic-spinner').hide();
			alert('Error: ' + error.message);
		});
	});
});
