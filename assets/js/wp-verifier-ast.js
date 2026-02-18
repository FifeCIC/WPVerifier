/**
 * AST (Accordion Sidebar Table) Functionality
 * @package WPVerifier
 */

(function($) {
	'use strict';

	window.WPVerifierAST = {
		knownLibraries: [],
		
		init: function(results) {
			this.results = results;
			this.currentPlugin = document.getElementById('plugin-check__plugins-dropdown') ? document.getElementById('plugin-check__plugins-dropdown').value : '';
			this.loadKnownLibraries();
			this.loadIgnoreRules();
			this.filterIgnoredIssues();
			this.render();
			this.bindEvents();
		},

		loadKnownLibraries: function() {
			if (window.WPVerifierLibraries) {
				this.knownLibraries = window.WPVerifierLibraries;
			}
		},

		loadIgnoreRules: function() {
			this.ignoreRules = [];
			const allRules = window.wpvIgnoreRules || {};
			if (this.currentPlugin && allRules[this.currentPlugin]) {
				this.ignoreRules = allRules[this.currentPlugin];
			}
		},

		filterIgnoredIssues: function() {
			this.ignoredCount = 0;
			['errors', 'warnings'].forEach(type => {
				if (!this.results[type]) return;
				
				Object.keys(this.results[type]).forEach(file => {
					const lines = this.results[type][file];
					Object.keys(lines).forEach(line => {
						const columns = lines[line];
						Object.keys(columns).forEach(column => {
							columns[column] = columns[column].filter(issue => {
								const isIgnored = this.isIgnored(file, issue.code);
								if (isIgnored) this.ignoredCount++;
								return !isIgnored;
							});
						});
					});
				});
			});
		},

		isIgnored: function(file, code) {
			return this.ignoreRules.some(rule => rule.file === file && rule.code === code);
		},

		isLibraryFile: function(file) {
			return this.knownLibraries.some(lib => file.includes(lib));
		},

		render: function() {
			const container = $('#wpv-ast-results');
			container.empty();

			const files = this.groupByFile(this.results);
			
			Object.keys(files).forEach(file => {
				const issues = files[file];
				const errorCount = issues.filter(i => i.type === 'ERROR').length;
				const warningCount = issues.filter(i => i.type === 'WARNING').length;
				const isLibrary = this.isLibraryFile(file);

				const row = $(`
					<div class="accordion-row" data-file="${this.escapeHtml(file)}">
						<div class="accordion-header">
							<div class="wpv-ast-file-name">${this.escapeHtml(file)}</div>
							<div class="wpv-ast-library">${isLibrary ? '<span class="wpv-ast-badge library">Library</span>' : ''}</div>
							<div class="wpv-ast-issue-count">${errorCount + warningCount} issues</div>
							<div class="wpv-ast-severity">
								${errorCount > 0 ? `<span class="wpv-ast-badge error">${errorCount} errors</span>` : ''}
								${warningCount > 0 ? `<span class="wpv-ast-badge warning">${warningCount} warnings</span>` : ''}
							</div>
						</div>
						<div class="accordion-content">
							<ul class="wpv-ast-issue-list"></ul>
						</div>
					</div>
				`);

				const issueList = row.find('.wpv-ast-issue-list');
				issues.forEach((issue, idx) => {
					const messageText = $('<div>').html(issue.message).text();
					issueList.append(`
						<li class="wpv-ast-issue-item" data-issue-id="${idx}">
							<span class="wpv-ast-badge ${issue.type.toLowerCase()}">${issue.type}</span>
							Line ${issue.line}: ${this.escapeHtml(messageText)}
							${issue.docs ? `<a href="${issue.docs}" target="_blank" class="wpv-issue-docs">↗</a>` : ''}
						</li>
					`);
				});

				container.append(row);
			});
		},

		bindEvents: function() {
			$(document).off('click', '.accordion-header').on('click', '.accordion-header', function(e) {
				const $header = $(this);
				const $content = $header.next('.accordion-content');
				
				$('.accordion-header').not($header).removeClass('active');
				$('.accordion-content').not($content).removeClass('active').slideUp(200);
				
				$header.toggleClass('active');
				$content.toggleClass('active').slideToggle(200);
			});

			$(document).off('click', '.wpv-ast-issue-item').on('click', '.wpv-ast-issue-item', function(e) {
				if ($(e.target).hasClass('wpv-issue-docs')) {
					return;
				}
				e.stopPropagation();
				const file = $(this).closest('.accordion-row').data('file');
				const issueId = $(this).data('issue-id');
				const files = WPVerifierAST.groupByFile(WPVerifierAST.results);
				const issue = files[file][issueId];
				WPVerifierAST.showDetails(file, issue);
			});
		},

		showDetails: function(file, issue) {
			const details = $('#wpv-ast-details');
			const aiPrompt = `I have a WordPress plugin verification error:\n\nFile: ${file}\nLine: ${issue.line}, Column: ${issue.column}\nType: ${issue.type}\nCode: ${issue.code}\nMessage: ${$('<div>').html(issue.message).text()}\n\nHow can I fix this?`;
			const isIgnored = this.isIgnored(file, issue.code);
			
			details.html(`
				<h3>${this.escapeHtml(file)}</h3>
				<div class="wpv-ast-detail-group">
					<label>Type:</label>
					<span class="wpv-ast-badge ${issue.type.toLowerCase()}">${issue.type}</span>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Location:</label>
					<p>Line ${issue.line}, Column ${issue.column}</p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Code:</label>
					<p><code>${this.escapeHtml(issue.code)}</code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Message:</label>
					<p>${this.escapeHtml($('<div>').html(issue.message).text())}</p>
				</div>
				<div class="wpv-ast-detail-actions">
					<button type="button" class="button wpv-copy-ai" data-prompt="${this.escapeHtml(aiPrompt)}">
						<span class="dashicons dashicons-clipboard"></span> Copy for AI
					</button>
					${!isIgnored ? `<button type="button" class="button wpv-ignore-code" data-file="${this.escapeHtml(file)}" data-code="${this.escapeHtml(issue.code)}">
						<span class="dashicons dashicons-hidden"></span> Ignore Code
					</button>` : '<span style="color: #999;">✓ Ignored</span>'}
					${issue.docs ? `<a href="${issue.docs}" target="_blank" class="button">Learn More</a>` : ''}
					${issue.link ? `<a href="${issue.link}" target="_blank" class="button button-primary">View in Editor</a>` : ''}
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
			
			$('.wpv-ignore-code').off('click').on('click', function() {
				const file = $(this).data('file');
				const code = $(this).data('code');
				WPVerifierAST.addIgnoreRule(file, code);
			});
		},

		addIgnoreRule: function(file, code) {
			if (!window.PLUGIN_CHECK || !window.PLUGIN_CHECK.actionAddIgnoreRule) {
				alert('Configuration error.');
				return;
			}
			
			const payload = new FormData();
			payload.append('nonce', window.PLUGIN_CHECK.nonce);
			payload.append('action', window.PLUGIN_CHECK.actionAddIgnoreRule);
			payload.append('plugin', this.currentPlugin);
			payload.append('file', file);
			payload.append('code', code);
			
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Issue ignored. Refresh results to see changes.');
					location.reload();
				} else {
					alert('Failed to add ignore rule: ' + (data.data.message || 'Unknown error'));
				}
			})
			.catch(error => {
				console.error(error);
				alert('Failed to add ignore rule.');
			});
		},

		groupByFile: function(results) {
			const files = {};
			
			['errors', 'warnings'].forEach(type => {
				if (!results[type]) return;
				
				Object.keys(results[type]).forEach(file => {
					if (!files[file]) files[file] = [];
					
					const lines = results[type][file];
					Object.keys(lines).forEach(line => {
						const columns = lines[line];
						Object.keys(columns).forEach(column => {
							columns[column].forEach(issue => {
								files[file].push({
									type: type === 'errors' ? 'ERROR' : 'WARNING',
									line: line,
									column: column,
									...issue
								});
							});
						});
					});
				});
			});
			
			return files;
		},

		escapeHtml: function(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

})(jQuery);
