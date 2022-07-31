<?php
$posts = get_posts(array(
	'post_type' => 'post',
	'posts_per_page' => -1,
));


foreach ( $posts as $index => $post ){
	$attached = get_post_thumbnail_id( $post->ID );
	if( $attached ) continue;

	//$attachment = get_post_meta( $post->ID, '_saved_image_featured_image_url', true );
	preg_match("#src='([^']+)'#", $post->post_content, $matches);
	$attachment = $matches[1];

	if( $attachment ){
		echo $post->ID . PHP_EOL;
		$filename = str_replace((get_site_url() . '/'), '', $attachment);

		// ID поста, к которому прикрепим вложение.
		$parent_post_id = $post->ID;

		// Проверим тип поста, который мы будем использовать в поле 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $filename ), null );

		// Получим путь до директории загрузок.
		$wp_upload_dir = wp_upload_dir();

		// Подготовим массив с необходимыми данными для вложения.
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Вставляем запись в базу данных.
		$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

		// Подключим нужный файл, если он еще не подключен
		// wp_generate_attachment_metadata() зависит от этого файла.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Создадим метаданные для вложения и обновим запись в базе данных.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		set_post_thumbnail( $post->ID, $attach_id );
	}
}
die();
