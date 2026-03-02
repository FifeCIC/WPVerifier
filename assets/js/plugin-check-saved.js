jQuery(document).ready(function($) {
	let currentData = null;
	let ignoredIssues = [];
	
	console.log('wpvAiGuidance loaded:', window.wpvAiGuidance);
	
	// Auto-load last selected plugin results
	const lastSelected = localStorage.getItem('wpv_last_selected_result');
	if (lastSelected) {
		const normalizedPath = lastSelected.replace(/\\/g, '/');
		const contentPath = normalizedPath.split('/wp-content/')[1];
		if (contentPath) {
			const currentUrl = window.location.href;
			const wpContentBase = currentUrl.substring(0, currentUrl.indexOf('/wp-admin/')) + '/wp-content/';
			const contentUrl = wpContentBase + contentPath;
			
			console.log('Loading results from:', contentUrl);
			
			fetch(contentUrl)
				.then(response => {
					if (!response.ok) throw new Error('Failed to load');
					return response.json();
				})
				.then(data => {
					window.wpvResultsData = data;
					
					if (data.results) {
						currentData = data.results;
						renderTable(data.results);
					} else if (data.errors || data.warnings) {
						const combined = {};
						Object.keys(data.errors || {}).forEach(file => {
							if (!combined[file]) combined[file] = [];
							Object.values(data.errors[file]).forEach(line => {
								Object.values(line).forEach(col => {
									col.forEach(issue => combined[file].push({...issue, type: 'ERROR'}));
								});
							});
						});
						Object.keys(data.warnings || {}).forEach(file => {
							if (!combined[file]) combined[file] = [];
							Object.values(data.warnings[file]).forEach(line => {
								Object.values(line).forEach(col => {
									col.forEach(issue => combined[file].push({...issue, type: 'WARNING'}));
								});
							});
						});
						currentData = combined;
						renderTable(combined);
					} else {
						// No results data found
						renderTable({});
					}
				})
				.catch(err => {
					console.error('Failed to load results:', err);
					$('#no-results-notice').show();
					$('#results-table-wrapper').hide();
				});
		} else {
			console.log('No valid content path found');
		}
	} else {
		console.log('No last selected plugin found');
		$('#no-results-notice').show();
		$('#results-table-wrapper').hide();
	}
	
	function renderTable(results) {
		const $table = $('#saved-results-table');
		$table.empty();
		
		const hasResults = Object.keys(results).length > 0;
		
		if (!hasResults) {
			$('#no-results-notice').show();
			$('#results-table-wrapper').hide();
			return;
		}
		
		$('#no-results-notice').hide();
		$('#results-table-wrapper').show();
		
		Object.keys(results).forEach(file => {
			const items = results[file];
			const fileName = file.split(/[\\\/]/).pop();
			const errorCount = items.filter(i => i.type === 'ERROR').length;
			const warningCount = items.filter(i => i.type === 'WARNING').length;
			
			const $row = $(`
				<div class="accordion-row" style="border: 1px solid #c3c4c7; border-top: none; background: #fff;">
					<div class="accordion-header" style="display: flex; gap: 15px;">
						<div class="wpv-ast-file-name" style="flex: 2;">${fileName}</div>
						<div class="wpv-ast-severity" style="flex: 1;">
							${errorCount > 0 ? `<span class="wpv-ast-badge error">${errorCount} errors</span>` : ''}
							${warningCount > 0 ? `<span class="wpv-ast-badge warning">${warningCount} warnings</span>` : ''}
						</div>
					</div>
					<div class="accordion-content" style="display: none;">
						<ul class="wpv-ast-issue-list"></ul>
					</div>
				</div>
			`);
			
			const $list = $row.find('.wpv-ast-issue-list');
			items.forEach((item, idx) => {
				$list.append(`
					<li class="wpv-ast-issue-item" data-file="${file}" data-idx="${idx}">
						<span class="wpv-ast-badge ${item.type.toLowerCase()}">${item.type}</span>
						Line ${item.line}: ${$('<div>').text(item.message).html()}
					</li>
				`);
			});
			
			$table.append($row);
		});
		
		bindEvents();
	}
	
	function bindEvents() {
		$('.accordion-header').off('click').on('click', function() {
			const $header = $(this);
			const $content = $header.next('.accordion-content');
			
			$header.toggleClass('active');
			$content.slideToggle(200);
		});
		
		$('.wpv-ast-issue-item').off('click').on('click', function(e) {
			e.stopPropagation();
			const file = $(this).data('file');
			const idx = $(this).data('idx');
			const item = currentData[file][idx];
			
			// Get plugin slug from localStorage path
			const lastSelected = localStorage.getItem('wpv_last_selected_result');
			const pluginSlug = lastSelected ? lastSelected.replace(/\\/g, '/').split('/').slice(-2, -1)[0] : 'wpseed';
			
			const currentUrl = new URL(window.location.href);
			const ignoreUrl = currentUrl.origin + currentUrl.pathname + '?page=wp-verifier&tab=results&action=ignore_code&plugin=' + encodeURIComponent(pluginSlug) + '&file=' + encodeURIComponent(file) + '&code=' + encodeURIComponent(item.code) + '&_wpnonce=' + (typeof PLUGIN_CHECK !== 'undefined' ? PLUGIN_CHECK.nonce : '');
			
			// Generate proper nonce URL using AJAX
			const adminPath = currentUrl.pathname.substring(0, currentUrl.pathname.indexOf('/plugins.php'));
			let fixedUrl = adminPath + '/admin-post.php?action=wpv_mark_fixed&plugin=' + encodeURIComponent(pluginSlug) + '&issue_id=' + encodeURIComponent(item.issue_id);
			
			// Get nonce via AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				async: false,
				data: {
					action: 'wpv_get_mark_fixed_nonce'
				},
				success: function(response) {
					if (response.success && response.data.nonce) {
						fixedUrl += '&_wpnonce=' + response.data.nonce;
					}
				}
			});
			
			// Build AI prompt
			const guidance = window.wpvAiGuidance && window.wpvAiGuidance[item.code] ? window.wpvAiGuidance[item.code].ai_guidance : '';
			console.log('AI Guidance for', item.code, ':', guidance);
			const aiPrompt = `I have a WordPress plugin verification error:

File: ${file}
Filename: ${file.split(/[\\\\/]/).pop()}
Line: ${item.line}, Column: ${item.column}
Type: ${item.type}
Code: ${item.code}
Message: ${$('<div>').text(item.message).text()}${guidance ? '\n\nAI Guidance: ' + guidance : ''}

Please fix this issue in the file from my workspace. The file is already in the workspace context.`;
			
			navigator.clipboard.writeText(aiPrompt).then(() => {
				const toast = $('<div class="wpv-toast">✓ Copied to clipboard</div>');
				$('body').append(toast);
				setTimeout(() => toast.addClass('show'), 10);
				setTimeout(() => {
					toast.removeClass('show');
					setTimeout(() => toast.remove(), 300);
				}, 3000);
			});
			
			$('#saved-results-details').html(`
				<div style="position: relative;">
					<span class="wpv-ast-badge ${item.type.toLowerCase()}" style="position: absolute; top: 0; right: 0;">${item.type}</span>
				</div>
				<div class="wpv-ast-detail-group" style="display: flex; gap: 10px; align-items: baseline;">
					<label style="min-width: 80px; font-weight: 600;">Issue ID:</label>
					<code>${item.issue_id || 'N/A'}</code>
				</div>
				<div class="wpv-ast-detail-group" style="display: flex; gap: 10px; align-items: baseline;">
					<label style="min-width: 80px; font-weight: 600;">Filename:</label>
					<strong>${file.split(/[\\\\/]/).pop()}</strong>
				</div>
				<div class="wpv-ast-detail-group" style="display: flex; gap: 10px; align-items: baseline;">
					<label style="min-width: 80px; font-weight: 600;">Line:</label>
					<span>${item.line}</span>
				</div>
				<div class="wpv-ast-detail-group" style="display: flex; gap: 10px; align-items: baseline;">
					<label style="min-width: 80px; font-weight: 600;">Code:</label>
					<code>${item.code}</code>
				</div>
				<div class="wpv-ast-detail-group">
					<label style="font-weight: 600; display: block; margin-bottom: 5px;">Message:</label>
					<p style="margin: 0;">${$('<div>').text(item.message).html()}</p>
				</div>
				<div class="wpv-ast-detail-group">
					<label style="font-weight: 600; display: block; margin-bottom: 5px;">Path:</label>
					<code style="font-size: 11px; word-break: break-all; display: block;">${file}</code>
				</div>
				<div class="wpv-ast-detail-actions">
					<a href="vscode://file/${file}:${item.line}:${item.column}" class="button">
						<span class="dashicons dashicons-editor-code"></span> VSCode
					</a>
					<button type="button" class="button wpv-recheck-file" data-file="${file}" data-plugin="${pluginSlug}">
						<span class="dashicons dashicons-update"></span> Recheck File
					</button>
					<a href="${fixedUrl}" class="button button-primary">
						<span class="dashicons dashicons-yes"></span> Fixed
					</a>
					<a href="${ignoreUrl}" class="button">
						<span class="dashicons dashicons-hidden"></span> Ignore Code
					</a>
					${item.docs ? `<a href="${item.docs}" target="_blank" class="button">Learn More</a>` : ''}
				</div>
			`);
			
			// Populate AI Guidance panel
			$('#wpv-ai-guidance-panel').html(`
				${guidance ? `<div class="wpv-ast-detail-group">
					<label style="font-weight: 600; display: block; margin-bottom: 5px;">Guidance:</label>
					<p style="margin: 0 0 15px 0; line-height: 1.6;">${guidance}</p>
				</div>` : ''}
				<div class="wpv-ast-detail-group">
					<label style="font-weight: 600; display: block; margin-bottom: 5px;">Instructions:</label>
					<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto;">${aiPrompt.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
				</div>
				<button type="button" class="button button-primary wpv-copy-ai" data-prompt="${aiPrompt.replace(/"/g, '&quot;')}">
					<span class="dashicons dashicons-clipboard"></span> Copy Instructions for AI
				</button>
			`);
			
			$('.wpv-copy-ai').off('click').on('click', function() {
				const prompt = $(this).data('prompt');
				navigator.clipboard.writeText(prompt).then(() => {
					const $btn = $(this);
					const originalText = $btn.html();
					$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
					setTimeout(() => $btn.html(originalText), 2000);
				});
			});
			
			$('.wpv-recheck-file').off('click').on('click', function() {
				const $btn = $(this);
				const file = $btn.data('file');
				const plugin = $btn.data('plugin');
				const originalHtml = $btn.html();
				
				$btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Rechecking...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wpv_recheck_file',
						nonce: typeof PLUGIN_CHECK !== 'undefined' ? PLUGIN_CHECK.nonce : '',
						plugin: plugin,
						file: file
					},
					success: function(response) {
						if (response.success) {
							alert('File rechecked successfully. Reload the results to see updates.');
							$('.load-result').first().click();
						} else {
							alert('Error: ' + (response.data?.message || 'Unknown error'));
						}
					},
					error: function() {
						alert('Failed to recheck file.');
					},
					complete: function() {
						$btn.prop('disabled', false).html(originalHtml);
					}
				});
			});
		});
	}
	
	// Accordion functionality
	$('.wpv-accordion-header').on('click', function() {
		const $header = $(this);
		const targetId = $header.data('target');
		const $content = $('#' + targetId);
		const $icon = $header.find('.dashicons');
		
		$content.slideToggle(200);
		$icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
	});
});
