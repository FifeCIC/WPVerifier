/**
 * Admin Settings JavaScript
 */

/* global pluginCheckSettings */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const providerSelect = document.getElementById( 'ai_provider' );
		const apiKeyInput = document.getElementById( 'ai_api_key' );
		const modelSelect = document.getElementById( 'ai_model' );
		const form = providerSelect ? providerSelect.closest('form') : null;

		if ( ! providerSelect || ! apiKeyInput || ! modelSelect ) {
			return;
		}

		// Add validation status indicators
		apiKeyInput.insertAdjacentHTML('afterend', '<span class="validation-status" id="api-key-status" style="margin-left: 10px;"></span>');
		modelSelect.insertAdjacentHTML('afterend', '<span class="validation-status" id="model-status" style="margin-left: 10px;"></span>');

		let savedModelValue = modelSelect.dataset.initialValue || '';
		let isFirstLoad = true;
		let apiKeyTimeout;

		// Real-time API key validation
		apiKeyInput.addEventListener('input', function() {
			clearTimeout(apiKeyTimeout);
			const key = this.value.trim();
			const provider = providerSelect.value;

			if (!key || !provider) {
				document.getElementById('api-key-status').innerHTML = '';
				return;
			}

			document.getElementById('api-key-status').innerHTML = '<span style="color: #999;">⏳ Validating...</span>';

			apiKeyTimeout = setTimeout(function() {
				validateApiKey(provider, key);
			}, 1000);
		});

		// Model change validation
		modelSelect.addEventListener('change', function() {
			const model = this.value;
			if (model) {
				document.getElementById('model-status').innerHTML = '<span style="color: #46b450;">✓ Valid</span>';
				savedModelValue = model;
			} else {
				document.getElementById('model-status').innerHTML = '';
			}
		});

		// Form submission validation
		if (form) {
			form.addEventListener('submit', function(e) {
				const provider = providerSelect.value;
				const apiKey = apiKeyInput.value.trim();
				const model = modelSelect.value;

				if (provider && !apiKey) {
					e.preventDefault();
					alert('Please enter an API key for the selected provider.');
					apiKeyInput.focus();
					return false;
				}

				if (provider && apiKey && !model) {
					e.preventDefault();
					alert('Please select a model.');
					modelSelect.focus();
					return false;
				}
			});
		}

		function validateApiKey(provider, apiKey) {
			const formData = new FormData();
			formData.append('action', 'plugin_check_get_models');
			formData.append('nonce', pluginCheckSettings.nonce);
			formData.append('provider', provider);
			formData.append('api_key', apiKey);

			fetch(pluginCheckSettings.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			})
			.then(response => response.json())
			.then(response => {
				if (response.success && response.data && Object.keys(response.data).length > 0) {
					document.getElementById('api-key-status').innerHTML = '<span style="color: #46b450;">✓ Valid</span>';
					updateModelOptions(provider, response.data);
				} else {
					document.getElementById('api-key-status').innerHTML = '<span style="color: #d63638;">✗ Invalid or no models available</span>';
					modelSelect.innerHTML = '<option value="">' + pluginCheckSettings.noModelsText + '</option>';
					modelSelect.disabled = true;
				}
			})
			.catch(error => {
				console.error('Validation error:', error);
				document.getElementById('api-key-status').innerHTML = '<span style="color: #d63638;">✗ Validation failed</span>';
			});
		}

		function updateModelOptions(provider, models) {
			modelSelect.innerHTML = '<option value="">' + pluginCheckSettings.selectModelText + '</option>';

			if (models) {
				Object.keys(models).forEach(key => {
					const option = document.createElement('option');
					option.value = key;
					option.textContent = models[key];
					if (savedModelValue === key) {
						option.selected = true;
					}
					modelSelect.appendChild(option);
				});
			}

			modelSelect.disabled = false;
			document.getElementById('model-status').innerHTML = '';
		}

		function updateFields() {
			const provider = providerSelect.value;

			if ( provider ) {
				apiKeyInput.disabled = false;
				modelSelect.disabled = false;
				if (apiKeyInput.value.trim()) {
					validateApiKey(provider, apiKeyInput.value.trim());
				}
			} else {
				apiKeyInput.disabled = true;
				modelSelect.disabled = true;
				modelSelect.value = '';
				savedModelValue = '';
				document.getElementById('api-key-status').innerHTML = '';
				document.getElementById('model-status').innerHTML = '';
			}
		}

		providerSelect.addEventListener( 'change', updateFields );
		updateFields();
	} );
} )();
