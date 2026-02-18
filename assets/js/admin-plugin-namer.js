(function($) {
	'use strict';

	const PluginNamer = {
		currentName: '',
		currentEvaluation: {},
		enabledChecks: wpvPluginNamer.enabledChecks || {
			domains: true,
			conflicts: true,
			seo: true,
			trademarks: true
		},

		init: function() {
			this.bindEvents();
			this.loadSavedNames();
			this.toggleSections();
		},

		bindEvents: function() {
			$('#wpv-analyze-name-btn').on('click', () => this.analyzeName());
			$('#wpv-plugin-name-input').on('keypress', (e) => {
				if (e.which === 13) this.analyzeName();
			});
			$('#wpv-save-evaluation-btn').on('click', () => this.saveEvaluation());
		},

			analyzeName: function() {
			const name = $('#wpv-plugin-name-input').val().trim();
			
			if (!name) {
				alert(wpvPluginNamer.i18n.error);
				return;
			}

			this.currentName = name;
			this.showLoading();
			this.currentEvaluation = {};

			const checks = [];
			if (this.enabledChecks.domains) checks.push(this.checkDomains(name));
			if (this.enabledChecks.conflicts) checks.push(this.checkConflicts(name));
			if (this.enabledChecks.seo) checks.push(this.analyzeSeo(name));
			if (this.enabledChecks.trademarks) checks.push(this.checkTrademarks(name));

			Promise.all(checks).then(() => {
				this.hideLoading();
				$('#wpv-analysis-results').slideDown();
			}).catch((error) => {
				this.hideLoading();
				alert(wpvPluginNamer.i18n.error + ': ' + error);
			});
		},

		checkDomains: function(name) {
			return $.ajax({
				url: wpvPluginNamer.ajaxUrl,
				type: 'POST',
				data: {
					action: wpvPluginNamer.actions.checkDomains,
					nonce: wpvPluginNamer.nonce,
					name: name
				}
			}).done((response) => {
				if (response.success) {
					this.currentEvaluation.domains = response.data.domains;
					this.renderDomains(response.data.domains, response.data.cached);
				}
			});
		},

		renderDomains: function(domains, cached) {
			const grid = $('#wpv-domains-grid');
			grid.empty();

			domains.forEach((domain) => {
				const statusClass = domain.available ? 'available' : 'taken';
				const statusText = domain.available ? 
					wpvPluginNamer.i18n.available : 
					wpvPluginNamer.i18n.taken;

				grid.append(`
					<div class="wpv-domain-item ${statusClass}">
						<div class="wpv-domain-name">${domain.domain}</div>
						<div class="wpv-domain-status">${statusText}</div>
					</div>
				`);
			});

			if (cached) {
				$('#wpv-domains-cached').show();
			} else {
				$('#wpv-domains-cached').hide();
			}
		},

		checkConflicts: function(name) {
			return $.ajax({
				url: wpvPluginNamer.ajaxUrl,
				type: 'POST',
				data: {
					action: wpvPluginNamer.actions.checkConflicts,
					nonce: wpvPluginNamer.nonce,
					name: name
				}
			}).done((response) => {
				if (response.success) {
					this.currentEvaluation.conflicts = response.data;
					this.renderConflicts(response.data);
				}
			});
		},

		renderConflicts: function(data) {
			const container = $('#wpv-conflicts-content');
			container.empty();

			if (!data.exists) {
				container.append(`
					<div class="wpv-conflict-alert success">
						<strong>${wpvPluginNamer.i18n.noConflicts}</strong>
					</div>
				`);
				return;
			}

			if (data.exact_match) {
				container.append(`
					<div class="wpv-conflict-alert">
						<strong>${wpvPluginNamer.i18n.exactMatch}</strong>
						<p>A plugin with this exact slug already exists on WordPress.org</p>
					</div>
				`);
			}

			if (data.similar_plugins && data.similar_plugins.length > 0) {
				let html = `<div class="wpv-conflict-alert warning">
					<strong>${wpvPluginNamer.i18n.similar}</strong>
					<ul class="wpv-similar-plugins">`;
				
				data.similar_plugins.forEach((plugin) => {
					html += `<li>
						<strong>${plugin.name}</strong> (${plugin.slug})
						${plugin.author ? `<br><small>by ${plugin.author}</small>` : ''}
					</li>`;
				});
				
				html += '</ul></div>';
				container.append(html);
			}
		},

		analyzeSeo: function(name) {
			return $.ajax({
				url: wpvPluginNamer.ajaxUrl,
				type: 'POST',
				data: {
					action: wpvPluginNamer.actions.analyzeSeo,
					nonce: wpvPluginNamer.nonce,
					name: name
				}
			}).done((response) => {
				if (response.success) {
					this.currentEvaluation.seo = response.data;
					this.renderSeo(response.data);
				}
			});
		},

		renderSeo: function(data) {
			const container = $('#wpv-seo-content');
			container.empty();

			const score = data.score;
			let scoreClass = 'poor';
			if (score >= 70) scoreClass = 'good';
			else if (score >= 40) scoreClass = 'medium';

			let html = `
				<div class="wpv-seo-score">
					<div class="wpv-score-gauge ${scoreClass}">${score}</div>
					<div class="wpv-seo-details">
						<div class="wpv-seo-metric">
							<strong>Length:</strong> ${data.length.char_count} characters, ${data.length.word_count} words
						</div>
						<div class="wpv-seo-metric">
							<strong>Keywords:</strong> ${data.keywords.descriptive_count} descriptive keywords
						</div>
						<div class="wpv-seo-metric">
							<strong>Readability:</strong> ${data.readability.avg_word_length} avg word length
						</div>
					</div>
				</div>
			`;

			const allRecommendations = [
				...data.length.recommendations,
				...data.keywords.recommendations,
				...data.readability.recommendations
			];

			if (allRecommendations.length > 0) {
				html += '<h4>Recommendations:</h4><ul class="wpv-recommendations">';
				allRecommendations.forEach((rec) => {
					html += `<li>${rec}</li>`;
				});
				html += '</ul>';
			}

			container.html(html);
		},

		checkTrademarks: function(name) {
			return $.ajax({
				url: wpvPluginNamer.ajaxUrl,
				type: 'POST',
				data: {
					action: wpvPluginNamer.actions.checkTrademarks,
					nonce: wpvPluginNamer.nonce,
					name: name
				}
			}).done((response) => {
				if (response.success) {
					this.currentEvaluation.trademarks = response.data;
					this.renderTrademarks(response.data);
				}
			});
		},

		renderTrademarks: function(data) {
			const container = $('#wpv-trademark-content');
			container.empty();

			let html = `<div class="wpv-trademark-risk ${data.risk_level}">
				Risk Level: ${data.risk_level.toUpperCase()}
			</div>`;

			if (data.conflicts && data.conflicts.length > 0) {
				html += '<h4>Conflicts Found:</h4><ul class="wpv-trademark-conflicts">';
				data.conflicts.forEach((conflict) => {
					html += `<li><strong>${conflict.term}</strong>: ${conflict.message}</li>`;
				});
				html += '</ul>';
			}

			if (data.warnings && data.warnings.length > 0) {
				html += '<h4>Warnings:</h4><ul class="wpv-trademark-conflicts">';
				data.warnings.forEach((warning) => {
					html += `<li><strong>${warning.term}</strong>: ${warning.message}</li>`;
				});
				html += '</ul>';
			}

			if (data.guidelines && data.guidelines.length > 0) {
				html += '<h4>Guidelines:</h4><ul class="wpv-recommendations">';
				data.guidelines.forEach((guideline) => {
					html += `<li><strong>${guideline.title}:</strong> ${guideline.description}`;
					if (guideline.link) {
						html += ` <a href="${guideline.link}" target="_blank">Learn more</a>`;
					}
					html += '</li>';
				});
				html += '</ul>';
			}

			container.html(html);
		},

		saveEvaluation: function() {
			const note = $('#wpv-save-note').val();

			$.ajax({
				url: wpvPluginNamer.ajaxUrl,
				type: 'POST',
				data: {
					action: wpvPluginNamer.actions.saveName,
					nonce: wpvPluginNamer.nonce,
					name: this.currentName,
					evaluation: JSON.stringify(this.currentEvaluation),
					note: note
				}
			}).done((response) => {
				if (response.success) {
					alert(wpvPluginNamer.i18n.saved);
					$('#wpv-save-note').val('');
					this.loadSavedNames();
				} else {
					alert(wpvPluginNamer.i18n.saveFailed);
				}
			});
		},

		loadSavedNames: function() {
			$.ajax({
				url: wpvPluginNamer.ajaxUrl,
				type: 'POST',
				data: {
					action: wpvPluginNamer.actions.getSavedNames,
					nonce: wpvPluginNamer.nonce
				}
			}).done((response) => {
				if (response.success) {
					this.renderSavedNames(response.data.names);
				}
			});
		},

		renderSavedNames: function(names) {
			const container = $('#wpv-saved-names-list');
			container.empty();

			if (!names || names.length === 0) {
				container.html('<div class="wpv-no-saved-names">No saved names yet.</div>');
				return;
			}

			let html = '<table class="wpv-saved-names-table"><thead><tr>';
			html += '<th>Name</th><th>Date</th><th>Note</th><th>Actions</th>';
			html += '</tr></thead><tbody>';

			names.forEach((item) => {
				html += `<tr>
					<td><strong>${item.name}</strong></td>
					<td>${item.saved_at}</td>
					<td>${item.note || '-'}</td>
					<td><button class="button button-small" onclick="alert('View details')">View</button></td>
				</tr>`;
			});

			html += '</tbody></table>';
			container.html(html);
		},

		showLoading: function() {
			$('#wpv-analysis-loading').show();
			$('#wpv-analyze-name-btn').prop('disabled', true);
		},

		hideLoading: function() {
			$('#wpv-analysis-loading').hide();
			$('#wpv-analyze-name-btn').prop('disabled', false);
		},

		toggleSections: function() {
			if (!this.enabledChecks.domains) $('.wpv-domains-section').hide();
			if (!this.enabledChecks.conflicts) $('.wpv-conflicts-section').hide();
			if (!this.enabledChecks.seo) $('.wpv-seo-section').hide();
			if (!this.enabledChecks.trademarks) $('.wpv-trademark-section').hide();
		}
	};

	$(document).ready(() => PluginNamer.init());

})(jQuery);
