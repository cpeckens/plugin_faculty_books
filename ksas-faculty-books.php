<?php
/*
Plugin Name: KSAS Faculty Books Metabox for Posts
Plugin URI: http://krieger.jhu.edu/communications/web/plugins/faculty-books
Description: Creates the metabox for faculty books details.
Version: 1.0
Author: Cara Peckens
Author URI: mailto:cpeckens@jhu.edu
License: GPL2
*/

$faculty_books_metabox = array( 
	'id' => 'faculty_books',
	'title' => 'Faculty Books Details',
	'page' => array('post'),
	'context' => 'normal',
	'priority' => 'high',
	'fields' => array(

				array(
					'name' 			=> 'Publisher',
					'desc' 			=> '',
					'id' 			=> 'ecpt_publisher',
					'class' 		=> 'ecpt_publisher',
					'type' 			=> 'text',
					'max' 			=> 0,
					'std'			=> ''													
				),
				array(
					'name' 			=> 'Publication Date',
					'desc' 			=> '',
					'id' 			=> 'ecpt_pub_date',
					'class' 		=> 'ecpt_pub_date',
					'type' 			=> 'text',
					'max' 			=> 0,
					'std'			=> ''													
				),
				array(
					'name' 			=> 'Purchase (Amazon) Link',
					'desc' 			=> '(Do NOT include http://)',
					'id' 			=> 'ecpt_pub_link',
					'class' 		=> 'ecpt_pub_link',
					'type' 			=> 'text',
					'max' 			=> 0,
					'std'			=> ''													
				),
								
				array(
					'name' 			=> 'Author',
					'desc' 			=> '',
					'id' 			=> 'ecpt_pub_author',
					'class' 		=> 'ecpt_pub_author',
					'type' 			=> 'select',
					'max' 			=> 0,
					'std'			=> ''
				),				
				array(
					'name' 			=> 'Role',
					'desc' 			=> '',
					'id' 			=> 'ecpt_pub_role',
					'class' 		=> 'ecpt_pub_role',
					'type' 			=> 'radio',
					'options' => array('author','co-author','editor', 'contributor'),
					'max' 			=> 0,
					'std'			=> 'author'
				),				


				
));			
			
add_action('admin_menu', 'ecpt_add_faculty_books_meta_box');
function ecpt_add_faculty_books_meta_box() {

	global $faculty_books_metabox;		

	foreach($faculty_books_metabox['page'] as $page) {
		add_meta_box($faculty_books_metabox['id'], $faculty_books_metabox['title'], 'ecpt_show_faculty_books_box', $page, 'normal', 'default', $faculty_books_metabox);
	}
}

// function to show meta boxes
function ecpt_show_faculty_books_box()	{
	global $post;
	global $faculty_books_metabox;
	global $ecpt_prefix;
	global $wp_version;
	
	// Use nonce for verification
	echo '<input type="hidden" name="ecpt_faculty_books_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
	
	echo '<table class="form-table">';

	foreach ($faculty_books_metabox['fields'] as $field) {
		// get current post meta data

		$meta = get_post_meta($post->ID, $field['id'], true);
		
		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', $field['name'], '</label></th>',
				'<td class="ecpt_field_type_' . str_replace(' ', '_', $field['type']) . '">';
		switch ($field['type']) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" /><br/>', '', $field['desc'];
				break;
			case 'select' :
				$author_select_query = new WP_Query(array(
					'post-type' => 'people',
					'role' => 'faculty',
					'meta_key' => 'ecpt_people_alpha',
					'orderby' => 'meta_value',
					'order' => 'ASC',
					'posts_per_page' => '-1')); 
				echo '<select name="', $field['id'], '" id="', $field['id'], '">';
				while ($author_select_query->have_posts()) : $author_select_query->the_post();
					$option = $post->ID;
					$selected = '';
					if($option == $meta) {
						$selected = 'selected="selected"';
					}
					echo '<option value="'. $post->ID .'"' . $selected .'>';
						the_title();
					echo '</option>';
				endwhile;
				echo '</select>', '', stripslashes($field['desc']);
				break;
			case 'radio':
				echo '<select name="', $field['id'], '" id="', $field['id'], '">';
				foreach ($field['options'] as $option) {
					echo '<option value="' . $option . '"', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
				}
				echo '</select>', '', stripslashes($field['desc']);
				break;
		}
		echo     '<td>',
			'</tr>';
	}
	
	echo '</table>';
}	

