<?php
/**
 * AI Providers Configuration
 *
 * @package wp-verifier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'openai'    => array(
		'label'  => 'OpenAI',
		'models' => array(
			'gpt-4'         => 'GPT-4',
			'gpt-4-turbo'   => 'GPT-4 Turbo',
			'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
		),
	),
	'anthropic' => array(
		'label'  => 'Anthropic',
		'models' => array(
			'claude-3-opus-20240229'   => 'Claude 3 Opus',
			'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
			'claude-3-haiku-20240307'  => 'Claude 3 Haiku',
		),
	),
	'gemini'    => array(
		'label'  => 'Google Gemini',
		'models' => array(
			'gemini-pro'    => 'Gemini Pro',
			'gemini-ultra'  => 'Gemini Ultra',
		),
	),
);
