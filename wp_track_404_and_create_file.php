<?php
add_action('wp_ajax_wccf_create_non_exist_file', function(){
	$url 	= urldecode( $_POST['url'] );
	$path 	= ABSPATH . str_replace( ( get_site_url() . '/') , '', $url );
	
	if( true || ! file_exists( $path ) ){
		$url = str_replace( get_site_url(), 'https://wccftech.com/', $url );
		$dirname = dirname( $path );
		if (! file_exists($dirname) ) {
			mkdir($dirname, 0777, true);
		}
		
		$content = file_get_contents( str_replace('-1.jpg', '.jpg', $url ) );
		if( $content ){
			$write = file_put_contents( $path, $content);
			
			$url = str_replace('https://wccftech.com/', get_site_url(), $url);
			
			
		}
	}
	
	wp_send_json([
		'success' 	=> true,
		'url' 		=> $url,
		'dirname' 	=> $dirname,
		'path'		=> $path,
		'write'     => $write
	]);
});
?>
<script>
	document.querySelectorAll('img').forEach(node => {
		node.onerror = function(){
			let formData = new FormData();
			formData.append('action', 'wccf_create_non_exist_file');
			formData.append('url', this.src);
			fetch('/wp-admin/admin-ajax.php', {
				method: 'POST',
				body: formData,
			}).then( response => response.json() ).then( data =>{
				console.log( data );
			});
		};
	});
</script>
