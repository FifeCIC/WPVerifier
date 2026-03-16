jQuery(document).ready(function($) {
	var sortOrder = 'desc';
	
	$('.wpv-sortable').on('click', function() {
		sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
		var $icon = $(this).find('.dashicons');
		$icon.removeClass('dashicons-arrow-down dashicons-arrow-up')
			.addClass(sortOrder === 'desc' ? 'dashicons-arrow-down' : 'dashicons-arrow-up');
		
		var $tbody = $('.wpv-issues-table tbody');
		var rows = $tbody.find('.wpv-issue-row').get();
		
		rows.sort(function(a, b) {
			var sevA = $(a).data('severity');
			var sevB = $(b).data('severity');
			if (sevA === sevB) return 0;
			var result = (sevA === 'ERROR') ? -1 : 1;
			return sortOrder === 'desc' ? result : -result;
		});
		
		$.each(rows, function(index, row) {
			var $row = $(row);
			var $details = $row.next('.wpv-issue-details');
			$tbody.append($row);
			$tbody.append($details);
		});
	});
	
	$('.wpv-issue-row').on('click', function() {
		var index = $(this).data('index');
		var detailsRow = $('#wpv-details-' + index);
		
		// Toggle this row
		if (detailsRow.is(':visible')) {
			detailsRow.hide();
			$(this).removeClass('active');
		} else {
			// Hide all other details
			$('.wpv-issue-details').hide();
			$('.wpv-issue-row').removeClass('active');
			// Show this one
			detailsRow.show();
			$(this).addClass('active');
		}
	});
	
	$('.wpv-copy-prompt').on('click', function(e) {
		e.stopPropagation();
		var prompt = $(this).data('prompt');
		var $button = $(this);
		
		navigator.clipboard.writeText(prompt).then(function() {
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-yes"></span> Copied!');
			setTimeout(function() {
				$button.html(originalText);
			}, 2000);
		});
	});
	
	// Handle "Mark As Fixed" button clicks
	$(document).on('click', '.wpv-fixed-link', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var $button = $(this);
		var issue_id = $button.data('issue-id');
		
		// Get current plugin
		var plugin = wpv_ajax_object?.current_plugin || '';
		if (!plugin) {
			alert('No plugin selected');
			return;
		}
		
		if (!issue_id) {
			alert('Issue ID not found');
			return;
		}
		
		// Disable button and show loading
		$button.prop('disabled', true);
		var originalHtml = $button.html();
		$button.html('<span class="dashicons dashicons-update-alt"></span> Marking...');
		
		// Make AJAX request
		$.ajax({
			url: wpv_ajax_object.ajax_url,
			type: 'POST',
			data: {
				action: 'wpv_mark_resolved',
				issue_id: issue_id,
				plugin: plugin,
				nonce: wpv_ajax_object.nonce
			},
			success: function(response) {
				if (response.success) {
					$button.html('<span class="dashicons dashicons-yes"></span> Fixed!');
					$button.removeClass('button-primary').addClass('button-secondary');
					setTimeout(function() {
						// Hide the entire issue row
						var $row = $button.closest('.wpv-issue-details').prev('.wpv-issue-row');
						var $details = $button.closest('.wpv-issue-details');
						$row.fadeOut();
						$details.fadeOut();
					}, 1500);
				} else {
					alert('Failed to mark issue as resolved: ' + (response.data?.message || 'Unknown error'));
					$button.html(originalHtml).prop('disabled', false);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', xhr.responseText);
				alert('Failed to mark issue as resolved');
				$button.html(originalHtml).prop('disabled', false);
			}
		});
	});
});
