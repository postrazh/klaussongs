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


<?php 
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => 1
    );

    $query = new WP_Query($args);

    while($query->have_posts()) : $query->the_post();
        $dontshowthisguy = $post->ID;
        ?>
            <div class="featured">
                <h5><?php the_title(); ?></h5>
            </div>

        <?php endwhile; wp_reset_query();  ?>

        <?php 
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => 5,
                'post__not_in' => array($dontshowthisguy)
            );

            $query = new WP_Query($args);

            while($query->have_posts()) : $query->the_post();
        ?>
            
            <p><?php the_title(); ?></p>
            

        <?php endwhile; wp_reset_query();  ?>





<!-- basic loop in post -->

<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    
    <?php the_title(); ?><br>

<?php endwhile; else : ?>
    <p><?php esc_html_e( 'Sorry, no posts matched your criteria.' ); ?></p>
<?php endif; ?>


<?php 
    $query_args = array(


);

$query = new WP_Query( $query_args ); 
while($query -> have_posts()) : $query -> the_post();
?>
And stuff here
<?php endwhile; wp_reset_query(); ?>









</div>

<?php get_footer();
