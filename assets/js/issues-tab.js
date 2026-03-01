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
});
