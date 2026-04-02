<tr class="plugin-check__results-row">
	<td data-label="<?php esc_attr_e( 'Line', 'wpverifier' ); ?>">
		{{data.line}}
	</td>
	<td data-label="<?php esc_attr_e( 'Column', 'wpverifier' ); ?>">
		{{data.column}}
	</td>
	<td data-label="<?php esc_attr_e( 'Type', 'wpverifier' ); ?>">
		{{data.type}}
	</td>
	<td data-label="<?php esc_attr_e( 'Code', 'wpverifier' ); ?>">
		<span class="error-icon" data-code="{{data.code}}">{{{data.icon}}}</span>
		{{data.code}}
	</td>
	<td data-label="<?php esc_attr_e( 'Message', 'wpverifier' ); ?>">
		{{{data.message}}}
		<# if ( data.docs ) { #>
			<br>
			<a href="{{data.docs}}" target="_blank">
				<?php esc_html_e( 'Learn more', 'wpverifier' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'wpverifier' ); ?></span>
				<span aria-hidden="true" class="dashicons dashicons-external"></span>
			</a>
		<# } #>
		<br>
		<button type="button" class="button button-small copy-for-ai" data-code="{{data.code}}" data-message="{{{data.message}}}">
			<?php esc_html_e( 'Copy for AI', 'wpverifier' ); ?>
		</button>
	</td>
	<# if ( data.hasLinks ) { #>
		<td>
			<# if ( data.link ) { #>
				<a href="{{data.link}}" target="_blank">
					<?php esc_html_e( 'View in code editor', 'wpverifier' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'wpverifier' ); ?></span>
					<span aria-hidden="true" class="dashicons dashicons-external"></span>
				</a>
			<# } #>
		</td>
	<# } #>
</tr>

