<h4><?php esc_html_e( 'FILE:', 'wp-verifier' ); ?> {{ data.file }}</h4>
<table id="plugin-check__results-table-{{data.index}}" class="widefat striped plugin-check__results-table">
	<thead>
		<tr>
			<td>
				<?php esc_html_e( 'Line', 'wp-verifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Column', 'wp-verifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Type', 'wp-verifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Code', 'wp-verifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Message', 'wp-verifier' ); ?>
			</td>
			<# if ( data.hasLinks ) { #>
				<td>
					<?php esc_html_e( 'Edit Link', 'wp-verifier' ); ?>
				</td>
			<# } #>
		</tr>
	</thead>
	<tbody id="plugin-check__results-body-{{data.index}}"></tbody>
</table>
<br>
