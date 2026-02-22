<?php
/**
 * AST Demo Page
 */

// Get saved results
$results_dir = WP_CONTENT_DIR . '/verifier-results';
$saved_results = array();

if (is_dir($results_dir)) {
	$plugins = glob($results_dir . '/*', GLOB_ONLYDIR);
	foreach ($plugins as $plugin_dir) {
		$json_file = $plugin_dir . '/results.json';
		if (file_exists($json_file)) {
			$data = json_decode(file_get_contents($json_file), true);
			if ($data && isset($data['results'])) {
				$saved_results[] = array(
					'plugin' => $data['plugin'],
					'path' => $json_file,
					'files' => array_keys($data['results'])
				);
			}
		}
	}
}
?>

<div style="margin-bottom: 20px;">
	<h3>Saved Results</h3>
	<div style="display: flex; gap: 10px; overflow-x: auto; padding: 10px 0;">
		<?php foreach ($saved_results as $result): ?>
			<div class="result-box" data-path="<?php echo esc_attr($result['path']); ?>" style="min-width: 200px; padding: 15px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer; background: #fff;">
				<strong><?php echo esc_html($result['plugin']); ?></strong><br>
				<small><?php echo count($result['files']); ?> files</small>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<div class="wpv-ast-container">
	<div class="wpv-ast-layout">
		<div class="wpv-ast-table-container">
			<div class="wpv-ast-table" id="ast-demo-table"></div>
		</div>
		
		<div class="wpv-ast-sidebar">
			<div class="wpv-ast-details" id="ast-demo-details">
				<div class="wpv-ast-placeholder">
					<p><?php esc_html_e('Select a result above', 'wp-verifier'); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	let currentData = null;
	
	$('.result-box').on('click', function() {
		const path = $(this).data('path');
		const normalizedPath = path.replace(/\\/g, '/');
		const contentPath = normalizedPath.split('/wp-content/')[1];
		const currentUrl = window.location.href;
		const wpContentBase = currentUrl.substring(0, currentUrl.indexOf('/wp-admin/')) + '/wp-content/';
		const contentUrl = wpContentBase + contentPath;
		
		$('.result-box').css('border-color', '#ddd');
		$(this).css('border-color', '#2271b1');
		
		fetch(contentUrl)
			.then(response => response.json())
			.then(data => {
				currentData = data.results;
				renderTable(data.results);
			});
	});
	
	function renderTable(results) {
		const $table = $('#ast-demo-table');
		$table.empty();
		
		Object.keys(results).forEach(file => {
			const items = results[file];
			const fileName = file.split(/[\\\/]/).pop();
			const errorCount = items.filter(i => i.type === 'ERROR').length;
			const warningCount = items.filter(i => i.type === 'WARNING').length;
			
			const $row = $(`
				<div class="accordion-row">
					<div class="accordion-header">
						<div class="wpv-ast-file-name">${fileName}</div>
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
			
			const $list = $row.find('.wpv-ast-issue-list');
			items.forEach((item, idx) => {
				$list.append(`
					<li class="wpv-ast-issue-item" data-file="${file}" data-idx="${idx}">
						<span class="wpv-ast-badge ${item.type.toLowerCase()}">${item.type}</span>
						Line ${item.line}: ${$('<div>').text(item.message).html()}
					</li>
				`);
			});
			
			$table.append($row);
		});
		
		bindEvents();
	}
	
	function bindEvents() {
		$('.accordion-header').off('click').on('click', function() {
			const $header = $(this);
			const $content = $header.next('.accordion-content');
			
			$('.accordion-header').not($header).removeClass('active');
			$('.accordion-content').not($content).removeClass('active').slideUp(200);
			
			$header.toggleClass('active');
			$content.toggleClass('active').slideToggle(200);
		});
		
		$('.wpv-ast-issue-item').off('click').on('click', function(e) {
			e.stopPropagation();
			const file = $(this).data('file');
			const idx = $(this).data('idx');
			const item = currentData[file][idx];
			
			$('#ast-demo-details').html(`
				<h3>${file.split(/[\\\\/]/).pop()}</h3>
				<div class="wpv-ast-detail-group">
					<label>Type:</label>
					<span class="wpv-ast-badge ${item.type.toLowerCase()}">${item.type}</span>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Line:</label>
					<p>${item.line}</p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Code:</label>
					<p><code>${item.code}</code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label>Message:</label>
					<p>${$('<div>').text(item.message).html()}</p>
				</div>
			`);
		});
	}
});
</script>
