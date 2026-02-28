<tr class="plugin-check__results-row">
	<td data-label="<?php esc_attr_e( 'Line', 'wp-verifier' ); ?>">
		{{data.line}}
	</td>
	<td data-label="<?php esc_attr_e( 'Column', 'wp-verifier' ); ?>">
		{{data.column}}
	</td>
	<td data-label="<?php esc_attr_e( 'Type', 'wp-verifier' ); ?>">
		{{data.type}}
	</td>
	<td data-label="<?php esc_attr_e( 'Code', 'wp-verifier' ); ?>">
		<span class="error-icon" data-code="{{data.code}}">{{{data.icon}}}</span>
		{{data.code}}
	</td>
	<td data-label="<?php esc_attr_e( 'Message', 'wp-verifier' ); ?>">
		{{{data.message}}}
		<# if ( data.docs ) { #>
			<br>
			<a href="{{data.docs}}" target="_blank">
				<?php esc_html_e( 'Learn more', 'wp-verifier' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'wp-verifier' ); ?></span>
				<span aria-hidden="true" class="dashicons dashicons-external"></span>
			</a>
		<# } #>
		<br>
		<button type="button" class="button button-small copy-for-ai" data-code="{{data.code}}" data-message="{{{data.message}}}">
			<?php esc_html_e( 'Copy for AI', 'wp-verifier' ); ?>
		</button>
	</td>
	<# if ( data.hasLinks ) { #>
		<td>
			<# if ( data.link ) { #>
				<a href="{{data.link}}" target="_blank">
					<?php esc_html_e( 'View in code editor', 'wp-verifier' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'wp-verifier' ); ?></span>
					<span aria-hidden="true" class="dashicons dashicons-external"></span>
				</a>
			<# } #>
		</td>
	<# } #>
</tr>

