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
		aiGuidanceConfig: {},
		debugMode: window.wpvDebugMode || false, // Set to true for verbose logging or add ?wpv_debug=1 to URL
		
		log: function(message, ...args) {
			if (this.debugMode || new URLSearchParams(window.location.search).get('wpv_debug') === '1') {
				console.log(message, ...args);
			}
		},
		
		error: function(message, ...args) {
			console.error(message, ...args);
		},
		
		init: function(results, rediscovered) {
			this.log('=== WPVerifierAST.init called ===');
			this.log('Results received:', results);
			this.log('Rediscovered:', rediscovered);
			this.log('Container #wpv-ast-results exists:', $('#wpv-ast-results').length);
			this.log('Error metadata loaded:', typeof window.wpvErrorMetadata, window.wpvErrorMetadata);
			
			// Wait for container if not found
			if ($('#wpv-ast-results').length === 0) {
				this.error('Container #wpv-ast-results not found, waiting...');
				setTimeout(() => this.init(results, rediscovered), 100);
				return;
			}
			
			this.results = results;
			this.rediscovered = rediscovered || [];
			this.currentPlugin = document.getElementById('plugin-check__plugins-dropdown') ? document.getElementById('plugin-check__plugins-dropdown').value : '';
			
			this.log('Current plugin:', this.currentPlugin);
			
			this.loadKnownLibraries();
			this.loadIgnoreRules();
			this.loadIgnoredPaths();
			this.loadAIGuidanceConfig();
			this.filterIgnoredIssues();
			
			this.log('About to call render()...');
			this.render();
			this.log('render() completed');
			
			this.renderIgnoredFolders();
			this.validateStructure();
			this.bindEvents();
			
			this.log('=== WPVerifierAST.init completed ===');
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

		loadAIGuidanceConfig: function() {
			const pluginUrl = window.wpvPluginUrl || '';
			if (!pluginUrl) {
				this.log('Plugin URL not available for AI guidance config');
				return;
			}
			
			fetch(pluginUrl + '/ai-guidance-config.json')
				.then(response => response.json())
				.then(data => {
					this.aiGuidanceConfig = data;
					this.log('AI Guidance config loaded:', data);
				})
				.catch(error => {
					this.log('Failed to load AI guidance config:', error);
				});
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
			this.log('=== render() called ===');
			const container = $('#wpv-ast-results');
			const astContainer = $('#wpv-ast-container');
			this.log('Container found:', container.length);
			
			// Show AST container and render results
			if (container.length > 0) {
				astContainer.show();
				this.log('Container HTML before clear:', container.html());
				container.empty();
			} else {
				this.log('Container not available for rendering');
				return;
			}

			const files = this.groupByFile(this.results);
			this.log('Files grouped:', Object.keys(files).length, 'files');
			this.log('Files:', Object.keys(files));
			
			if (Object.keys(files).length === 0) {
				this.error('No files to render!');
				container.html('<p style="padding: 20px; color: #666;">No issues found in verification results.</p>');
				return;
			}
			
			Object.keys(files).forEach(file => {
				this.log('Rendering file:', file);
				const issues = files[file];
				this.log('  Issues count:', issues.length);
				const errorCount = issues.filter(i => i.type === 'ERROR').length;
				const warningCount = issues.filter(i => i.type === 'WARNING').length;
				const fixedCount = issues.filter(i => i.resolved === true).length;
				const ignoredCount = issues.filter(i => i.ignored === true).length;
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
								${fixedCount > 0 ? `<span class="wpv-ast-badge fixed">${fixedCount} fixed</span>` : ''}
								${ignoredCount > 0 ? `<span class="wpv-ast-badge ignored">${ignoredCount} ignored</span>` : ''}
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
					const statusBadge = issue.resolved ? '<span class="wpv-ast-badge fixed">Fixed</span>' : 
										   issue.ignored ? '<span class="wpv-ast-badge ignored">Ignored</span>' : 
										   '<span class="wpv-ast-badge pending">Pending</span>';
					issueList.append(`
						<li class="wpv-ast-issue-item" 
							data-issue-id="${issue.issue_id || idx}" 
							data-file="${this.escapeHtml(file)}"
							data-issue-code="${this.escapeHtml(issue.code || '')}"
							data-issue-line="${issue.line || ''}"
							data-issue-message="${this.escapeHtml(messageText)}"
							data-issue-type="${issue.type || ''}">
							${issue.icon}
							<span class="wpv-ast-badge ${issue.type.toLowerCase()}">${issue.type}</span>
							${statusBadge}
							<span class="wpv-issue-id">[${issue.issue_id || 'NO-ID'}]</span>
							Line ${issue.line}: ${this.escapeHtml(messageText)}
							${issue.docs ? `<a href="${issue.docs}" target="_blank" class="wpv-issue-docs">↗</a>` : ''}
						</li>
					`);
				});

				container.append(row);
			});
			
			const html = container.html();
			this.log('Container HTML after render:', html ? html.substring(0, 200) : 'empty');
			this.log('=== render() completed ===');
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
				const $item = $(this);
				const issueId = $item.data('issue-id');
				const file = $item.data('file');
				const issueCode = $item.data('issue-code');
				const issueLine = $item.data('issue-line');
				const issueMessage = $item.data('issue-message');
				const issueType = $item.data('issue-type');
				
				WPVerifierAST.showDetailsInSidebar(issueId, file, issueCode, issueLine, issueMessage, issueType);
			});
			
			// Handle Fixed button clicks in sidebar
			$(document).off('click', '.wpv-fixed-btn').on('click', '.wpv-fixed-btn', function(e) {
				e.preventDefault();
				const issueId = $(this).data('issue-id');
				const file = $(this).data('file');
				const code = $(this).data('code');
				WPVerifierAST.markComplete(issueId, file, null, code);
			});
			
			// Handle copy prompt button
			$(document).off('click', '.wpv-copy-prompt').on('click', '.wpv-copy-prompt', function(e) {
				e.preventDefault();
				const targetId = $(this).data('target');
				const textarea = document.getElementById(targetId);
				if (textarea) {
					navigator.clipboard.writeText(textarea.value).then(() => {
						const $btn = $(this);
						const originalText = $btn.html();
						$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
						setTimeout(() => $btn.html(originalText), 2000);
					});
				}
			});
		},

		showDetailsInSidebar: function(issueId, file, code, line, message, type) {
			this.log('Showing details in sidebar for issue:', issueId);
			
			// Update PAN01 - Issue Details with simple DOM updates
			$('#wpv-current-issue-id').text(issueId || 'N/A');
			$('#wpv-current-issue-type').text(type || 'UNKNOWN').attr('class', 'wpv-ast-badge ' + (type || 'unknown').toLowerCase());
			$('#wpv-current-issue-file').text(file || 'Unknown file');
			$('#wpv-current-issue-line').text(line || 'N/A');
			$('#wpv-current-issue-code').text(code || 'N/A');
			$('#wpv-current-issue-message').html(message || 'No message');
			
			// Update button data attributes
			$('#wpv-current-fixed-btn').attr({
				'data-issue-id': issueId || '',
				'data-file': file || '',
				'data-code': code || ''
			});
			$('#wpv-current-ignore-btn').attr({
				'data-issue-id': issueId || '',
				'data-file': file || '',
				'data-code': code || ''
			});
			$('#wpv-current-vscode-btn').attr('href', 'vscode://file/' + this.getVSCodePath(file) + ':' + (line || 1));
			
			// Update PAN02 - AI Prompt
			$('#wpv-ai-issue-id').text(issueId || 'N/A');
			const aiGuidance = this.getAIGuidance(code);
			const aiPrompt = `Issue ID: ${issueId || 'N/A'}
File: ${file || 'Unknown file'}
Line: ${line || 'N/A'}
Code: ${code || 'N/A'}
Type: ${type || 'UNKNOWN'}
Message: ${message || 'No message'}

Instructions for AI:
Please review this WordPress coding standards issue and provide a fix. The issue ID is ${issueId || 'N/A'} for reference.${aiGuidance}`;
			$('#wpv-ai-prompt-text').val(aiPrompt);
			
			// Show the panels
			$('#pan01-content').show();
			$('#pan02-content').show();
			$('.wpv-accordion-header[data-target="pan01-content"]').addClass('active');
			$('.wpv-accordion-header[data-target="pan02-content"]').addClass('active');
		},
		
		getVSCodePath: function(file) {
			// Convert file path to VSCode-compatible path
			// This is a simplified version - you may need to adjust based on your setup
			return file.replace(/\\/g, '/');
		},
		
		getAIGuidance: function(code) {
			const guidance = this.aiGuidanceConfig[code];
			if (guidance && guidance.ai_guidance) {
				return '\n\nAI Guidance for ' + code + ':\n' + guidance.ai_guidance;
			}
			return '';
		},

		showDetails: function(file, issue) {
			this.log('Issue data:', issue);
			const details = $('#wpv-ast-details');
			const aiGuidance = $('#wpv-ast-ai-guidance');
			const aiPrompt = `I have a WordPress plugin verification error:\n\nFile: ${file}\nLine: ${issue.line}, Column: ${issue.column}\nType: ${issue.type}\nCode: ${issue.code}\nMessage: ${$('<div>').html(issue.message).text()}\n\nFix this now, please.`;
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
					<button type="button" class="button button-primary wpv-mark-complete" data-issue-id="${this.escapeHtml(issue.issue_id || '')}" data-file="${this.escapeHtml(file)}" data-line="${issue.line}" data-code="${this.escapeHtml(issue.code)}" title="Permanently removes this issue from results (use when code is actually fixed)">
						<span class="dashicons dashicons-yes"></span> Fixed
					</button>
					${!isIgnored ? `<a href="${ignoreUrl}" class="button" title="Marks issue as ignored but keeps it in results (use for false positives)">
						<span class="dashicons dashicons-hidden"></span> Ignore
					</a>` : '<span style="color: #999;">✓ Ignored</span>'}
					${issue.docs ? `<a href="${issue.docs}" target="_blank" class="button">Learn More</a>` : ''}
					${issue.link ? `<a href="${issue.link}" target="_blank" class="button">View in Editor</a>` : ''}
				</div>
			`);
			
			// Show AI Guidance
			this.showAIGuidance(issue, aiPrompt);
			
			details.show();
			
			$('.wpv-mark-complete').off('click').on('click', function() {
				const issueId = $(this).data('issue-id');
				const file = $(this).data('file');
				const line = $(this).data('line');
				const code = $(this).data('code');
				WPVerifierAST.markComplete(issueId, file, line, code);
			});
		},

		markComplete: function(issueId, file, line, code) {
			if (!window.PLUGIN_CHECK || !window.PLUGIN_CHECK.nonce) {
				alert('Configuration error.');
				return;
			}
			
			// Use the correct AJAX action and send issue_id
			const payload = new FormData();
			payload.append('nonce', window.PLUGIN_CHECK.nonce);
			payload.append('action', 'wpv_mark_resolved');
			payload.append('plugin', this.currentPlugin);
			payload.append('issue_id', issueId);
			payload.append('file', file);
			payload.append('line', line);
			payload.append('code', code);
			
			console.log('Sending Fixed request:', {
				action: 'wpv_mark_resolved',
				plugin: this.currentPlugin,
				issue_id: issueId,
				file: file,
				line: line,
				code: code
			});
			
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(response => response.json())
			.then(data => {
				console.log('Fixed response:', data);
				if (data.success) {
					alert('Issue marked as fixed and removed from results.');
					location.reload();
				} else {
					alert('Failed to mark as fixed: ' + (data.data?.message || 'Unknown error'));
				}
			})
			.catch(error => {
				console.error('Fixed request error:', error);
				alert('Failed to mark as fixed.');
			});
		},

		showAIGuidance: function(issue, aiPrompt) {
			const container = $('#wpv-ast-ai-guidance');
			const content = $('#wpv-ai-guidance-content');
			
			const guidance = this.aiGuidanceConfig[issue.code];
			
			if (guidance && guidance.ai_guidance) {
				content.html(`
					<div style="margin-bottom: 15px; padding: 12px; background: white; border-radius: 4px; border-left: 3px solid #0073aa;">
						<strong style="color: #0073aa;">Guidance for ${this.escapeHtml(issue.code)}:</strong>
						<p style="margin: 8px 0 0 0; color: #333;">${this.escapeHtml(guidance.ai_guidance)}</p>
					</div>
					<div class="wpv-ast-detail-actions">
						<button type="button" class="button wpv-copy-ai" data-prompt="${this.escapeHtml(aiPrompt)}">
							<span class="dashicons dashicons-clipboard"></span> Copy for AI
						</button>
					</div>
				`);
			} else {
				content.html(`
					<div style="margin-bottom: 15px; padding: 12px; background: white; border-radius: 4px; color: #666;">
						<em>No AI guidance available for this issue type.</em>
					</div>
					<div class="wpv-ast-detail-actions">
						<button type="button" class="button wpv-copy-ai" data-prompt="${this.escapeHtml(aiPrompt)}">
							<span class="dashicons dashicons-clipboard"></span> Copy for AI
						</button>
					</div>
				`);
			}
			
			container.show();
			
			$('.wpv-copy-ai').off('click').on('click', function() {
				const prompt = $(this).data('prompt');
				navigator.clipboard.writeText(prompt).then(() => {
					const $btn = $(this);
					const originalText = $btn.html();
					$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
					setTimeout(() => $btn.html(originalText), 2000);
				});
			});
		},

		isRediscovered: function(file, line, code) {
			return this.rediscovered.some(r => r.file === file && r.line == line && r.code === code);
		},

		groupByFile: function(results) {
			const files = {};
			
			// Check which types are selected
			const includeErrors = document.querySelector('input[name="types"][value="error"]')?.checked !== false;
			const includeWarnings = document.querySelector('input[name="types"][value="warning"]')?.checked !== false;
			
			// Try to load actual issue IDs from JSON file
			this.loadActualIssueIds().then(jsonResults => {
				if (jsonResults && jsonResults.results) {
					// Use actual issue IDs from JSON file
					Object.entries(jsonResults.results).forEach(([file, issues]) => {
						if (!files[file]) files[file] = [];
						issues.forEach(issue => {
							if ((includeErrors && issue.type === 'ERROR') || (includeWarnings && issue.type === 'WARNING')) {
								const metadata = window.wpvErrorMetadata && window.wpvErrorMetadata[issue.code];
								const icon = metadata ? 
									`<span class="dashicons dashicons-${metadata.icon}" style="color: ${metadata.color}; margin-right: 5px;" title="${metadata.description || ''}"></span>` :
									`<span class="dashicons dashicons-warning" style="color: #666; margin-right: 5px;"></span>`;
								files[file].push({...issue, icon: icon});
							}
						});
					});
					// Re-render with correct issue IDs
					this.renderWithActualData(files);
					return;
				}
				
				// Fallback to old method if JSON loading fails
				this.groupByFileOldMethod(results, files, includeErrors, includeWarnings);
			});
			
			return files;
		},
		
		loadActualIssueIds: function() {
			const plugin = this.currentPlugin;
			if (!plugin) return Promise.resolve(null);
			
			const pluginFolder = plugin.indexOf('/') !== -1 ? plugin.split('/')[0] : plugin;
			const currentUrl = window.location.href;
			const wpContentBase = currentUrl.substring(0, currentUrl.indexOf('/wp-admin/')) + '/wp-content/';
			const jsonUrl = wpContentBase + 'plugins/' + pluginFolder + '/.wpv-results.json';
			
			return fetch(jsonUrl)
				.then(response => response.json())
				.catch(error => {
					console.log('Could not load JSON file:', error);
					return null;
				});
		},
		
		renderWithActualData: function(files) {
			const container = $('#wpv-ast-results');
			container.empty();
			
			if (Object.keys(files).length === 0) {
				container.html('<p style="padding: 20px; color: #666;">No issues found in verification results.</p>');
				return;
			}
			
			Object.keys(files).forEach(file => {
				const issues = files[file];
				const errorCount = issues.filter(i => i.type === 'ERROR').length;
				const warningCount = issues.filter(i => i.type === 'WARNING').length;
				const fixedCount = issues.filter(i => i.resolved === true).length;
				const ignoredCount = issues.filter(i => i.ignored === true).length;
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
								${fixedCount > 0 ? `<span class="wpv-ast-badge fixed">${fixedCount} fixed</span>` : ''}
								${ignoredCount > 0 ? `<span class="wpv-ast-badge ignored">${ignoredCount} ignored</span>` : ''}
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
					const statusBadge = issue.resolved ? '<span class="wpv-ast-badge fixed">Fixed</span>' : 
										   issue.ignored ? '<span class="wpv-ast-badge ignored">Ignored</span>' : 
										   '<span class="wpv-ast-badge pending">Pending</span>';
					issueList.append(`
						<li class="wpv-ast-issue-item" 
							data-issue-id="${issue.issue_id || idx}" 
							data-file="${this.escapeHtml(file)}"
							data-issue-code="${this.escapeHtml(issue.code || '')}"
							data-issue-line="${issue.line || ''}"
							data-issue-message="${this.escapeHtml(messageText)}"
							data-issue-type="${issue.type || ''}">
							${issue.icon}
							<span class="wpv-ast-badge ${issue.type.toLowerCase()}">${issue.type}</span>
							${statusBadge}
							<span class="wpv-issue-id">[${issue.issue_id || 'NO-ID'}]</span>
							Line ${issue.line}: ${this.escapeHtml(messageText)}
							${issue.docs ? `<a href="${issue.docs}" target="_blank" class="wpv-issue-docs">↗</a>` : ''}
						</li>
					`);
				});

				container.append(row);
			});
		},
		
		groupByFileOldMethod: function(results, files, includeErrors, includeWarnings) {
			// Fallback to old method
			if (includeErrors && results.errors) {
				Object.entries(results.errors).forEach(([file, lines]) => {
					if (!files[file]) files[file] = [];
					Object.entries(lines).forEach(([lineNum, columns]) => {
						Object.entries(columns).forEach(([colNum, issues]) => {
							issues.forEach(issue => {
								// Generate issue_id to match server-side logic: md5(file + line + code)
								const issueId = 'E-' + this.generateServerIssueHash(file, lineNum, issue.code);
								const metadata = window.wpvErrorMetadata && window.wpvErrorMetadata[issue.code];
								const icon = metadata ? 
									`<span class="dashicons dashicons-${metadata.icon}" style="color: ${metadata.color}; margin-right: 5px;" title="${metadata.description || ''}"></span>` :
									`<span class="dashicons dashicons-warning" style="color: #666; margin-right: 5px;"></span>`;
								files[file].push({type: 'ERROR', line: parseInt(lineNum), column: parseInt(colNum), issue_id: issueId, icon: icon, ...issue});
							});
						});
					});
				});
			}
			
			if (includeWarnings && results.warnings) {
				Object.entries(results.warnings).forEach(([file, lines]) => {
					if (!files[file]) files[file] = [];
					Object.entries(lines).forEach(([lineNum, columns]) => {
						Object.entries(columns).forEach(([colNum, issues]) => {
							issues.forEach(issue => {
								// Generate issue_id to match server-side logic: md5(file + line + code)
								const issueId = 'W-' + this.generateServerIssueHash(file, lineNum, issue.code);
								const metadata = window.wpvErrorMetadata && window.wpvErrorMetadata[issue.code];
								const icon = metadata ? 
									`<span class="dashicons dashicons-${metadata.icon}" style="color: ${metadata.color}; margin-right: 5px;" title="${metadata.description || ''}"></span>` :
									`<span class="dashicons dashicons-warning" style="color: #666; margin-right: 5px;"></span>`;
								files[file].push({type: 'WARNING', line: parseInt(lineNum), column: parseInt(colNum), issue_id: issueId, icon: icon, ...issue});
							});
						});
					});
				});
			}
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

		// Generate issue hash to match server-side logic: md5(file + line + code)
		generateServerIssueHash: function(file, line, code) {
			// Simple hash function to approximate MD5 behavior
			// Server uses: md5($relative_file . $line . $issue['code'])
			const str = file + line + code;
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

		getErrorIcon: function(code) {
			const metadata = window.wpvErrorMetadata && window.wpvErrorMetadata[code];
			if (metadata) {
				return `<span class="dashicons dashicons-${metadata.icon}" style="color: ${metadata.color}; margin-right: 5px;" title="${metadata.description || ''}"></span>`;
			}
			return `<span class="dashicons dashicons-warning" style="color: #666; margin-right: 5px;"></span>`;
		},

		addIcons: function() {
			if (!window.wpvErrorMetadata) {
				this.log('No error metadata available');
				return;
			}
			
			$('.wpv-ast-issue-item').each(function() {
				const $item = $(this);
				if ($item.find('.dashicons').length > 0) return; // Already has icon
				
				const text = $item.text();
				const codeMatch = text.match(/WordPress\.[\w\.]+/);
				if (codeMatch) {
					const code = codeMatch[0];
					const metadata = window.wpvErrorMetadata[code];
					if (metadata) {
						const icon = `<span class="dashicons dashicons-${metadata.icon}" style="color: ${metadata.color}; margin-right: 5px;" title="${metadata.description || ''}"></span>`;
						$item.prepend(icon);
					}
				}
			});
		},

		validateStructure: function() {
			this.log('=== validateStructure called ===');
			this.log('this.currentPlugin:', this.currentPlugin);
			this.log('window.PLUGIN_CHECK:', window.PLUGIN_CHECK);
			if (!this.currentPlugin) {
				this.log('ABORT: Missing plugin');
				return;
			}
			if (!window.PLUGIN_CHECK || !window.PLUGIN_CHECK.nonce) {
				this.log('ABORT: Missing PLUGIN_CHECK or nonce');
				return;
			}
			
			const payload = new FormData();
			payload.append('nonce', window.PLUGIN_CHECK.nonce);
			payload.append('action', 'plugin_check_validate_structure');
			payload.append('plugin', this.currentPlugin);
			this.log('Sending structure validation for:', this.currentPlugin);
			
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(response => response.json())
			.then(data => {
				this.log('Structure validation response:', data);
				if (data.success && data.data.validation) {
					this.renderStructureValidation(data.data.validation);
				}
			})
			.catch(error => this.error('Structure validation error:', error));
		},

		renderStructureValidation: function(validation) {
			this.log('renderStructureValidation called with:', validation);
			const container = $('#plugin-check__results');
			this.log('Container found:', container.length);
			const readinessDiv = container.find('div[style*="margin: 20px 0"]').first();
			this.log('Readiness div found:', readinessDiv.length);
			
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
