jQuery(document).ready(function($) {
	let currentData = null;
	let ignoredIssues = [];
	
	// Check URL for plugin parameter and auto-load
	const urlParams = new URLSearchParams(window.location.search);
	const pluginParam = urlParams.get('plugin');
	if (pluginParam) {
		const targetBox = $('.result-box').filter(function() {
			return $(this).data('path').includes(pluginParam);
		});
		if (targetBox.length > 0) {
			targetBox.find('.load-result').click();
		}
	} else {
		// Load last selected from localStorage
		const lastSelected = localStorage.getItem('wpv_last_selected_result');
		if (lastSelected) {
			const targetBox = $('.result-box').filter(function() {
				const boxPath = $(this).data('path');
				return boxPath === lastSelected || boxPath.replace(/\\/g, '/') === lastSelected.replace(/\\/g, '/');
			});
			if (targetBox.length > 0) {
				setTimeout(() => targetBox.find('.load-result').click(), 100);
			}
		}
	}
	
	// Load ignored issues from saved results
	function loadIgnoredIssues(data) {
		if (!data.errors && !data.warnings) return;
		
		let totalIssues = 0;
		let displayedIssues = 0;
		
		// Count from errors/warnings structure
		if (data.errors) {
			Object.values(data.errors).forEach(lines => {
				Object.values(lines).forEach(cols => {
					Object.values(cols).forEach(issues => {
						totalIssues += issues.length;
					});
				});
			});
		}
		if (data.warnings) {
			Object.values(data.warnings).forEach(lines => {
				Object.values(lines).forEach(cols => {
					Object.values(cols).forEach(issues => {
						totalIssues += issues.length;
					});
				});
			});
		}
		
		// Count displayed issues
		Object.values(currentData).forEach(issues => {
			displayedIssues += issues.length;
		});
		
		ignoredIssues = totalIssues - displayedIssues;
		
		// Show ignored count
		if (ignoredIssues > 0) {
			$('#saved-results-ignored-count').html(`
				<div style="padding: 12px; background: #f0f0f1; border-left: 3px solid #999; margin-bottom: 15px;">
					<strong style="color: #666;">${ignoredIssues} Issue${ignoredIssues !== 1 ? 's' : ''} Ignored</strong>
					<p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">These issues are hidden based on your ignore rules.</p>
				</div>
			`).show();
		} else {
			$('#saved-results-ignored-count').hide();
		}
	}
	
	$('.result-box').on('click', '.load-result', function(e) {
		e.stopPropagation();
		const $box = $(this).closest('.result-box');
		const path = $box.data('path');
		const pluginName = $box.find('strong').text();
		
		// Store selection in localStorage
		localStorage.setItem('wpv_last_selected_result', path);
		
		const normalizedPath = path.replace(/\\/g, '/');
		const contentPath = normalizedPath.split('/wp-content/')[1];
		const currentUrl = window.location.href;
		const wpContentBase = currentUrl.substring(0, currentUrl.indexOf('/wp-admin/')) + '/wp-content/';
		const contentUrl = wpContentBase + contentPath;
		
		$('.result-box').css('border-color', '#ddd');
		$box.css('border-color', '#2271b1');
		
		$('#files-with-issues-heading').text('Files with Issues - ' + pluginName);
		
		fetch(contentUrl)
			.then(response => response.json())
			.then(data => {
				// Make data available globally for AST
				window.wpvResultsData = data;
				
				// Support both old and new format
				if (data.results) {
					currentData = data.results;
					renderTable(data.results);
				} else if (data.errors || data.warnings) {
					// Convert new format to old format for rendering
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
					loadIgnoredIssues(data);
				}
			});
	});
	
	$('.result-box').on('click', '.delete-result', function(e) {
		e.stopPropagation();
		const $box = $(this).closest('.result-box');
		const path = $box.data('path');
		const pluginName = $box.find('strong').text();
		
		if (!confirm('Are you sure you want to delete results for ' + pluginName + '?')) {
			return;
		}
		
		const pluginSlug = path.replace(/\\/g, '/').split('/').slice(-2, -1)[0];
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'plugin_check_delete_results',
				nonce: typeof PLUGIN_CHECK !== 'undefined' ? PLUGIN_CHECK.nonce : '',
				plugin: pluginSlug
			},
			success: function(response) {
				if (response.success) {
					$box.fadeOut(300, function() {
						$(this).remove();
					});
					$('#saved-results-table').empty();
					$('#saved-results-details').html('<div class="wpv-ast-placeholder"><p>Select a result above</p></div>');
					$('#files-with-issues-heading').text('Files with Issues');
				} else {
					alert('Error: ' + response.data.message);
				}
			},
			error: function() {
				alert('Failed to delete results.');
			}
		});
	});
	
	function renderTable(results) {
		const $table = $('#saved-results-table');
		$table.empty();
		
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
					<div class="accordion-content" style="display: block;">
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
			
			const selectedBox = $('.result-box').filter(function() {
				return $(this).css('border-color') === 'rgb(34, 113, 177)';
			});
			const path = selectedBox.data('path');
			const pluginSlug = path.replace(/\\/g, '/').split('/').slice(-2, -1)[0];
			
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
			
			const aiPrompt = `I have a WordPress plugin verification error:\n\nIssue ID: ${item.issue_id || 'N/A'}\nFile: ${file}\nLine: ${item.line}, Column: ${item.column}\nType: ${item.type}\nCode: ${item.code}\nMessage: ${$('<div>').text(item.message).text()}\n\nFix this issue for me.`;
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
				<div class="wpv-ast-detail-group">
					<label>Issue ID:</label>
					<p><code>${item.issue_id || 'N/A'}</code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Filename:</label>
					<span><strong>${file.split(/[\\\\/]/).pop()}</strong></span>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Path:</label>
					<p><code style="font-size: 11px; word-break: break-all;">${file}</code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Type:</label>
					<span class="wpv-ast-badge ${item.type.toLowerCase()}">${item.type}</span>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Line:</label>
					<span>${item.line}</span>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Code:</label>
					<p><code>${item.code}</code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Message:</label>
					<p>${$('<div>').text(item.message).html()}</p>
				</div>
				<div class="wpv-ast-detail-actions">
					<button type="button" class="button wpv-copy-ai" data-prompt="I have a WordPress plugin verification error:\n\nFile: ${file}\nLine: ${item.line}, Column: ${item.column}\nType: ${item.type}\nCode: ${item.code}\nMessage: ${$('<div>').text(item.message).text()}\n\nFix this issue for me.">
						<span class="dashicons dashicons-clipboard"></span> Copy for AI
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
			
			$('.wpv-copy-ai').off('click').on('click', function() {
				const prompt = $(this).data('prompt');
				navigator.clipboard.writeText(prompt).then(() => {
					const $btn = $(this);
					const originalText = $btn.html();
					$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
					setTimeout(() => $btn.html(originalText), 2000);
				});
			});
		});
	}
});
