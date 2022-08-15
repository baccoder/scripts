<?php
add_action('wp_ajax_nopriv_cosmo_apply_gallery_images', function(){
	function send_debug($message){
		echo $message . PHP_EOL;
		return;
	}

	function find_and_return_attachmend_id( $filename, $parent_post_id = 0 ){
		$filename = trim( $filename );

		$base_dir_1 = ABSPATH . 'wp-content/uploads/2022/gallery/';
		$base_dir_2 = ABSPATH . 'wp-content/uploads/2022/gallery/products/';
		$base_dir_3 = ABSPATH . 'wp-content/uploads/wpallimport/files2/';

		if( file_exists( $base_dir_1 . $filename ) ){
			$filename = $base_dir_1 . $filename;
		}
		else if( file_exists( $base_dir_2 . $filename ) ){
			$filename = $base_dir_2 . $filename;
		}
		else if( file_exists( $base_dir_3 . $filename ) ){
			$filename = $base_dir_3 . $filename;
		}
		else{
			send_debug("File {$filename} for {$parent_post_id} not found in any directory, check it manual");

			return null;
		}



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

		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$values = wp_generate_attachment_metadata($attach_id, $filename);
		wp_update_attachment_metadata($post_id, $values);

		return $attach_id;
	}



	$data = file_get_contents( ABSPATH . 'wp-content/themes/woodmart/data.csv');
	$data = explode("\n", $data);

	array_shift( $data );
	
	if( $_GET['reversed'] === '1' ){
		$data = array_reverse( $data );
	}

	$images_dir = ABSPATH . '/wp-content/uploads/wpallimport/files/';
	$counter = 0;
	foreach( $data as $index => $row ){
		$row = explode(';', $row);
		$title = $row[0];
		$images = explode(',', $row[1] );
		array_shift( $images );

		if( ! $images ){
			continue;
		}

		$post = get_page_by_title( $title, OBJECT, 'product' );
		if( ! $post ){
			send_debug("[{$index}] Title not found");
		}

		$product = wc_get_product( $post->ID );
		if( ! $product ){
			send_debug("[{$index}] Product with ID {$post->ID} not found");
		}

		if( $product->get_gallery_image_ids() ){
			continue;
		}

		$gallery_ids = [];
		foreach( $images as $image ){
			$gallery_ids[] = find_and_return_attachmend_id( $image, $product->get_id() );
		}
		$product->set_gallery_image_ids( $gallery_ids );
		$product->save();
		
		if( $_GET['reversed'] === '1' ){
			$index = count( $data ) - $index;
		}
		
		echo '[+] ' . date('d.m.Y H:i:s') . ' - ' . $product->get_id() . ' | Index = ' . $index;
		break;
	}

	die();
});
