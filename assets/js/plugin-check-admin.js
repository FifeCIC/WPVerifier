( function ( pluginCheck ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const resultsContainer = document.getElementById( 'plugin-check__results' );
	const exportContainer = document.getElementById(
		'plugin-check__export-controls'
	);
	const spinner = document.getElementById( 'plugin-check__spinner' );
	const pluginsList = document.getElementById(
		'plugin-check__plugins-dropdown'
	);
	const categoriesList = document.querySelectorAll(
		'input[name=categories]'
	);
	const typesList = document.querySelectorAll( 'input[name=types]' );
	const templates = {};

	// Return early if the elements cannot be found on the page.
	if (
		! checkItButton ||
		! pluginsList ||
		! resultsContainer ||
		! spinner ||
		! categoriesList.length ||
		! typesList.length
	) {
		// Elements not found - probably on a different tab
		return;
	}

	let aggregatedResults = createEmptyAggregatedResults();
	let checksCompleted = false;
	exportContainer.classList.add( 'is-hidden' );
	exportContainer.addEventListener( 'click', onExportContainerClick );

	const includeExperimental = document.getElementById(
		'plugin-check__include-experimental'
	);

	// Handle disabling the Check it button when a plugin is not selected.
	function canRunChecks() {
		if ( '' === pluginsList.value ) {
			checkItButton.disabled = true;
		} else {
			checkItButton.disabled = false;
		}
	}

	// Run on page load to test if dropdown is auto populated.
	canRunChecks();
	pluginsList.addEventListener( 'change', canRunChecks );
	pluginsList.addEventListener( 'change', checkForExcludeFolders );

	function saveUserSettings() {
		const selectedCategories = [];

		// Assuming you have a list of category checkboxes, find the selected ones.
		categoriesList.forEach( function ( checkbox ) {
			if ( checkbox.checked ) {
				selectedCategories.push( checkbox.value );
			}
		} );

		// Join the selected category slugs with '__' and save it as a user setting.
		const settingValue = selectedCategories.join( '__' );
		window.setUserSetting(
			'plugin_check_category_preferences',
			settingValue
		);
	}

	// Attach the saveUserSettings function when a category checkbox is clicked.
	categoriesList.forEach( function ( checkbox ) {
		checkbox.addEventListener( 'change', saveUserSettings );
	} );

	/**
	 * Check for vendor/libraries/library folders when plugin is selected.
	 *
	 * @since 1.9.0
	 */
	function checkForExcludeFolders() {
		const excludeContainer = document.getElementById('plugin-check__exclude-folders');
		if (!excludeContainer || !pluginsList.value) {
			if (excludeContainer) excludeContainer.style.display = 'none';
			return;
		}

		const payload = new FormData();
		payload.append('action', 'plugin_check_detect_folders');
		payload.append('plugin', pluginsList.value);
		payload.append('nonce', pluginCheck.nonce);

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.folders && data.data.folders.length > 0) {
				let html = '<h4 style="margin: 10px 0 5px 0;">Exclude Folders</h4>';
				data.data.folders.forEach(folder => {
					html += `<label style="display: block; margin: 5px 0;"><input type="checkbox" name="exclude_folders" value="${folder}" checked> ${folder}/</label>`;
				});
				excludeContainer.innerHTML = html;
				excludeContainer.style.display = 'block';
			} else {
				excludeContainer.style.display = 'none';
			}
		})
		.catch(error => {
			console.error('Error detecting folders:', error);
			excludeContainer.style.display = 'none';
		});
	}

	// When the Check it button is clicked.
	checkItButton.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		// Save last selected plugin
		if ( pluginsList.value ) {
			const payload = new FormData();
			payload.append( 'action', 'save_user_meta' );
			payload.append( 'meta_key', 'wpv_last_selected_plugin' );
			payload.append( 'meta_value', pluginsList.value );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: payload } );
		}

		// Show pre-check summary
		showPreCheckSummary();
	} );

	/**
	 * Show pre-check summary before running checks.
	 *
	 * @since 1.9.0
	 */
	function showPreCheckSummary() {
		const selectedPlugin = pluginsList.options[pluginsList.selectedIndex]?.text || 'Unknown';
		const selectedCategories = [];
		const selectedTypes = [];
		const excludedFolders = [];

		categoriesList.forEach( ( checkbox ) => {
			if ( checkbox.checked ) {
				selectedCategories.push( checkbox.nextSibling.textContent.trim() );
			}
		} );

		typesList.forEach( ( checkbox ) => {
			if ( checkbox.checked ) {
				selectedTypes.push( checkbox.nextSibling.textContent.trim() );
			}
		} );

		const excludeCheckboxes = document.querySelectorAll('input[name="exclude_folders"]:checked');
		excludeCheckboxes.forEach(cb => excludedFolders.push(cb.value));

		const includeExp = includeExperimental && includeExperimental.checked;

		const modal = document.createElement( 'div' );
		modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; display: flex; align-items: center; justify-content: center;';
		modal.innerHTML = `
			<div style="background: #fff; padding: 30px; border-radius: 4px; max-width: 500px; width: 90%;">
				<h2 style="margin: 0 0 20px 0;">Pre-Check Summary</h2>
				<div style="margin-bottom: 20px;">
					<strong>Plugin:</strong> ${selectedPlugin}
				</div>
				<div style="margin-bottom: 20px;">
					<strong>Categories (${selectedCategories.length}):</strong><br>
					${selectedCategories.length ? selectedCategories.join(', ') : 'None selected'}
				</div>
				<div style="margin-bottom: 20px;">
					<strong>Result Types:</strong><br>
					${selectedTypes.join(', ')}
				</div>
				${excludedFolders.length ? `<div style="margin-bottom: 20px; color: #2271b1;"><strong>📁 Excluded Folders:</strong><br>${excludedFolders.join(', ')}</div>` : ''}
				${includeExp ? '<div style="margin-bottom: 20px; color: #dba617;"><strong>⚠ Experimental checks included</strong></div>' : ''}
				<div style="display: flex; gap: 10px; justify-content: flex-end;">
					<button type="button" class="button button-secondary" id="precheck-cancel">Cancel</button>
					<button type="button" class="button button-primary" id="precheck-start">Start Verification</button>
				</div>
			</div>
		`;

		document.body.appendChild( modal );

		document.getElementById( 'precheck-cancel' ).addEventListener( 'click', () => {
			modal.remove();
		} );

		document.getElementById( 'precheck-start' ).addEventListener( 'click', () => {
			modal.remove();
			startVerification();
		} );
	}

	/**
	 * Start the verification process.
	 *
	 * @since 1.9.0
	 */
	function startVerification() {
		resetResults();
		checkItButton.disabled = true;
		pluginsList.disabled = true;
		spinner.classList.add( 'is-active' );
		for ( let i = 0; i < categoriesList.length; i++ ) {
			categoriesList[ i ].disabled = true;
		}
		for ( let i = 0; i < typesList.length; i++ ) {
			typesList[ i ].disabled = true;
		}

		getChecksToRun()
			.then( setUpEnvironment )
			.then( runChecks )
			.then( cleanUpEnvironment )
			.then( ( data ) => {
				console.log( data.message );
				resetForm();
			} )
			.catch( ( error ) => {
				console.error( error );
				resetForm();
			} );
	}



	// File monitoring
	const startMonitorBtn = document.getElementById('plugin-check__start-monitor');
	const stopMonitorBtn = document.getElementById('plugin-check__stop-monitor');
	const viewLogBtn = document.getElementById('plugin-check__view-log');
	const monitorStatus = document.getElementById('plugin-check__monitor-status');
	let monitorInterval;

	if (startMonitorBtn) {
		startMonitorBtn.addEventListener('click', () => {
			if ('' === pluginsList.value) {
				alert('Please select a plugin first.');
				return;
			}
			startMonitoring(pluginsList.value);
		});
	}

	if (stopMonitorBtn) {
		stopMonitorBtn.addEventListener('click', stopMonitoring);
	}

	if (viewLogBtn) {
		viewLogBtn.addEventListener('click', showMonitorLog);
	}

	function startMonitoring(plugin) {
		const payload = new FormData();
		payload.append('nonce', pluginCheck.nonce);
		payload.append('action', 'plugin_check_start_monitoring');
		payload.append('plugin', plugin);

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				startMonitorBtn.style.display = 'none';
				stopMonitorBtn.style.display = 'inline-block';
				monitorStatus.innerHTML = '<span style="color: #46b450;">✓ Monitoring active</span>';
				monitorInterval = setInterval(checkFileChanges, 5000);
			} else {
				alert('Failed to start monitoring: ' + (data.data?.message || 'Unknown error'));
			}
		})
		.catch(error => {
			console.error(error);
			alert('Failed to start monitoring.');
		});
	}

	function stopMonitoring() {
		const payload = new FormData();
		payload.append('nonce', pluginCheck.nonce);
		payload.append('action', 'plugin_check_stop_monitoring');

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				startMonitorBtn.style.display = 'inline-block';
				stopMonitorBtn.style.display = 'none';
				monitorStatus.innerHTML = '';
				if (monitorInterval) clearInterval(monitorInterval);
			}
		});
	}

	function checkFileChanges() {
		const payload = new FormData();
		payload.append('nonce', pluginCheck.nonce);
		payload.append('action', 'plugin_check_file_changes');

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.changed) {
				monitorStatus.innerHTML = '<span style="color: #d63638;">⚠ Changes detected! Running checks...</span>';
				if (window.wp && window.wp.a11y) {
					window.wp.a11y.speak('File changes detected. Running verification checks.');
				}
				setTimeout(() => {
					checkItButton.click();
				}, 1000);
			}
		});
	}

	function showMonitorLog() {
		const payload = new FormData();
		payload.append('nonce', pluginCheck.nonce);
		payload.append('action', 'plugin_check_monitor_log');

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				const log = data.data.log;
				let html = '<h3>Monitoring Log</h3><div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
				if (log.length === 0) {
					html += '<p>No log entries yet.</p>';
				} else {
					log.forEach(entry => {
						html += `<div style="margin-bottom: 8px; padding: 8px; background: #fff; border-left: 3px solid #2271b1;"><strong>${entry.time}</strong><br>${entry.message}</div>`;
					});
				}
				html += '</div>';
				const modal = document.createElement('div');
				modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; display: flex; align-items: center; justify-content: center;';
				modal.innerHTML = `<div style="background: #fff; padding: 20px; border-radius: 4px; max-width: 600px; width: 90%;">${html}<button type="button" class="button" style="margin-top: 15px;" onclick="this.closest('div[style*=fixed]').remove()">Close</button></div>`;
				document.body.appendChild(modal);
			}
		});
	}

	/**
	 * Reset the results container.
	 *
	 * @since 1.0.0
	 */
	function resetResults() {
		// Empty the results container.
		resultsContainer.innerText = '';
		exportContainer.innerHTML = '';
		exportContainer.classList.add( 'is-hidden' );
		resetAggregatedResults();
		checksCompleted = false;
	}

	/**
	 * Resets the form controls once checks have completed or failed.
	 *
	 * @since 1.0.0
	 */
	function resetForm() {
		spinner.classList.remove( 'is-active' );
		checkItButton.disabled = false;
		pluginsList.disabled = false;
		for ( let i = 0; i < categoriesList.length; i++ ) {
			categoriesList[ i ].disabled = false;
		}
		for ( let i = 0; i < typesList.length; i++ ) {
			typesList[ i ].disabled = false;
		}
	}

	function createEmptyAggregatedResults() {
		return {
			errors: {},
			warnings: {},
		};
	}

	function resetAggregatedResults() {
		aggregatedResults = createEmptyAggregatedResults();
	}

	function mergeAggregatedResults( results ) {
		if ( results.errors ) {
			mergeResultTree( aggregatedResults.errors, results.errors );
		}
		if ( results.warnings ) {
			mergeResultTree( aggregatedResults.warnings, results.warnings );
		}
	}

	function hasOwn( object, key ) {
		return Object.prototype.hasOwnProperty.call( object, key );
	}

	function mergeResultTree( target, source ) {
		for ( const file of Object.keys( source ) ) {
			if ( ! hasOwn( target, file ) ) {
				target[ file ] = {};
			}

			const sourceFile = source[ file ];
			const targetFile = target[ file ];

			for ( const line of Object.keys( sourceFile ) ) {
				if ( ! hasOwn( targetFile, line ) ) {
					targetFile[ line ] = {};
				}

				const sourceLine = sourceFile[ line ];
				const targetLine = targetFile[ line ];

				for ( const column of Object.keys( sourceLine ) ) {
					if ( ! hasOwn( targetLine, column ) ) {
						targetLine[ column ] = [];
					}

					for ( const entry of sourceLine[ column ] ) {
						targetLine[ column ].push( cloneResultEntry( entry ) );
					}
				}
			}
		}
	}

	function cloneResultEntry( entry ) {
		return { ...entry };
	}

	function hasAggregatedResults() {
		return (
			hasEntries( aggregatedResults.errors ) ||
			hasEntries( aggregatedResults.warnings )
		);
	}

	function hasEntries( tree ) {
		for ( const file of Object.keys( tree ) ) {
			const lines = tree[ file ] || {};

			for ( const line of Object.keys( lines ) ) {
				const columns = lines[ line ] || {};

				for ( const column of Object.keys( columns ) ) {
					if ( ( columns[ column ] || [] ).length > 0 ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	function defaultString( key ) {
		if (
			pluginCheck.strings &&
			Object.prototype.hasOwnProperty.call( pluginCheck.strings, key )
		) {
			return pluginCheck.strings[ key ];
		}
		// Return empty string if localized string is missing.
		return '';
	}

	function renderExportButtons() {
		exportContainer.innerHTML = '';
		if ( ! checksCompleted ) {
			exportContainer.classList.add( 'is-hidden' );
			return;
		}

		exportContainer.classList.remove( 'is-hidden' );

		const downloadButtonConfigs = [
			{ format: 'csv', label: defaultString( 'downloadCsv' ), action: 'download' },
			{ format: 'json', label: defaultString( 'downloadJson' ), action: 'download' },
			{ format: 'markdown', label: defaultString( 'downloadMarkdown' ), action: 'download' },
		];

		const saveButtonConfigs = [
			{ format: 'csv', label: defaultString( 'saveCsv' ), action: 'save' },
			{ format: 'json', label: defaultString( 'saveJson' ), action: 'save' },
			{ format: 'markdown', label: defaultString( 'saveMarkdown' ), action: 'save' },
		];

		downloadButtonConfigs.forEach( ( item ) => {
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.classList.add( 'button', 'button-secondary', 'plugin-check__export-button' );
			button.textContent = item.label;
			button.setAttribute( 'data-export-format', item.format );
			button.setAttribute( 'data-export-action', item.action );
			exportContainer.appendChild( button );
		} );

		saveButtonConfigs.forEach( ( item ) => {
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.classList.add( 'button', 'button-secondary', 'plugin-check__save-button' );
			button.textContent = item.label;
			button.setAttribute( 'data-export-format', item.format );
			button.setAttribute( 'data-export-action', item.action );
			exportContainer.appendChild( button );
		} );
	}

	function announce( message ) {
		if ( window.wp && window.wp.a11y && window.wp.a11y.speak ) {
			window.wp.a11y.speak( message );
			return;
		}

		console.warn( message );
	}

	function onExportContainerClick( event ) {
		const button = event.target.closest( '[data-export-format]' );
		if ( ! button || button.disabled ) {
			return;
		}

		event.preventDefault();
		const action = button.getAttribute( 'data-export-action' );
		if ( action === 'save' ) {
			handleSave( button );
		} else {
			handleExport( button );
		}
	}

	function handleExport( button ) {
		if ( ! hasAggregatedResults() ) {
			announce( defaultString( 'noResults' ) );
			return;
		}

		const format = button.getAttribute( 'data-export-format' );
		if ( ! format ) {
			return;
		}

		const originalText = button.textContent;
		button.disabled = true;
		button.textContent = defaultString( 'exporting' );

		requestExport( format )
			.then( ( payload ) => {
				downloadExport( payload );
			} )
			.catch( ( error ) => {
				console.error( error );
				const failureMessage = defaultString( 'exportError' );
				announce( failureMessage );
			} )
			.finally( () => {
				button.disabled = false;
				button.textContent = originalText;
			} );
	}

	function requestExport( format ) {
		const payload = new FormData();
		payload.append( 'nonce', pluginCheck.nonce );
		payload.append( 'action', pluginCheck.actionExportResults );
		payload.append( 'format', format );
		if ( pluginsList.value ) {
			payload.append( 'plugin', pluginsList.value );
		}
		payload.append( 'plugin_label', getSelectedPluginLabel() );
		payload.append( 'results', JSON.stringify( aggregatedResults ) );

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload,
		} )
			.then( ( response ) => response.json() )
			.then( ( responseData ) => {
				if ( ! responseData ) {
					throw new Error( 'Response contains no data' );
				}

				if ( ! responseData.success ) {
					const defaultExportErrorMessage =
						defaultString( 'exportError' );
					let message = defaultExportErrorMessage;
					if ( responseData.data && responseData.data.message ) {
						message = responseData.data.message;
					}
					throw new Error( message );
				}

				if (
					! responseData.data ||
					! responseData.data.content ||
					! responseData.data.filename
				) {
					throw new Error( 'Export payload is incomplete' );
				}

				return responseData.data;
			} );
	}

	function downloadExport( exportPayload ) {
		const blob = new Blob( [ exportPayload.content ], {
			type: exportPayload.mime_type || 'text/plain',
		} );
		const downloadLink = document.createElement( 'a' );
		downloadLink.href = window.URL.createObjectURL( blob );
		downloadLink.download = exportPayload.filename;
		document.body.appendChild( downloadLink );
		downloadLink.click();
		document.body.removeChild( downloadLink );
		window.URL.revokeObjectURL( downloadLink.href );
	}

	function handleSave( button ) {
		if ( ! hasAggregatedResults() ) {
			announce( defaultString( 'noResults' ) );
			return;
		}

		const format = button.getAttribute( 'data-export-format' );
		if ( ! format ) {
			return;
		}

		const originalText = button.textContent;
		button.disabled = true;
		button.textContent = defaultString( 'saving' );

		requestSave( format )
			.then( ( response ) => {
				announce( defaultString( 'saveSuccess' ) );
				checkForSavedResults();
			} )
			.catch( ( error ) => {
				console.error( error );
				const failureMessage = defaultString( 'saveError' );
				announce( failureMessage );
			} )
			.finally( () => {
				button.disabled = false;
				button.textContent = originalText;
			} );
	}

	function requestSave( format ) {
		const payload = new FormData();
		payload.append( 'nonce', pluginCheck.nonce );
		payload.append( 'action', pluginCheck.actionSaveResults );
		payload.append( 'format', format );
		if ( pluginsList.value ) {
			payload.append( 'plugin', pluginsList.value );
		}
		payload.append( 'plugin_label', getSelectedPluginLabel() );
		
		// Add checked categories
		const checkedCategories = [];
		for ( let i = 0; i < categoriesList.length; i++ ) {
			if ( categoriesList[ i ].checked ) {
				checkedCategories.push( categoriesList[ i ].value );
			}
		}
		payload.append( 'categories', JSON.stringify( checkedCategories ) );
		
		// Add metadata to results
		const resultsWithMeta = {
			...aggregatedResults,
			meta: {
				checked_categories: checkedCategories,
				timestamp: new Date().toISOString()
			}
		};
		payload.append( 'results', JSON.stringify( resultsWithMeta ) );

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload,
		} )
			.then( ( response ) => response.json() )
			.then( ( responseData ) => {
				if ( ! responseData ) {
					throw new Error( 'Response contains no data' );
				}

				if ( ! responseData.success ) {
					const defaultSaveErrorMessage = defaultString( 'saveError' );
					let message = defaultSaveErrorMessage;
					if ( responseData.data && responseData.data.message ) {
						message = responseData.data.message;
					}
					throw new Error( message );
				}

				return responseData.data;
			} );
	}

	function showLoadDialog() {
		const payload = new FormData();
		payload.append( 'nonce', pluginCheck.nonce );
		payload.append( 'action', pluginCheck.actionLoadResults );
		payload.append( 'plugin', pluginsList.value );

		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload,
		} )
			.then( ( response ) => response.json() )
			.then( ( responseData ) => {
				if ( ! responseData.success ) {
					alert( responseData.data.message || 'No saved results found.' );
					return;
				}

				loadSavedResults( responseData.data.path );
			} )
			.catch( ( error ) => {
				console.error( error );
				alert( 'Failed to load saved results.' );
			} );
	}

	function checkForSavedResults() {
		if ( '' === pluginsList.value ) {
			return;
		}

		const payload = new FormData();
		payload.append( 'nonce', pluginCheck.nonce );
		payload.append( 'action', pluginCheck.actionLoadResults );
		payload.append( 'plugin', pluginsList.value );

		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload,
		} )
			.then( ( response ) => response.json() )
			.then( ( responseData ) => {
				// Results checked
			} )
			.catch( ( error ) => {
				// Error checking
			} );
	}

	function loadSavedResultsList() {
		const listContainer = document.getElementById( 'plugin-check__saved-results-list' );
		if ( ! listContainer ) {
			return;
		}

		if ( ! pluginCheck.actionListSavedResults ) {
			listContainer.innerHTML = '<p style="color: #d63638;">Configuration error.</p>';
			return;
		}

		const payload = new FormData();
		payload.append( 'nonce', pluginCheck.nonce );
		payload.append( 'action', pluginCheck.actionListSavedResults );

		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload,
		} )
			.then( ( response ) => response.json() )
			.then( ( responseData ) => {
				if ( ! responseData.success || ! responseData.data.results.length ) {
					listContainer.innerHTML = '<p style="color: #666;">No saved results found.</p>';
					return;
				}

				let html = '<ul style="list-style: none; margin: 0; padding: 0;">';
				responseData.data.results.forEach( ( item ) => {
					const date = new Date( item.modified * 1000 ).toLocaleDateString();
					html += '<li style="margin-bottom: 8px;"><a href="#" class="plugin-check__load-saved" data-path="' + item.path + '" style="text-decoration: none; display: block; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">' +
						'<strong>' + item.plugin + '</strong><br>' +
						'<small style="color: #666;">' + date + '</small>' +
						'</a></li>';
				} );
				html += '</ul>';
				listContainer.innerHTML = html;

				listContainer.querySelectorAll( '.plugin-check__load-saved' ).forEach( ( link ) => {
					link.addEventListener( 'click', ( e ) => {
						e.preventDefault();
						loadSavedResults( link.getAttribute( 'data-path' ) );
					} );
				} );
			} )
			.catch( ( error ) => {
				console.error( 'Error loading saved results list:', error );
				listContainer.innerHTML = '<p style="color: #d63638;">Error loading saved results.</p>';
			} );
	}

	function loadSavedResults( filePath ) {
		const normalizedPath = filePath.replace( /\\/g, '/' );
		const contentPath = normalizedPath.split( '/wp-content/' )[1];
		if ( ! contentPath ) {
			alert( 'Invalid file path.' );
			return;
		}
		// Use WordPress content URL from current page URL
		const currentUrl = window.location.href;
		const wpContentBase = currentUrl.substring( 0, currentUrl.indexOf( '/wp-admin/' ) ) + '/wp-content/';
		const contentUrl = wpContentBase + contentPath;
		
		fetch( contentUrl )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'File not found: ' + contentUrl );
				}
				return response.json();
			} )
			.then( ( data ) => {
				resetResults();
				// Data now includes metadata from saved JSON
				aggregatedResults = {
					errors: data.errors || {},
					warnings: data.warnings || {}
				};
				const readiness = data.readiness || null;
				const rediscovered = data.rediscovered || [];
				renderResultsMessage( false, readiness, rediscovered );
			} )
			.catch( ( error ) => {
				console.error( error );
				alert( 'Failed to load results: ' + error.message );
			} );
	}

	function getSelectedPluginLabel() {
		const selectedIndex = pluginsList.selectedIndex;
		if ( selectedIndex < 0 ) {
			return '';
		}
		return pluginsList.options[ selectedIndex ].text;
	}

	/**
	 * Setup the runtime environment if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} data Data object with props passed to form data.
	 */
	function setUpEnvironment( data ) {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', data.plugin );
		pluginCheckData.append(
			'action',
			pluginCheck.actionSetUpRuntimeEnvironment
		);
		pluginCheckData.append(
			'include-experimental',
			includeExperimental && includeExperimental.checked ? 1 : 0
		);

		for ( let i = 0; i < data.checks.length; i++ ) {
			pluginCheckData.append( 'checks[]', data.checks[ i ] );
		}

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				if ( ! responseData.data || ! responseData.data.message ) {
					throw new Error( 'Response contains no data.' );
				}

				console.log( responseData.data.message );

				return responseData.data;
			} );
	}

	/**
	 * Cleanup the runtime environment.
	 *
	 * @since 1.0.0
	 *
	 * @return {Object} The response data.
	 */
	function cleanUpEnvironment() {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append(
			'action',
			pluginCheck.actionCleanUpRuntimeEnvironment
		);

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				if ( ! responseData.data || ! responseData.data.message ) {
					throw new Error( 'Response contains no data.' );
				}

				return responseData.data;
			} );
	}

	/**
	 * Get the Checks to run.
	 *
	 * @since 1.0.0
	 */
	function getChecksToRun() {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );
		pluginCheckData.append( 'action', pluginCheck.actionGetChecksToRun );
		pluginCheckData.append(
			'include-experimental',
			includeExperimental && includeExperimental.checked ? 1 : 0
		);

		for ( let i = 0; i < categoriesList.length; i++ ) {
			if ( categoriesList[ i ].checked ) {
				pluginCheckData.append(
					'categories[]',
					categoriesList[ i ].value
				);
			}
		}

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				if (
					! responseData.data ||
					! responseData.data.plugin ||
					! responseData.data.checks
				) {
					throw new Error(
						'Plugin and Checks are missing from the response.'
					);
				}

				return responseData.data;
			} );
	}

	/**
	 * Run Checks.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} data The response data.
	 */
	async function runChecks( data ) {
		let isSuccessMessage = true;
		const totalChecks = data.checks.length;
		const startTime = Date.now();
		let aggregatedReadiness = null;
		let aggregatedRediscovered = [];
		let aggregatedCompleted = {};
		
		// Create progress indicator
		const progressDiv = document.createElement('div');
		progressDiv.id = 'plugin-check__progress';
		progressDiv.style.cssText = 'margin: 20px 0; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1; border-radius: 4px;';
		progressDiv.innerHTML = `
			<div style="margin-bottom: 10px;">
				<strong style="font-size: 14px;">Running check 0 of ${totalChecks}...</strong>
				<span style="float: right; color: #2271b1; font-weight: 600;">0%</span>
			</div>
			<div style="background: #fff; height: 8px; border-radius: 4px; overflow: hidden;">
				<div id="plugin-check__progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s ease;"></div>
			</div>
			<div id="plugin-check__progress-details" style="margin-top: 8px; font-size: 12px; color: #646970;"></div>
		`;
		resultsContainer.appendChild(progressDiv);
		
		const progressBar = document.getElementById('plugin-check__progress-bar');
		const progressDetails = document.getElementById('plugin-check__progress-details');
		
		for ( let i = 0; i < data.checks.length; i++ ) {
			const current = i + 1;
			const percent = Math.round((current / totalChecks) * 100);
			const elapsed = Math.round((Date.now() - startTime) / 1000);
			const checkName = data.checks[i].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
			
			// Update progress display
			progressDiv.querySelector('strong').textContent = `Running check ${current} of ${totalChecks}...`;
			progressDiv.querySelector('span').textContent = `${percent}%`;
			progressBar.style.width = `${percent}%`;
			progressDetails.innerHTML = `<strong>${checkName}</strong> • Elapsed: ${elapsed}s`;
			
			try {
				const results = await runCheck( data.plugin, data.checks[ i ] );
				
				const errorsLength = Object.keys( results.errors || {} ).length;
				const warningsLength = Object.keys( results.warnings || {} ).length;
				
				if (
					isSuccessMessage &&
					( errorsLength > 0 || warningsLength > 0 )
				) {
					isSuccessMessage = false;
				}
				if ( results.readiness ) {
					aggregatedReadiness = results.readiness;
				}
				if ( results.rediscovered ) {
					aggregatedRediscovered = results.rediscovered;
				}
				if ( results.completed ) {
					aggregatedCompleted = results.completed;
				}
				mergeAggregatedResults( results );
				renderResults( results, aggregatedRediscovered );
			} catch ( e ) {
				console.error(`Check ${data.checks[i]} failed:`, e);
			}
		}
		
		// Remove progress indicator
		progressDiv.remove();

		// Auto-save results if enabled
		if (pluginCheck && pluginCheck.autoSaveResults) {
			const savePayload = new FormData();
			savePayload.append('nonce', pluginCheck.nonce);
			savePayload.append('action', pluginCheck.actionSaveResults);
			savePayload.append('format', 'json');
			if (pluginsList.value) {
				savePayload.append('plugin', pluginsList.value);
			}
			savePayload.append('plugin_label', getSelectedPluginLabel());
			
			const resultsWithMeta = {
				errors: aggregatedResults.errors,
				warnings: aggregatedResults.warnings,
				readiness: aggregatedReadiness,
				rediscovered: aggregatedRediscovered,
				completed: aggregatedCompleted
			};
			savePayload.append('results', JSON.stringify(resultsWithMeta));
			
			showSaveStatus('Saving results and calculating readiness score...');
			
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: savePayload
			}).then(response => {
				return response.json();
			}).then(data => {
				if (data.success && data.data && data.data.path) {
					const normalizedPath = data.data.path.replace( /\\/g, '/' );
					const contentPath = normalizedPath.split( '/wp-content/' )[1];
					if (contentPath) {
						const currentUrl = window.location.href;
						const wpContentBase = currentUrl.substring( 0, currentUrl.indexOf( '/wp-admin/' ) ) + '/wp-content/';
						const contentUrl = wpContentBase + contentPath;
						fetch(contentUrl)
							.then(r => r.json())
							.then(jsonData => {
								console.log('About to call renderResultsMessage from auto-save success');
								renderResultsMessage( false, jsonData.readiness || null, aggregatedRediscovered );
								showSaveStatus('✓ Results saved successfully', 'success');
								checkForSavedResults();
								loadSavedResultsList();
							})
							.catch(err => {
								console.error('Failed to load saved JSON:', err);
								renderResultsMessage( false, aggregatedReadiness, aggregatedRediscovered );
								showSaveStatus('✓ Results saved', 'success');
							});
					}
				} else {
					console.error('Save failed:', data);
					renderResultsMessage( false, aggregatedReadiness, aggregatedRediscovered );
					showSaveStatus('✗ Save failed', 'error');
				}
			}).catch(error => {
				console.error('Auto-save error:', error);
				renderResultsMessage( false, aggregatedReadiness, aggregatedRediscovered );
				showSaveStatus('✗ Save failed', 'error');
			});
		} else {
			renderResultsMessage( false, aggregatedReadiness, aggregatedRediscovered );
		}
	}

	/**
	 * Show save status message above export buttons.
	 *
	 * @since 1.9.0
	 *
	 * @param {string} message Status message.
	 * @param {string} type Message type (success, error, or default).
	 */
	function showSaveStatus(message, type = 'info') {
		const readinessDiv = resultsContainer.querySelector('div[style*="margin: 20px 0"]');
		if (!readinessDiv) return;
		
		let statusDiv = readinessDiv.querySelector('#save-status');
		if (!statusDiv) {
			statusDiv = document.createElement('div');
			statusDiv.id = 'save-status';
			const buttonsDiv = readinessDiv.querySelector('div[style*="border-top"]');
			if (buttonsDiv) {
				readinessDiv.insertBefore(statusDiv, buttonsDiv);
			}
		}
		
		const colors = {
			success: '#00a32a',
			error: '#d63638',
			info: '#2271b1'
		};
		
		statusDiv.style.cssText = `margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 3px solid ${colors[type]}; color: ${colors[type]}; font-size: 13px;`;
		statusDiv.textContent = message;
	}

	/**
	 * Renders result message.
	 *
	 * @since 1.0.0
	 *
	 * @param {boolean} isSuccessMessage Whether the message is a success message.
	 * @param {Object} readiness Readiness score data.
	 * @param {Array} rediscovered Rediscovered issues.
	 */
	function renderResultsMessage( isSuccessMessage, readiness, rediscovered ) {
		console.log('renderResultsMessage called with readiness:', readiness);
		let html = '';

		// Show rediscovered issues warning
		if ( rediscovered && rediscovered.length > 0 ) {
			html += `<div style="margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
				<strong style="color: #856404;">⚠ ${rediscovered.length} Previously Completed Issue(s) Rediscovered</strong>
				<p style="margin: 10px 0 0 0; color: #856404;">These issues were marked as complete but have reappeared. They are marked with ⚠ in the results.</p>
			</div>`;
		}

		// Add readiness score display if available
		if ( readiness && readiness.overall !== undefined ) {
			html += renderReadinessScore( readiness );
		}

		// Clear and set the results container
		resultsContainer.innerHTML = html;

		checksCompleted = true;
		
		// Render export buttons directly into placeholder
		renderExportButtons();
		const placeholder = document.getElementById('export-buttons-placeholder');
		if (placeholder && exportContainer.children.length > 0) {
			placeholder.addEventListener('click', onExportContainerClick);
			while (exportContainer.firstChild) {
				const btn = exportContainer.firstChild;
				btn.style.marginRight = '8px';
				placeholder.appendChild(btn);
			}
			exportContainer.classList.add('is-hidden');
		}

		// Add structure validation after readiness score and export buttons
		if (pluginsList && pluginsList.value) {
			const payload = new FormData();
			payload.append('nonce', pluginCheck.nonce);
			payload.append('action', 'plugin_check_validate_structure');
			payload.append('plugin', pluginsList.value);
			
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(response => response.json())
			.then(data => {
				if (data.success && data.data.validation) {
					const validation = data.data.validation;
					const checks = [
						{key: 'readme_file', label: 'README File', data: validation.readme_file},
						{key: 'license_file', label: 'LICENSE File', data: validation.license_file},
						{key: 'language_folder', label: 'Language Folder', data: validation.language_folder},
						{key: 'language_files', label: 'Language Files (.pot)', data: validation.language_files}
					];
					
					const allPass = checks.every(c => c.data.status === 'pass');
					const statusColor = allPass ? '#00a32a' : '#dba617';
					const statusText = allPass ? 'All Required Files Present' : 'Some Files Missing or Incomplete';
					
					let structureHtml = `
						<div id="structure-validation-results" style="margin: 25px 0; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
							<h3 style="margin: 0 0 15px 0; font-size: 16px; color: ${statusColor};">${statusText}</h3>
							<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
					`;
					
					checks.forEach(check => {
						const icon = check.data.status === 'pass' ? '✓' : (check.data.status === 'warning' ? '⚠' : '✗');
						const color = check.data.status === 'pass' ? '#00a32a' : (check.data.status === 'warning' ? '#dba617' : '#d63638');
						const detail = check.data.file || check.data.path || check.data.message || '';
						const escapedDetail = document.createElement('div');
						escapedDetail.textContent = detail;
						
						structureHtml += `
							<div style="padding: 8px; border-left: 3px solid ${color}; background: #f9f9f9;">
								<div style="font-weight: 600; color: ${color};">${icon} ${check.label}</div>
								<div style="font-size: 12px; color: #666; margin-top: 4px;">${escapedDetail.innerHTML}</div>
							</div>
						`;
					});
					
					structureHtml += `
							</div>
						</div>
					`;
					
					const readinessContainer = document.getElementById('readiness-score-container');
					if (readinessContainer) {
						const tempDiv = document.createElement('div');
						tempDiv.innerHTML = structureHtml;
						const structureElement = tempDiv.querySelector('#structure-validation-results');
						if (structureElement) {
							readinessContainer.insertAdjacentElement('afterend', structureElement);
							console.log('Structure validation inserted after readiness container');
						}
					} else {
						console.error('Readiness container not found!');
					}
				}
			})
			.catch(error => console.error('Structure validation error:', error));
		}
	}



	/**
	 * Render readiness score display.
	 *
	 * @since 1.9.0
	 *
	 * @param {Object} readiness Readiness score data.
	 * @return {string} HTML for readiness score.
	 */
	function renderReadinessScore( readiness ) {
		if (!readiness || readiness.overall === undefined) {
			console.log('No readiness data, returning empty');
			return '';
		}
		
		console.log('Rendering readiness score:', readiness);
		
		const statusColors = {
			excellent: '#00a32a',
			good: '#72aee6',
			fair: '#dba617',
			'needs-work': '#d63638'
		};
		const statusLabels = {
			excellent: 'Excellent - Ready for Submission',
			good: 'Good - Minor Issues',
			fair: 'Fair - Needs Improvement',
			'needs-work': 'Needs Work - Major Issues'
		};

		const color = statusColors[readiness.status] || '#646970';
		const label = statusLabels[readiness.status] || readiness.status;

		return `
			<div id="readiness-score-container" style="margin: 20px 0; padding: 25px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
				<div style="display: flex; align-items: center; gap: 30px;">
					<div style="text-align: center; min-width: 120px;">
						<div style="font-size: 64px; font-weight: 700; color: ${color}; line-height: 1;">${readiness.overall}</div>
						<div style="font-size: 12px; color: #646970; margin-top: 5px;">out of 100</div>
					</div>
					<div style="flex: 1;">
						<h3 style="margin: 0 0 8px 0; font-size: 20px;">Readiness Score: ${getSelectedPluginLabel()}</h3>
						<div style="font-size: 16px; color: ${color}; font-weight: 600; margin-bottom: 10px;">${label}</div>
						<div style="font-size: 14px; color: #646970;">
							<strong>${readiness.errors}</strong> error${readiness.errors !== 1 ? 's' : ''} • 
							<strong>${readiness.warnings}</strong> warning${readiness.warnings !== 1 ? 's' : ''}
						</div>
					</div>
				</div>
				<div id="export-buttons-placeholder" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;"></div>
			</div>
		`;
	}

	/**
	 * Run a single check.
	 *
	 * @since 1.0.0
	 *
	 * @param {string} plugin The plugin to check.
	 * @param {string} check  The check to run.
	 * @return {Object} The check results.
	 */
	function runCheck( plugin, check ) {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', plugin );
		pluginCheckData.append( 'checks[]', check );
		pluginCheckData.append( 'action', pluginCheck.actionRunChecks );
		pluginCheckData.append(
			'include-experimental',
			includeExperimental && includeExperimental.checked ? 1 : 0
		);

		for ( let i = 0; i < typesList.length; i++ ) {
			if ( typesList[ i ].checked ) {
				pluginCheckData.append( 'types[]', typesList[ i ].value );
			}
		}

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				// If the response is successful and there is no message in the response.
				if ( ! responseData.data || ! responseData.data.message ) {
					throw new Error( 'Response contains no data' );
				}

				return responseData.data;
			} );
	}

	/**
	 * Handles any errors in the data returned from the response.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} data The response data.
	 * @return {Object} The response data.
	 */
	function handleDataErrors( data ) {
		if ( ! data ) {
			throw new Error( 'Response contains no data' );
		}

		if ( ! data.success ) {
			// If not successful and no message in the response.
			if ( ! data.data || ! data.data[ 0 ].message ) {
				throw new Error( 'Response contains no data' );
			}

			// If not successful and there is a message in the response.
			throw new Error( data.data[ 0 ].message );
		}

		return data;
	}

	/**
	 * Renders results for each check on the page.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} results The results object.
	 * @param {Array} rediscovered Rediscovered issues array.
	 */
	function renderResults( results, rediscovered ) {
		// Skip rendering individual check results - only show summary
		return;
	}

	/**
	 * Renders the file results table.
	 *
	 * @since 1.0.0
	 *
	 * @param {string} file     The file name for the results.
	 * @param {Object} errors   The file errors.
	 * @param {Object} warnings The file warnings.
	 */
	function renderFileResults( file, errors, warnings ) {
		// Skip - AST handles all rendering
		return;
	}

	/**
	 * Checks if there are any links in the results object.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} results The results object.
	 * @return {boolean} True if there are links, false otherwise.
	 */
	function hasLinksInResults( results ) {
		for ( const line in results ) {
			for ( const column in results[ line ] ) {
				for ( let i = 0; i < results[ line ][ column ].length; i++ ) {
					if ( results[ line ][ column ][ i ].link ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Renders a result row onto the file table.
	 *
	 * @since 1.0.0
	 *
	 * @param {string}  type     The result type. Either ERROR or WARNING.
	 * @param {Object}  results  The results object.
	 * @param {Object}  table    The HTML table to append a result row to.
	 * @param {boolean} hasLinks Whether any result has links.
	 */
	function renderResultRows( type, results, table, hasLinks ) {
		// Loop over each result by the line, column and messages.
		for ( const line in results ) {
			for ( const column in results[ line ] ) {
				for ( let i = 0; i < results[ line ][ column ].length; i++ ) {
					const message = results[ line ][ column ][ i ].message;
					const docs = results[ line ][ column ][ i ].docs;
					const code = results[ line ][ column ][ i ].code;
					const link = results[ line ][ column ][ i ].link;

					table.innerHTML += renderTemplate(
						'plugin-check-results-row',
						{
							line,
							column,
							type,
							message,
							docs,
							code,
							link,
							hasLinks,
						}
					);
				}
			}
		}
	}

	/**
	 * Renders the template with data.
	 *
	 * @since 1.0.0
	 *
	 * @param {string} templateSlug The template slug
	 * @param {Object} data         Template data.
	 * @return {string} Template HTML.
	 */
	function renderTemplate( templateSlug, data ) {
		if ( ! templates[ templateSlug ] ) {
			templates[ templateSlug ] = wp.template( templateSlug );
		}
		const template = templates[ templateSlug ];
		return template( data );
	}
} )( PLUGIN_CHECK ); /* global PLUGIN_CHECK */

