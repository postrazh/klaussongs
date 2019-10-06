<?php
/**
 * The blog template file.
 *
 * @package flatsome
 */

get_header();

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



<!-- display all products the loop is in business -->
    <ul class="products">
    <?php
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 12
            );
        $loop = new WP_Query( $args );
        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) : $loop->the_post();
                wc_get_template_part( 'content', 'product' );
            endwhile;
        } else {
            echo __( 'No products found' );
        }
        wp_reset_postdata();
    ?>
</ul><!--/.products-->

<?php 

if ( have_posts() ) {
    do_action( 'woocommerce_before_shop_loop' );
    woocommerce_product_loop_start();
    if ( wc_get_loop_prop( 'total' ) ) {
        while ( have_posts() ) {
            the_post();
            do_action( 'woocommerce_shop_loop' );
            wc_get_template_part( 'content', 'product' );
        }
    }
    woocommerce_product_loop_end();
    do_action( 'woocommerce_after_shop_loop' );
} else {
    do_action( 'woocommerce_no_products_found' );
}



?>






</div>

<?php get_footer();
