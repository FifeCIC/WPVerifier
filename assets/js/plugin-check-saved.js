jQuery(document).ready(function($) {
	if (typeof wpvSavedResults === 'undefined') {
		return;
	}
	
	$('.accordion-header').on('click', function() {
		const $header = $(this);
		$header.toggleClass('active').next('.accordion-content').slideToggle(200);
		
		// Update File Details panel
		const file = $header.closest('.accordion-row').find('.wpv-ast-file-name').text();
		const issues = wpvSavedResults[file] || [];
		const errorCount = issues.filter(i => i.type === 'ERROR').length;
		const warningCount = issues.filter(i => i.type === 'WARNING').length;
		
		$('#file-details').html(`
			<div style="margin-bottom: 10px;">
				<strong>File:</strong><br>${file}
			</div>
			<div style="margin-bottom: 10px;">
				<strong>Total Issues:</strong> ${issues.length}
			</div>
			<div style="margin-bottom: 10px;">
				<strong>Errors:</strong> <span class="wpv-ast-badge error">${errorCount}</span>
			</div>
			<div style="margin-bottom: 10px;">
				<strong>Warnings:</strong> <span class="wpv-ast-badge warning">${warningCount}</span>
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
			<div style="margin-bottom: 15px;">
				<span class="wpv-ast-badge ${issue.type.toLowerCase()}">${issue.type}</span>
			</div>
			<div style="margin-bottom: 10px;">
				<strong>File:</strong> ${file.split(/[\\\\/]/).pop()}
			</div>
			<div style="margin-bottom: 10px;">
				<strong>Line:</strong> ${issue.line}
			</div>
			<div style="margin-bottom: 10px;">
				<strong>Code:</strong> <code>${issue.code}</code>
			</div>
			<div style="margin-bottom: 10px;">
				<strong>Message:</strong><br>${$('<div>').text(issue.message).html()}
			</div>
			<div class="wpv-ast-detail-actions" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 15px;">
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
			<div style="margin-bottom: 10px;">
				<strong>Instructions:</strong><br>
				<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">${aiPrompt}</pre>
			</div>
			<button type="button" class="button button-primary wpv-copy-prompt-btn">
				<span class="dashicons dashicons-clipboard"></span> Copy Instructions for AI
			</button>
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
