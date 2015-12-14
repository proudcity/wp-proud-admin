<?php
//custom logo
function custom_login_logo() {
    //check if set
    $logo = get_option( 'wp_flat_admin_custom_logo_path' );
    if(empty($logo)){
        $logo = plugin_dir_url( __FILE__ )."../assets/images/dashboard-icon.png";
    }
    ?>
    <style type="text/css">
        .login h1 a {
            background-image: url("<?php echo $logo; ?>");
        }
    </style>
<?php
}
add_action( 'login_enqueue_scripts', 'custom_login_logo' );

//custom logo url
function custom_login_logo_url(){
    $custom_url = get_option( 'custom_login_logo_url' );
    if(empty($custom_url)){
        $custom_url = get_bloginfo('url');
    }
    return ($custom_url);
}
add_filter('login_headerurl', 'custom_login_logo_url');

//custom logo url title
function custom_login_logo_url_title(){
    $custom_url_title = get_option( 'custom_url_title' );
    if(empty($custom_url_title)){
        $custom_url_title = get_bloginfo('name');
    }
    return ($custom_url_title);
}
add_filter('login_headertitle',  'custom_login_logo_url_title');
?>
