<h4><?php wpverifier_header( __( 'FILE:', 'wpverifier' ), 'FT01' ); ?> {{ data.file }}</h4>
<table id="plugin-check__results-table-{{data.index}}" class="widefat striped plugin-check__results-table">
	<thead>
		<tr>
			<td>
				<?php esc_html_e( 'Line', 'wpverifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Column', 'wpverifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Type', 'wpverifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Code', 'wpverifier' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Message', 'wpverifier' ); ?>
			</td>
			<# if ( data.hasLinks ) { #>
				<td>
					<?php esc_html_e( 'Edit Link', 'wpverifier' ); ?>
				</td>
			<# } #>
		</tr>
	</thead>
	<tbody id="plugin-check__results-body-{{data.index}}"></tbody>
</table>
<br>
