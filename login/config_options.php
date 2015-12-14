<?php

//must check that the user has the required capability
if (!current_user_can('manage_options'))
{
    wp_die( __('You do not have sufficient permissions to access this page.') );
}

// variables for the field and option names
$opt_name = 'wp_flat_admin_custom_logo_path';
$hidden_field_name = 'wp_flat_admin_submit_hidden';
$data_field_name = 'wp_flat_admin_custom_logo_path';

$custom_url_title = get_option( 'custom_url_title' );
if(empty($custom_url_title)){
    $custom_url_title = get_bloginfo('name');
}


$custom_url = get_option( 'custom_login_logo_url' );
if(empty($custom_url)){
    $custom_url = get_bloginfo('url');
}

//check for post
if(isset($_POST[$data_field_name])){
    $opt_val = $_POST[ $data_field_name ];

    //Save the posted value in the database
    update_option( 'wp_flat_admin_custom_logo_path', $opt_val );
    update_option( 'custom_login_logo_url', $_POST['custom_login_logo_url'] );
    update_option( 'custom_url_title', $_POST['custom_url_title'] );
}

// Read in existing option value from database
$opt_val = get_option( $opt_name );


// Now display the settings editing screen

echo '<div class="wrap" id="manage-login-logo">';

// header

echo "<h4>" . __( 'WP Flat Admin Settings', 'menu-test' ) . "</h4>";

// settings form

?>


<!--- stuff -->
<?php
   // wp_enqueue_media();
?>

<form action="" method="post" id="submit_picture">
    Custom login logo <br />
    <input placeholder="Upload image or add url" id="upload_image" type="text" name="<?php echo $data_field_name; ?>"  value="<?php echo $opt_val; ?>" class="form-control"/>
    <input id="upload_image_button" class="button" type="button" value="Upload Custom Logo" />
    <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
<br />
    Custom login logo link
    <br />
    <input placeholder="Custom login logo link" type="text" name="custom_url"  value="<?php echo $custom_url; ?>" class="form-control"/>
<br />
    Custom login logo title<br />
    <input placeholder="Custom login logo title" type="text" name="custom_url_title"  value="<?php echo $custom_url_title; ?>" class="form-control"/>
<br />
    <input type="submit" id="save_image" class="button button-primary" value="save">
</form>
<div id="show_image">

    <?php
        if(!empty($opt_val)){
            print "<img src='" .$opt_val. "'>";
        }
    ?>

</div>

<?php

if (isset($_GET['page']) && $_GET['page'] == 'wpflatadmin') {
    wp_enqueue_media();
    wp_enqueue_script('logo-uploader-js', plugin_dir_url( __FILE__ ) .'../assets/js/logo-uploader.js', array('jquery'));
}

?>

</div>
