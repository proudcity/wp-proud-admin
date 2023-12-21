<?php if ($metatags) {
	// https://developer.wordpress.org/apis/security/escaping/#toc_2
	echo wp_kses(
		$metatags,
		array(
			'meta' => array(
				'name' => array(),
				'content' => array(),
			)
		)
	);
} ?>
