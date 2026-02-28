jQuery(document).ready(function($) {
	const $pluginSelect = $('#prep-plugin-select');
	const $configDiv = $('#plugin-configuration');
	const $configContent = $('#config-content');

	$pluginSelect.on('change', function() {
		const plugin = $(this).val();
		if (!plugin) {
			$configDiv.hide();
			return;
		}

		displayConfiguration(plugin);
	});

	function displayConfiguration(plugin) {
		let html = '<table class="form-table" style="margin-top: 0;">';
		
		html += '<tr>';
		html += '<th style="width: 200px;">Plugin Distribution</th>';
		html += '<td>';
		html += '<label style="display: block; margin: 5px 0;"><input type="radio" name="plugin-dist" value="wporg" checked> WordPress.org</label>';
		html += '<label style="display: block; margin: 5px 0;"><input type="radio" name="plugin-dist" value="github"> GitHub</label>';
		html += '<label style="display: block; margin: 5px 0;"><input type="radio" name="plugin-dist" value="other"> Other</label>';
		html += '<p class="description">WordPress.org applies strict requirements. GitHub and Other skip checks like hidden files, plugin updater detection, and readme requirements.</p>';
		html += '</td>';
		html += '</tr>';

		html += '<tr>';
		html += '<th>Keep Cached Results</th>';
		html += '<td>';
		html += '<label><input type="checkbox" id="keep-cache" checked> Preserve existing verification results</label>';
		html += '<p class="description">When unchecked, saving will clear all cached results and you\'ll start fresh on next verification.</p>';
		html += '</td>';
		html += '</tr>';;;

		html += '<tr>';
		html += '<th>Vendor Folders</th>';
		html += '<td>';
		html += '<div id="vendor-folders-container"><p style="color: #666;">Detecting vendor folders...</p></div>';
		html += '</td>';
		html += '</tr>';

		html += '</table>';
		html += '<p><button type="button" id="save-config" class="button button-primary">Save Configuration</button> <span id="prep-spinner" class="spinner" style="float: none;"></span></p>';

		$configContent.html(html);
		$configDiv.show();

		detectVendorsInline(plugin);

		$('#save-config').on('click', function() {
			const distType = $('input[name="plugin-dist"]:checked').val();
			const keepCache = $('#keep-cache').is(':checked');
			saveConfiguration(plugin, distType === 'wporg', keepCache);
		});
	}

	function detectVendorsInline(plugin) {
		const $container = $('#vendor-folders-container');
		$container.html('<p style="color: #666;">Detecting vendor folders...</p>');

		const payload = new FormData();
		payload.append('action', 'plugin_check_detect_vendors');
		payload.append('nonce', PLUGIN_CHECK.nonce);
		payload.append('plugin', plugin);

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.vendors) {
				displayVendorsInline(data.data.vendors, $container);
			} else {
				$container.html('<p style="color: #666;">No vendor folders detected.</p>');
			}
		})
		.catch(error => {
			$container.html('<p style="color: #d63638;">Error detecting vendors.</p>');
		});
	}

	function displayVendorsInline(vendors, $container) {
		if (Object.keys(vendors).length === 0) {
			$container.html('<p style="color: #666;">No vendor folders detected.</p>');
			return;
		}

		let html = '';
		for (const [folder, subdirs] of Object.entries(vendors)) {
			if (subdirs.length === 0) continue;
			
			html += '<div style="margin-bottom: 15px;">';
			html += '<strong>' + folder + '/</strong>';
			html += '<div style="margin-left: 20px; margin-top: 5px;">';
			
			subdirs.forEach(subdir => {
				const path = folder + '/' + subdir;
				html += '<label style="display: block; margin: 3px 0;">';
				html += '<input type="checkbox" class="vendor-checkbox" data-path="' + path + '" checked> ';
				html += subdir;
				html += '</label>';
			});
			
			html += '</div></div>';
		}

		$container.html(html);
	}

	function saveConfiguration(plugin, wporgPrep, keepCache) {
		const $spinner = $('#prep-spinner');
		$spinner.addClass('is-active');
		$('#save-config').prop('disabled', true);

		const selectedVendors = [];
		$('.vendor-checkbox:checked').each(function() {
			selectedVendors.push($(this).data('path'));
		});

		const pluginFolder = plugin.indexOf('/') !== -1 ? plugin.split('/')[0] : plugin;
		const currentUrl = window.location.href;
		const wpContentBase = currentUrl.substring(0, currentUrl.indexOf('/wp-admin/')) + '/wp-content/';
		const jsonUrl = wpContentBase + 'verifier-results/' + pluginFolder + '/results.json';

		fetch(jsonUrl)
			.then(response => response.json())
			.then(data => {
				if (!data.configuration) {
					data.configuration = {};
				}
				data.configuration.wporg_preparation = wporgPrep;
				data.configuration.skipped_rules = wporgPrep ? [] : [
					'hidden_files',
					'application_detected',
					'plugin_updater_detected',
					'outdated_tested_upto_header',
					'stable_tag_mismatch',
					'readme_mismatched_header_requires',
					'mismatched_tested_up_to_header',
					'missing_direct_file_access_protection'
				];

				if (selectedVendors.length > 0) {
					data.ignored_paths = selectedVendors.map(path => ({
						path: path,
						reason: 'vendor',
						added_by: 'admin',
						added_at: new Date().toISOString()
					}));
				}

				if (!keepCache) {
					delete data.results;
					delete data.readiness;
					delete data.generated_at;
				}

				const payload = new FormData();
				payload.append('action', 'plugin_check_update_config');
				payload.append('nonce', PLUGIN_CHECK.nonce);
				payload.append('plugin', plugin);
				payload.append('config', JSON.stringify(data));

				return fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: payload
				});
			})
			.catch(error => {
				const data = {
					configuration: {
						wporg_preparation: wporgPrep,
						skipped_rules: wporgPrep ? [] : [
							'hidden_files',
							'application_detected',
							'plugin_updater_detected',
							'outdated_tested_upto_header',
							'stable_tag_mismatch',
							'readme_mismatched_header_requires',
							'mismatched_tested_up_to_header',
							'missing_direct_file_access_protection'
						]
					},
					ignored_paths: selectedVendors.map(path => ({
						path: path,
						reason: 'vendor',
						added_by: 'admin',
						added_at: new Date().toISOString()
					}))
				};

				const payload = new FormData();
				payload.append('action', 'plugin_check_update_config');
				payload.append('nonce', PLUGIN_CHECK.nonce);
				payload.append('plugin', plugin);
				payload.append('config', JSON.stringify(data));

				return fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: payload
				});
			})
			.then(response => response.json())
			.then(result => {
				$spinner.removeClass('is-active');
				$('#save-config').prop('disabled', false);
				
				if (result.success) {
					alert('Configuration saved successfully!');
				} else {
					alert('Error: ' + (result.data.message || 'Failed to save'));
				}
			})
			.catch(error => {
				$spinner.removeClass('is-active');
				$('#save-config').prop('disabled', false);
				alert('Error: ' + error.message);
			});
	}
});
