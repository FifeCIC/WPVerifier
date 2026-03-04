jQuery(document).ready(function($) {
	if (typeof wpvSavedResults === 'undefined') {
		return;
	}
	
	let aiGuidanceConfig = {};
	
	// Load AI guidance config
	if (typeof wpvConfig !== 'undefined' && wpvConfig.pluginUrl) {
		fetch(wpvConfig.pluginUrl + 'ai-guidance-config.json')
			.then(response => response.json())
			.then(data => {
				aiGuidanceConfig = data;
			})
			.catch(err => console.log('AI guidance config not loaded'));
	}
	
	$('.accordion-header').on('click', function() {
		const $header = $(this);
		$header.toggleClass('active').next('.accordion-content').slideToggle(200);
		
		// Update File Details panel
		const file = $header.closest('.accordion-row').find('.wpv-ast-file-name').text();
		const issues = wpvSavedResults[file] || [];
		
		$('#file-details').html(`
			<div class="wpv-detail-info">
				<strong>File:</strong> ${file}
			</div>
			<div class="wpv-detail-info">
				<strong>Total Issues:</strong> ${issues.length}
			</div>
		`);
	});
	
	// Sidebar accordion functionality
	$('.wpv-accordion-header').on('click', function() {
		const $header = $(this);
		const targetId = $header.data('target');
		const $content = $('#' + targetId);
		const $icon = $header.find('.dashicons');
		
		$content.slideToggle(200);
		$icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
	});
	
	$('.wpv-ast-issue-item').on('click', function(e) {
		e.stopPropagation();
		const file = $(this).data('file');
		const idx = $(this).data('idx');
		const issue = wpvSavedResults[file][idx];
		
		const aiPrompt = `File: ${file}
Line: ${issue.line}
Code: ${issue.code}
Message: ${issue.message}

Please fix this issue in the file from my workspace.`;
		
		const guidance = aiGuidanceConfig[issue.code];
		const detailedPrompt = guidance ? `File: ${file}
Line: ${issue.line}
Code: ${issue.code}
Message: ${issue.message}

AI Guidance:
${guidance.ai_guidance}

Please fix this issue in the file from my workspace.` : aiPrompt;
		
		// Copy to clipboard
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
			<div class="wpv-detail-header">
				<div class="wpv-detail-content">
					<div class="wpv-detail-info">
						<strong>Line:</strong> ${issue.line}
					</div>
					<div class="wpv-detail-info">
						<strong>Code:</strong> <code>${issue.code}</code>
					</div>
					<div class="wpv-detail-info">
						<strong>Message:</strong><br>${$('<div>').text(issue.message).html()}
					</div>
				</div>
				<span class="wpv-ast-badge ${issue.type.toLowerCase()}">${issue.type}</span>
			</div>
			<div class="wpv-ast-detail-actions">
				<button type="button" class="button wpv-copy-ai-btn">
					<span class="dashicons dashicons-clipboard"></span> Copy for AI
				</button>
				<a href="vscode://file/${file}:${issue.line}:${issue.column || 0}" class="button">
					<span class="dashicons dashicons-editor-code"></span> VSCode
				</a>
				<button type="button" class="button wpv-recheck-file-btn" data-file="${file}">
					<span class="dashicons dashicons-update"></span> Recheck File
				</button>
				<a href="#" class="button button-primary wpv-fixed-btn" data-issue-id="${issue.issue_id || ''}">
					<span class="dashicons dashicons-yes"></span> Fixed
				</a>
				<a href="#" class="button wpv-ignore-btn" data-file="${file}" data-code="${issue.code}">
					<span class="dashicons dashicons-hidden"></span> Ignore Code
				</a>
				${issue.docs ? `<a href="${issue.docs}" target="_blank" class="button">Learn More</a>` : ''}
			</div>
		`);
		
		$('#wpv-ai-guidance-panel').html(`
			${guidance ? `<div class="wpv-detail-info">
				<strong>AI Prompt:</strong><br>
				<pre class="wpv-prompt-pre">${detailedPrompt}</pre>
			</div>` : `<div class="wpv-detail-info">
				<strong>AI Prompt:</strong><br>
				<pre class="wpv-prompt-pre">${aiPrompt}</pre>
			</div>`}
			${guidance ? `<button type="button" class="button button-primary wpv-copy-detailed-btn">
				<span class="dashicons dashicons-clipboard"></span> Copy AI Prompt
			</button>` : `<button type="button" class="button button-primary wpv-copy-prompt-btn">
				<span class="dashicons dashicons-clipboard"></span> Copy AI Prompt
			</button>`}
		`);
		
		// Bind button events
		$('.wpv-copy-ai-btn, .wpv-copy-prompt-btn').on('click', function() {
			navigator.clipboard.writeText(aiPrompt).then(() => {
				const $btn = $(this);
				const originalHtml = $btn.html();
				$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
				setTimeout(() => $btn.html(originalHtml), 2000);
			});
		});
		
		$('.wpv-copy-detailed-btn').on('click', function() {
			navigator.clipboard.writeText(detailedPrompt).then(() => {
				const $btn = $(this);
				const originalHtml = $btn.html();
				$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
				setTimeout(() => $btn.html(originalHtml), 2000);
			});
		});
		
		$('.wpv-recheck-file-btn').on('click', function() {
			const $btn = $(this);
			const file = $btn.data('file');
			const originalHtml = $btn.html();
			
			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Rechecking...');
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpv_recheck_file',
					file: file
				},
				success: function(response) {
					if (response.success) {
						alert('File rechecked successfully. Reload the page to see updates.');
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
		
		$('.wpv-fixed-btn').on('click', function(e) {
			e.preventDefault();
			const issueId = $(this).data('issue-id');
			if (issueId) {
				window.location.href = ajaxurl.replace('admin-ajax.php', 'admin-post.php') + '?action=wpv_mark_fixed&issue_id=' + encodeURIComponent(issueId);
			}
		});
		
		$('.wpv-ignore-btn').on('click', function(e) {
			e.preventDefault();
			const file = $(this).data('file');
			const code = $(this).data('code');
			const url = window.location.pathname + '?page=wp-verifier&tab=results&action=ignore_code&file=' + encodeURIComponent(file) + '&code=' + encodeURIComponent(code);
			window.location.href = url;
		});
	});
});
