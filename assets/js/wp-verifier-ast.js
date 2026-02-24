/**
 * AST (Accordion Sidebar Table) Functionality
 * @package WPVerifier
 */

(function($) {
	'use strict';

	window.WPVerifierAST = {
		knownLibraries: [],
		rediscovered: [],
		ignoredFolders: [],
		
		init: function(results, rediscovered) {
			console.log('=== WPVerifierAST.init called ===');
			console.log('Results received:', results);
			console.log('Rediscovered:', rediscovered);
			console.log('Container #wpv-ast-results exists:', $('#wpv-ast-results').length);
			
			this.results = results;
			this.rediscovered = rediscovered || [];
			this.currentPlugin = document.getElementById('plugin-check__plugins-dropdown') ? document.getElementById('plugin-check__plugins-dropdown').value : '';
			
			console.log('Current plugin:', this.currentPlugin);
			
			this.loadKnownLibraries();
			this.loadIgnoreRules();
			this.loadIgnoredPaths();
			this.filterIgnoredIssues();
			
			console.log('About to call render()...');
			this.render();
			console.log('render() completed');
			
			this.renderIgnoredFolders();
			this.validateStructure();
			this.bindEvents();
			
			console.log('=== WPVerifierAST.init completed ===');
		},

		loadKnownLibraries: function() {
			if (window.WPVerifierLibraries) {
				this.knownLibraries = window.WPVerifierLibraries;
			}
		},

		loadIgnoreRules: function() {
			this.ignoreRules = [];
			this.ignoredFolders = [];
			const allRules = window.wpvIgnoreRules || {};
			if (this.currentPlugin && allRules[this.currentPlugin]) {
				this.ignoreRules = allRules[this.currentPlugin];
				this.ignoredFolders = this.extractIgnoredFolders(this.ignoreRules);
			}
			console.log('Ignored folders:', this.ignoredFolders);
		},

		loadIgnoredPaths: function() {
			this.ignoredPaths = [];
			if (window.wpvResultsData && window.wpvResultsData.ignored_paths) {
				this.ignoredPaths = window.wpvResultsData.ignored_paths;
				this.ignoredPaths.forEach(item => {
					if (item.path && !this.ignoredFolders.includes(item.path)) {
						this.ignoredFolders.push(item.path);
					}
				});
			}
		},

		extractIgnoredFolders: function(ignoreRules) {
			const folders = new Set();
			ignoreRules.forEach(rule => {
				if (rule.code === '*' && rule.file.includes('/')) {
					const folder = rule.file.split('/')[0];
					folders.add(folder);
				}
			});
			return Array.from(folders).sort();
		},

		renderIgnoredFolders: function() {
			const container = $('#wpv-ast-ignored-folders');
			const list = $('#wpv-ignored-folders-list');
			
			if (!this.ignoredFolders || this.ignoredFolders.length === 0) {
				container.hide();
				return;
			}
			
			list.empty();
			this.ignoredFolders.forEach(folder => {
				const fromPrep = this.ignoredPaths.some(p => p.path === folder);
				const badge = fromPrep ? '<span style="font-size: 10px; background: #2271b1; color: white; padding: 2px 6px; border-radius: 3px; margin-left: 8px;">Preparation</span>' : '';
				list.append(`<li style="padding: 4px 0; color: #666;"><span class="dashicons dashicons-hidden" style="font-size: 14px; width: 14px; height: 14px;"></span> ${this.escapeHtml(folder)}${badge}</li>`);
			});
			container.show();
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
								const isIgnored = this.isIgnored(file, issue.code) || this.isInIgnoredPath(file);
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

		isInIgnoredPath: function(file) {
			return this.ignoredPaths.some(item => file.includes(item.path));
		},

		isLibraryFile: function(file) {
			return this.knownLibraries.some(lib => file.includes(lib));
		},

		render: function() {
			console.log('=== render() called ===');
			const container = $('#wpv-ast-results');
			console.log('Container found:', container.length);
			console.log('Container HTML before clear:', container.html());
			
			container.empty();

			const files = this.groupByFile(this.results);
			console.log('Files grouped:', Object.keys(files).length, 'files');
			console.log('Files:', Object.keys(files));
			
			if (Object.keys(files).length === 0) {
				console.warn('No files to render!');
				container.html('<p style="padding: 20px; color: #666;">No issues found in verification results.</p>');
				return;
			}
			
			Object.keys(files).forEach(file => {
				console.log('Rendering file:', file);
				const issues = files[file];
				console.log('  Issues count:', issues.length);
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
			
			console.log('Container HTML after render:', container.html().substring(0, 200));
			console.log('=== render() completed ===');
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
			console.log('Issue data:', issue);
			const details = $('#wpv-ast-details');
			const aiPrompt = `I have a WordPress plugin verification error:\n\nFile: ${file}\nLine: ${issue.line}, Column: ${issue.column}\nType: ${issue.type}\nCode: ${issue.code}\nMessage: ${$('<div>').html(issue.message).text()}\n\nHow can I fix this?`;
			const isIgnored = this.isIgnored(file, issue.code);
			const isRediscovered = this.isRediscovered(file, issue.line, issue.code);
			
			// Build ignore link URL
			const currentUrl = new URL(window.location.href);
			const ignoreUrl = currentUrl.origin + currentUrl.pathname + '?page=wp-verifier&tab=verify&action=ignore_code&plugin=' + encodeURIComponent(this.currentPlugin) + '&file=' + encodeURIComponent(file) + '&code=' + encodeURIComponent(issue.code) + '&_wpnonce=' + (window.PLUGIN_CHECK ? window.PLUGIN_CHECK.nonce : '');
			
			details.html(`
				<h3>Advanced Verification - Issue Details</h3>
				${isRediscovered ? '<div style="padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107; margin-bottom: 15px;"><strong style="color: #856404;">⚠ Previously Completed</strong><br><small style="color: #856404;">This issue was marked as complete but has reappeared.</small></div>' : ''}
				<div class="wpv-ast-detail-group">
					<label>Issue ID:</label>
					<p><code>${this.escapeHtml(issue.issue_id || 'N/A')}</code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>File:</label>
					<p style="word-break: break-all;">${this.escapeHtml(file)}</p>
				</div>
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
					<button type="button" class="button button-primary wpv-mark-complete" data-file="${this.escapeHtml(file)}" data-line="${issue.line}" data-code="${this.escapeHtml(issue.code)}">
						<span class="dashicons dashicons-yes"></span> Mark Complete
					</button>
					${!isIgnored ? `<a href="${ignoreUrl}" class="button">
						<span class="dashicons dashicons-hidden"></span> Ignore Code
					</a>` : '<span style="color: #999;">✓ Ignored</span>'}
					${issue.docs ? `<a href="${issue.docs}" target="_blank" class="button">Learn More</a>` : ''}
					${issue.link ? `<a href="${issue.link}" target="_blank" class="button">View in Editor</a>` : ''}
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
			
			$('.wpv-mark-complete').off('click').on('click', function() {
				const file = $(this).data('file');
				const line = $(this).data('line');
				const code = $(this).data('code');
				WPVerifierAST.markComplete(file, line, code);
			});
		},

		markComplete: function(file, line, code) {
			if (!window.PLUGIN_CHECK || !window.PLUGIN_CHECK.nonce) {
				alert('Configuration error.');
				return;
			}
			
			const payload = new FormData();
			payload.append('nonce', window.PLUGIN_CHECK.nonce);
			payload.append('action', 'plugin_check_mark_complete');
			payload.append('plugin', this.currentPlugin);
			payload.append('file', file);
			payload.append('line', line);
			payload.append('code', code);
			
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Issue marked as complete.');
					location.reload();
				} else {
					alert('Failed to mark complete: ' + (data.data?.message || 'Unknown error'));
				}
			})
			.catch(error => {
				console.error(error);
				alert('Failed to mark complete.');
			});
		},

		isRediscovered: function(file, line, code) {
			return this.rediscovered.some(r => r.file === file && r.line == line && r.code === code);
		},

		groupByFile: function(results) {
			const files = {};
			
			if (results.errors) {
				Object.entries(results.errors).forEach(([file, lines]) => {
					if (!files[file]) files[file] = [];
					Object.entries(lines).forEach(([lineNum, columns]) => {
						Object.entries(columns).forEach(([colNum, issues]) => {
							issues.forEach(issue => {
								const issueId = 'E-' + this.generateIssueHash(file, lineNum);
								files[file].push({type: 'ERROR', line: parseInt(lineNum), column: parseInt(colNum), issue_id: issueId, ...issue});
							});
						});
					});
				});
			}
			
			if (results.warnings) {
				Object.entries(results.warnings).forEach(([file, lines]) => {
					if (!files[file]) files[file] = [];
					Object.entries(lines).forEach(([lineNum, columns]) => {
						Object.entries(columns).forEach(([colNum, issues]) => {
							issues.forEach(issue => {
								const issueId = 'W-' + this.generateIssueHash(file, lineNum);
								files[file].push({type: 'WARNING', line: parseInt(lineNum), column: parseInt(colNum), issue_id: issueId, ...issue});
							});
						});
					});
				});
			}
			
			return files;
		},

		generateIssueHash: function(file, line) {
			const basename = file.split(/[\\\/]/).pop();
			const str = basename + '-' + line;
			let hash = 0;
			for (let i = 0; i < str.length; i++) {
				const char = str.charCodeAt(i);
				hash = ((hash << 5) - hash) + char;
				hash = hash & hash;
			}
			return Math.abs(hash).toString(16).substring(0, 8).padStart(8, '0');
		},

		escapeHtml: function(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		validateStructure: function() {
			console.log('=== validateStructure called ===');
			console.log('this.currentPlugin:', this.currentPlugin);
			console.log('window.PLUGIN_CHECK:', window.PLUGIN_CHECK);
			if (!this.currentPlugin || !window.PLUGIN_CHECK) {
				console.log('ABORT: Missing plugin or PLUGIN_CHECK');
				return;
			}
			
			const payload = new FormData();
			payload.append('nonce', window.PLUGIN_CHECK.nonce);
			payload.append('action', 'plugin_check_validate_structure');
			payload.append('plugin', this.currentPlugin);
			console.log('Sending structure validation for:', this.currentPlugin);
			
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(response => response.json())
			.then(data => {
				console.log('Structure validation response:', data);
				if (data.success && data.data.validation) {
					this.renderStructureValidation(data.data.validation);
				}
			})
			.catch(error => console.error('Structure validation error:', error));
		},

		renderStructureValidation: function(validation) {
			console.log('renderStructureValidation called with:', validation);
			const container = $('#plugin-check__results');
			console.log('Container found:', container.length);
			const readinessDiv = container.find('div[style*="margin: 20px 0"]').first();
			console.log('Readiness div found:', readinessDiv.length);
			
			if (!readinessDiv.length) return;
			
			const checks = [
				{key: 'readme_file', label: 'README File', data: validation.readme_file},
				{key: 'license_file', label: 'LICENSE File', data: validation.license_file},
				{key: 'language_folder', label: 'Language Folder', data: validation.language_folder},
				{key: 'language_files', label: 'Language Files (.pot)', data: validation.language_files}
			];
			
			const allPass = checks.every(c => c.data.status === 'pass');
			const statusColor = allPass ? '#00a32a' : '#dba617';
			const statusText = allPass ? 'All Required Files Present' : 'Some Files Missing or Incomplete';
			
			let html = `
				<div style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
					<h3 style="margin: 0 0 15px 0; font-size: 16px; color: ${statusColor};">${statusText}</h3>
					<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
			`;
			
			checks.forEach(check => {
				const icon = check.data.status === 'pass' ? '✓' : (check.data.status === 'warning' ? '⚠' : '✗');
				const color = check.data.status === 'pass' ? '#00a32a' : (check.data.status === 'warning' ? '#dba617' : '#d63638');
				const detail = check.data.file || check.data.path || check.data.message || '';
				
				html += `
					<div style="padding: 8px; border-left: 3px solid ${color}; background: #f9f9f9;">
						<div style="font-weight: 600; color: ${color};">${icon} ${check.label}</div>
						<div style="font-size: 12px; color: #666; margin-top: 4px;">${this.escapeHtml(detail)}</div>
					</div>
				`;
			});
			
			html += `
					</div>
				</div>
			`;
			
			readinessDiv.after(html);
		}
	};

})(jQuery);
