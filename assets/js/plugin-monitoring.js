/**
 * Plugin Monitoring functionality
 */

(function($) {
	'use strict';

	const PluginMonitoring = {
		selectedPlugin: null,

		init: function() {
			this.bindEvents();
			this.loadMonitoringStatus();
		},

		bindEvents: function() {
			$('.plugin-monitor-row').on('click', this.handleRowClick.bind(this));
			$('#plugin-check__run-check').on('click', this.handleRunCheck.bind(this));
			$('#plugin-check__start-monitor').on('click', this.handleStartMonitor.bind(this));
			$('#plugin-check__stop-monitor').on('click', this.handleStopMonitor.bind(this));
			$('#plugin-check__view-log').on('click', this.handleViewLog.bind(this));
		},

		loadMonitoringStatus: function() {
			$('.plugin-monitor-row').each(function() {
				const plugin = $(this).data('plugin');
				PluginMonitoring.loadPluginStatus(plugin);
			});
		},

		loadPluginStatus: function(plugin) {
			$.ajax({
				url: PluginMonitorConfig.ajaxUrl,
				method: 'POST',
				data: {
					action: PluginMonitorConfig.actionLoadResults,
					nonce: PluginMonitorConfig.nonce,
					plugin: plugin
				},
				success: function(response) {
					if (response.success && response.data.path) {
						const url = response.data.path.replace('C:/wamp64/www/Ecosystem/wp-content/', '/wp-content/');
						$.getJSON(url, function(data) {
							PluginMonitoring.updatePluginRow(plugin, data);
						}).fail(function() {
							console.log('No saved results for ' + plugin);
						});
					}
				}
			});
		},

		updatePluginRow: function(plugin, data) {
			const $row = $('.plugin-monitor-row[data-plugin="' + plugin + '"]');
			const errorCount = this.countIssues(data.errors);
			const warningCount = this.countIssues(data.warnings);
			
			$row.find('.error-count').text(errorCount || '0');
			$row.find('.warning-count').text(warningCount || '0');
			
			if (data.timestamp_human) {
				$row.find('.last-check').text(data.timestamp_human);
			}
		},

		countIssues: function(issues) {
			if (!issues) return 0;
			let count = 0;
			for (const file in issues) {
				for (const line in issues[file]) {
					for (const col in issues[file][line]) {
						count += issues[file][line][col].length;
					}
				}
			}
			return count;
		},

		getTopIssues: function(data) {
			const issues = [];
			['errors', 'warnings'].forEach(type => {
				if (!data[type]) return;
				for (const file in data[type]) {
					for (const line in data[type][file]) {
						for (const col in data[type][file][line]) {
							data[type][file][line][col].forEach(issue => {
								issues.push({
									type: type === 'errors' ? 'ERROR' : 'WARNING',
									file: file,
									line: line,
									...issue
								});
							});
						}
					}
				}
			});
			return issues.slice(0, 3);
		},

		handleRowClick: function(e) {
			const $row = $(e.currentTarget);
			this.selectedPlugin = $row.data('plugin');
			const pluginName = $row.find('strong').text();
			
			$('.plugin-monitor-row').removeClass('selected');
			$row.addClass('selected');
			
			$('#plugin-monitor-placeholder').hide();
			$('#plugin-monitor-details').show();
			$('#monitor-plugin-name').text(pluginName);
			
			this.loadPluginDetails();
		},

		loadPluginDetails: function() {
			$.ajax({
				url: PluginMonitorConfig.ajaxUrl,
				method: 'POST',
				data: {
					action: PluginMonitorConfig.actionLoadResults,
					nonce: PluginMonitorConfig.nonce,
					plugin: this.selectedPlugin
				},
				success: function(response) {
					if (response.success && response.data.path) {
						const url = response.data.path.replace('C:/wamp64/www/Ecosystem/wp-content/', '/wp-content/');
						$.getJSON(url, function(data) {
							PluginMonitoring.renderTopIssues(data);
						}).fail(function() {
							$('#monitor-issues-list').html('<p>No saved results</p>');
						});
					} else {
						$('#monitor-issues-list').html('<p>No saved results</p>');
					}
				}
			});
		},

		renderTopIssues: function(data) {
			const topIssues = this.getTopIssues(data);
			if (topIssues.length > 0) {
				let html = '<ul>';
				topIssues.forEach(issue => {
					const typeClass = issue.type === 'ERROR' ? 'issue-type-error' : 'issue-type-warning';
					html += '<li><span class="' + typeClass + '">' + issue.type + '</span><br>';
					html += '<small>' + issue.file + ':' + issue.line + '</small><br>';
					html += '<small>' + $('<div>').text(issue.message).html() + '</small></li>';
				});
				html += '</ul>';
				$('#monitor-issues-list').html(html);
			} else {
				$('#monitor-issues-list').html('<p>No issues found</p>');
			}
		},

		handleRunCheck: function() {
			if (!this.selectedPlugin) return;
			const url = PluginMonitorConfig.verifyTabUrl + '&plugin=' + encodeURIComponent(this.selectedPlugin);
			window.location.href = url;
		},

		handleStartMonitor: function() {
			if (!this.selectedPlugin) return;
			
			$.ajax({
				url: PluginMonitorConfig.ajaxUrl,
				method: 'POST',
				data: {
					action: PluginMonitorConfig.actionStartMonitor,
					nonce: PluginMonitorConfig.nonce,
					plugin: this.selectedPlugin
				},
				success: function(response) {
					if (response.success) {
						$('#plugin-check__monitor-status').html('<p style="color: #00a32a;">Monitoring started</p>');
						$('#plugin-check__start-monitor').hide();
						$('#plugin-check__stop-monitor').show();
					} else {
						alert('Failed to start monitoring: ' + (response.data?.message || 'Unknown error'));
					}
				},
				error: function() {
					alert('Failed to start monitoring');
				}
			});
		},

		handleStopMonitor: function() {
			$.ajax({
				url: PluginMonitorConfig.ajaxUrl,
				method: 'POST',
				data: {
					action: PluginMonitorConfig.actionStopMonitor,
					nonce: PluginMonitorConfig.nonce
				},
				success: function(response) {
					if (response.success) {
						$('#plugin-check__monitor-status').html('<p>Monitoring stopped</p>');
						$('#plugin-check__start-monitor').show();
						$('#plugin-check__stop-monitor').hide();
					}
				}
			});
		},

		handleViewLog: function() {
			$.ajax({
				url: PluginMonitorConfig.ajaxUrl,
				method: 'POST',
				data: {
					action: PluginMonitorConfig.actionViewLog,
					nonce: PluginMonitorConfig.nonce
				},
				success: function(response) {
					if (response.success && response.data.log) {
						alert('Monitor Log:\n\n' + JSON.stringify(response.data.log, null, 2));
					} else {
						alert('No log entries found');
					}
				}
			});
		}
	};

	$(document).ready(function() {
		if ($('.plugin-monitor-row').length) {
			PluginMonitoring.init();
		}
	});

})(jQuery);
