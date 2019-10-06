<?php
/**
 * The blog template file.
 *
 * @package flatsome
 */

get_header();




global $wpdb;

$results = $wpdb->get_results( "
    SELECT attribute_name
    FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
" );

if ( count($results) > 0) {
    $data = []; // Initializing

    // Loop through results objects
    foreach( $results as $result ) {
        // add each value to an array
        $data[] = $result->attribute_name . "<br>";
    }

    // output data of all rows
    echo implode( "<br>", $data );
} else {
    echo "0 results";
}



$output = '<ul style="list-style:none;">';

// Loop through all Woocommerce product attributes
foreach( wc_get_attribute_taxonomies() as $attribute ) {
    $attribute_label = $attribute->attribute_label; // Product attribute name
    $attribute_slug  = $attribute->attribute_name;  // Product attribute slug

    $output .= '<li class="'.$attribute_slug.'">' . $attribute_label . '</li>';
}
// Output
echo $output . '</ul>';





?>


<?php

$servername = "localhost";
$username = "i2042577_wp4";
$password = "D.2EX5bxhCAIoQaxFqB96";
$dbname = "i2042577_wp4";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
// $sql = "SELECT wp_posts FROM wp_woocommerce_attribute_taxonomies"; 
// $sql = "SELECT post_title FROM wp_posts WHERE post_type = 'product' "; 
// $sql = "SELECT taxonomy FROM wp_term_taxonomy WHERE term_taxonomy_id = 19 "; 

$sql = "SELECT p.`ID` AS 'Product ID', p.`post_title` AS 'Product Name', t.`name` AS 'carat'
       FROM `wp_posts` AS p
       INNER JOIN `wp_term_relationships` AS tr ON p.`ID` = tr.`object_id`
       INNER JOIN `wp_term_taxonomy` AS tt ON tr.`term_taxonomy_id` = tt.`term_id`
       AND tt.`taxonomy` LIKE 'pa_carat%'
       INNER JOIN `wp_terms` AS t ON tr.`term_taxonomy_id` = t.`term_id`
       WHERE p.`post_type` = 'product'
       AND p.`post_status` = 'publish' ";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
         echo $row['carat'] . "<br>";
    }
} else {
    echo "0 results";
}
$conn->close();

?>

<div id="content" class="blog-wrapper blog-single page-wrapper">


<h1>evething before this doesn't work</h1>


    <table style="width:100%" class="pr-tb">
  <tr>
    <th>Title</th> 
    <th>Shape</th> 
    <th>Carat</th>
    <th>Color</th>
    <th>Clarity</th>
    <th>Cut</th>
    <th>Report</th>
    <th>Symmetry</th>
    
  </tr>
  <tr>
    <td><?php ?></td>
    <td>Shape</td>
    <td>Carat</td>
    <td>Color</td>
    <td>Clarity</td>
    <td>Cut</td>
    <td>Report</td>
    <td>Symmetry</td>
    
  </tr>
</table>

<!-- this snippet shows the wp posts -->
<div class="container">

<?php
    $params = array('posts_per_page' => 5); // (1)
    $wc_query = new WP_Query($params); // (2)
?>
<?php if ($wc_query->have_posts()) : // (3) ?>
<?php while ($wc_query->have_posts()) : // (4)
                    $wc_query->the_post(); // (4.1) ?>
<?php the_title(); // (4.2) ?><br>
<?php endwhile; ?>
<?php wp_reset_postdata(); // (5) ?>
<?php else:  ?>
    <p><?php _e( 'No Products' ); // (6) ?></p>
<?php endif; ?>
         

<!-- This parameter shows wc products -->
<?php
    $params = array('posts_per_page' => 5, 'post_type' => 'product'); 
    $wc_query = new WP_Query($params); 
?>
<?php if ($wc_query->have_posts()) :  ?>
<?php while ($wc_query->have_posts()) : $wc_query->the_post();  ?>

<?php the_title();?><br>


<?php endwhile; ?>
<?php wp_reset_postdata();  ?>
<?php else:  ?>
    <p><?php _e( 'No Products' );  ?></p>
<?php endif; ?>


<!-- this piece of code output all attributes at least -->
<!-- this piece of code output all attributes at least -->
<!-- this piece of code output all attributes at least -->
<!-- this piece of code output all attributes at least -->
<!-- this piece of code output all attributes at least -->
<select name="attribute_taxonomy" class="attribute_taxonomy">
    <option value=""><?php esc_html_e('product attribute', 'woocommerce'); ?></option>
    <?php
    global $wc_product_attributes;

    // Array of defined attribute taxonomies.
    $attribute_taxonomies = wc_get_attribute_taxonomies();

    if (!empty($attribute_taxonomies)) {
        foreach ($attribute_taxonomies as $tax) {
            $attribute_taxonomy_name = wc_attribute_taxonomy_name($tax->attribute_name);
            $label = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
            echo '<option value="' . esc_attr($attribute_taxonomy_name) . '">' . esc_html($label) . '</option>';

        }
    }
    ?>
</select>


<!--  this code talks about attribute but don't know what it says -->
<?php 
/**
 * Display product attribute archive links 
 */
add_shortcode( 'hell', 'wc_show_attribute_links' );
// if you'd like to show it on archive page, replace "woocommerce_product_meta_end" with "woocommerce_shop_loop_item_title"

function wc_show_attribute_links() {
    global $post;
    $attribute_names = array( '<ATTRIBUTE_NAME>', '<ANOTHER_ATTRIBUTE_NAME>' ); // Add attribute names here and remember to add the pa_ prefix to the attribute name
        
    foreach ( $attribute_names as $attribute_name ) {
        $taxonomy = get_taxonomy( $attribute_name );
        
        if ( $taxonomy && ! is_wp_error( $taxonomy ) ) {
            $terms = wp_get_post_terms( $post->ID, $attribute_name );
            $terms_array = array();
        
            if ( ! empty( $terms ) ) {
                foreach ( $terms as $term ) {
                   $archive_link = get_term_link( $term->slug, $attribute_name );
                   $full_line = '<a href="' . $archive_link . '">'. $term->name . '</a>';
                   array_push( $terms_array, $full_line );
                }
                echo $taxonomy->labels->name . ' ' . implode( $terms_array, ', ' );
            }
        }
    }
    return $post;
}

?>
</div>

<?php get_footer();
