/**
 * Results Tab - Minimal JavaScript
 * PHASE 5: Only handles accordion toggle, copy-to-clipboard, and Fixed button AJAX.
 * All HTML is rendered server-side by admin-page-results.php.
 * No DOM innerHTML replacement. No template rendering.
 *
 * @package wp-verifier
 */
jQuery(function($) {
	'use strict';

	// Accordion toggle
	$(document).on('click', '.accordion-header', function() {
		var $content = $(this).next('.accordion-content');
		$(this).toggleClass('active');
		$content.slideToggle(200);
	});

	// Sidebar accordion toggle
	$(document).on('click', '.wpv-accordion-header', function() {
		var target = $(this).data('target');
		if (target) {
			$(this).toggleClass('active');
			$('#' + target).slideToggle(200);
		}
	});

	// Copy AI Prompt
	$(document).on('click', '.wpv-copy-prompt', function(e) {
		e.preventDefault();
		var targetId = $(this).data('target');
		var textarea = document.getElementById(targetId);
		if (!textarea) return;
		var $btn = $(this);
		navigator.clipboard.writeText(textarea.value).then(function() {
			var original = $btn.html();
			$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
			setTimeout(function() { $btn.html(original); }, 2000);
		});
	});

	// Fixed button - AJAX call then reload
	$(document).on('click', '.wpv-fixed-btn', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var issueId = $btn.data('issue-id');
		var plugin = wpv_ajax_object.current_plugin || '';

		if (!issueId || !plugin) {
			alert('Missing issue ID or plugin.');
			return;
		}

		$btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Removing...');

		$.ajax({
			url: wpv_ajax_object.ajax_url,
			type: 'POST',
			data: {
				action: 'wpv_mark_resolved',
				issue_id: issueId,
				plugin: plugin,
				nonce: wpv_ajax_object.nonce
			},
			success: function(response) {
				if (response.success) {
					var url = new URL(window.location.href);
					url.searchParams.delete('issue_id');
					window.location.href = url.toString();
				} else {
					alert('Failed: ' + (response.data && response.data.message || 'Unknown error'));
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Fixed');
				}
			},
			error: function() {
				alert('Request failed.');
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Fixed');
			}
		});
	});

	// Ignore button - AJAX call then reload
	$(document).on('click', '.wpv-ignore-btn', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var issueId = $btn.data('issue-id');
		var plugin = wpv_ajax_object.current_plugin || '';

		if (!issueId || !plugin) {
			alert('Missing issue ID or plugin.');
			return;
		}

		$btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Ignoring...');

		$.ajax({
			url: wpv_ajax_object.ajax_url,
			type: 'POST',
			data: {
				action: 'wpv_mark_ignored',
				issue_id: issueId,
				plugin: plugin,
				nonce: wpv_ajax_object.nonce
			},
			success: function(response) {
				if (response.success) {
					var url = new URL(window.location.href);
					url.searchParams.delete('issue_id');
					window.location.href = url.toString();
				} else {
					alert('Failed: ' + (response.data && response.data.message || 'Unknown error'));
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-hidden"></span> Ignore');
				}
			},
			error: function() {
				alert('Request failed.');
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-hidden"></span> Ignore');
			}
		});
	});
});
