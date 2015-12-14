<?php
//custom logo
function custom_login_logo() { ?>
    <style type="text/css">
        .login h1 a {
            background-image: url(<?php echo plugin_dir_url( __FILE__ ); ?>../assets/images/dashboard-icon.png);
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'custom_login_logo' );
?>
