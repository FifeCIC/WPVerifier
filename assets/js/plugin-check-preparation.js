jQuery(document).ready(function($) {
	const $pluginSelect = $('#prep-plugin-select');
	const $resultsDiv = $('#vendor-detection-results');
	const $vendorList = $('#vendor-list');
	const $confirmBtn = $('#confirm-ignore-vendors');
	const $spinner = $('#prep-spinner');

	$pluginSelect.on('change', function() {
		const plugin = $(this).val();
		if (!plugin) {
			$resultsDiv.hide();
			return;
		}

		detectVendors(plugin);
	});

	$confirmBtn.on('click', function() {
		const plugin = $pluginSelect.val();
		if (!plugin) return;

		const selectedVendors = [];
		$('.vendor-checkbox:checked').each(function() {
			selectedVendors.push($(this).data('path'));
		});

		if (selectedVendors.length === 0) {
			alert('No vendors selected');
			return;
		}

		saveIgnoredPaths(plugin, selectedVendors);
	});

	function detectVendors(plugin) {
		$spinner.addClass('is-active');
		$resultsDiv.hide();

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
			$spinner.removeClass('is-active');
			
			if (data.success && data.data.vendors) {
				displayVendors(data.data.vendors);
			} else {
				$vendorList.html('<p>No vendor folders detected.</p>');
				$resultsDiv.show();
			}
		})
		.catch(error => {
			$spinner.removeClass('is-active');
			alert('Error detecting vendors: ' + error.message);
		});
	}

	function displayVendors(vendors) {
		$vendorList.empty();

		if (Object.keys(vendors).length === 0) {
			$vendorList.html('<p>No vendor folders detected.</p>');
			$resultsDiv.show();
			return;
		}

		for (const [folder, subdirs] of Object.entries(vendors)) {
			const $folderDiv = $('<div class="vendor-folder"></div>');
			$folderDiv.append(`<h4>${folder}/</h4>`);

			if (subdirs.length === 0) {
				$folderDiv.append('<p><em>Empty folder</em></p>');
			} else {
				subdirs.forEach(subdir => {
					const path = `${folder}/${subdir}`;
					const $item = $('<div class="vendor-item"></div>');
					$item.html(`
						<label>
							<input type="checkbox" class="vendor-checkbox" data-path="${path}" checked>
							${subdir}
						</label>
					`);
					$folderDiv.append($item);
				});
			}

			$vendorList.append($folderDiv);
		}

		$resultsDiv.show();
	}

	function saveIgnoredPaths(plugin, paths) {
		$spinner.addClass('is-active');
		$confirmBtn.prop('disabled', true);

		const payload = new FormData();
		payload.append('action', 'plugin_check_save_ignored_paths');
		payload.append('nonce', PLUGIN_CHECK.nonce);
		payload.append('plugin', plugin);
		payload.append('paths', JSON.stringify(paths));

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
		.then(response => response.json())
		.then(data => {
			$spinner.removeClass('is-active');
			$confirmBtn.prop('disabled', false);
			
			if (data.success) {
				alert('Vendor paths saved successfully!');
			} else {
				alert('Error: ' + (data.data.message || 'Failed to save'));
			}
		})
		.catch(error => {
			$spinner.removeClass('is-active');
			$confirmBtn.prop('disabled', false);
			alert('Error saving paths: ' + error.message);
		});
	}
});