add_action('save_post', 'ecpt_faculty_books_save');

// Save data from meta box
function ecpt_faculty_books_save($post_id) {
	global $post;
	global $faculty_books_metabox;
	
	// verify nonce
	if (!isset($_POST['ecpt_faculty_books_meta_box_nonce']) || !wp_verify_nonce($_POST['ecpt_faculty_books_meta_box_nonce'], basename(__FILE__))) {
		return $post_id;
	}

	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}

	// check permissions
	if ('page' == $_POST['post_type']) {
		if (!current_user_can('edit_page', $post_id)) {
			return $post_id;
		}
	} elseif (!current_user_can('edit_post', $post_id)) {
		return $post_id;
	}
	
	foreach ($faculty_books_metabox['fields'] as $field) {
	
		$old = get_post_meta($post_id, $field['id'], true);
		$new = $_POST[$field['id']];
		
		if ($new && $new != $old) {
			if($field['type'] == 'date') {
				$new = ecpt_format_date($new);
				update_post_meta($post_id, $field['id'], $new);
			} else {
				update_post_meta($post_id, $field['id'], $new);
				
				
			}
		} elseif ('' == $new && $old) {
			delete_post_meta($post_id, $field['id'], $old);
		}
	}
}
function add_faculty_book_category() {
		wp_insert_term('Faculty Books', 'category',  array('description'=> '','slug' => 'books'));
	}
add_action('init', 'add_faculty_book_category');

/*************Faculty Books Widget*****************/
class Faculty_Books_Widget extends WP_Widget {
	function Faculty_Books_Widget() {
		$widget_options = array( 'classname' => 'ksas_books', 'description' => __('Displays faculty books at random', 'ksas_books') );
		$control_options = array( 'width' => 300, 'height' => 350, 'id_base' => 'ksas_books-widget' );
		$this->WP_Widget( 'ksas_books-widget', __('Faculty Books', 'ksas_books'), $widget_options, $control_options );
	}

	/* Widget Display */
	function widget( $args, $instance ) {
		extract( $args );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$quantity = $instance['quantity'];
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;
		$books_widget_query = new WP_Query(array(
					'post_type' => 'post',
					'category_name' => 'books',
					'posts_per_page' => $quantity,
					'orderby' => 'random',
					));
		if ( $books_widget_query->have_posts() ) :  while ($books_widget_query->have_posts()) : $books_widget_query->the_post(); global $post;?>
				<article class="row">
				<?php $faculty_post_id = get_post_meta($post->ID, 'ecpt_pub_author', true); ?>
						<a href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail()) { ?> 
								<?php the_post_thumbnail('thumbnail'); ?>
							<?php } ?>
							<h6><?php the_title(); ?></h6>
							<p><b>Author: <?php echo get_the_title($faculty_post_id); ?></b><br>
						</a>
				</article>
		<?php endwhile; endif;  echo $after_widget;
	}

	/* Update/Save the widget settings. */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['quantity'] = strip_tags( $new_instance['quantity'] );

		return $instance;
	}

	/* Widget Options */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Faculty Books', 'ksas_books'), 'quantity' => __('3', 'ksas_books'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Number of Stories: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'quantity' ); ?>"><?php _e('Number of stories to display:', 'ksas_books'); ?></label>
			<input id="<?php echo $this->get_field_id( 'quantity' ); ?>" name="<?php echo $this->get_field_name( 'quantity' ); ?>" value="<?php echo $instance['quantity']; ?>" style="width:100%;" />
		</p>

	<?php
	}
}

function ksas_load_faculty_books_widget() {
	register_widget('Faculty_Books_Widget');
}
	add_action( 'widgets_init', 'ksas_load_faculty_books_widget' );

?>