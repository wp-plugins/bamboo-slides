<?php
/******************************************************************/
/*
Plugin Name:	Bamboo Slides
Plugin URI:  http://www.bamboosolutions.co.uk/wordpress/bamboo-slides
Author:      	Bamboo Solutions
Author URI:	http://www.bamboosolutions.co.uk
Version:     1.5
Description: With three different animation styles, Bamboo Slides allows you to incorporate a cool looking interactive banner or slideshow into any page – no coding or Flash required.
*/
/******************************************************************/

	add_action( 'init', 		'bamboo_slides_init' );
	add_action( 'admin_init',	'bamboo_slides_admin_init' );
	add_action( 'save_post',	'bamboo_slides_save_post' );

	add_shortcode( 'bamboo-slides', 'bamboo_slides' );

/******************************************************************/

	function bamboo_slides_init() {

		// REGISTER SLIDER POST TYPE
		register_post_type( 'bamboo_slide',
			array(
				'labels'	=> array(
					'name' 			=> __( 'Bamboo Slide', 	'bamboo' ),
					'singular_name'	=> __( 'Slide', 			'bamboo' ),
					'menu_name' 	=> __( 'Bamboo Slides', 'bamboo' ),
					'all_items' 		=> __( 'All Slides', 	'bamboo' ),
					'add_new_item' 	=> __( 'Add New Slide',	'bamboo' ),
					'edit_item' 		=> __( 'Edit Slide', 	'bamboo' ),
					'new_item' 		=> __( 'New Slide', 		'bamboo' ),
					'view_item' 		=> __( 'View Slide', 	'bamboo' ),
					'search_items' 	=> __( 'Search Slides', 	'bamboo' )
				),
				'public' 			  	=> true,
				'has_archive'	 	  	=> true,
				'exclude_from_search' 	=> true,
				'show_in_nav_menus'   	=> false,
				'menu_position'			=> 20,
				'supports' 			  	=> array ( 'title', 'editor', 'tags', 'thumbnail')
			)
		);

		// REGISTER SLIDE GROUP TAXONOMY
		register_taxonomy( 'bamboo_slide_group', 'bamboo_slide',
			array(
				'hierarchical'	=> true,
				'labels'			=> array(
					'name'                       	=> _x( 'Slide Group', 'taxonomy general name' ),
					'singular_name'              	=> _x( 'Slide Group', 'taxonomy singular name' ),
					'search_items'               	=> __( 'Search Slide Groups' ),
					'popular_items'              	=> __( 'Popular Slide Groups' ),
					'all_items'                  	=> __( 'All Slide Groups' ),
					'parent_item'                	=> null,
					'parent_item_colon'          	=> null,
					'edit_item'                  	=> __( 'Edit Slide Group' ),
					'update_item'                	=> __( 'Update Slide Group' ),
					'add_new_item'              	=> __( 'Add New Slide Group' ),
					'new_item_name'              	=> __( 'New Slide Group Name' ),
					'separate_items_with_commas'	=> __( 'Separate slide groups with commas' ),
					'add_or_remove_items'        	=> __( 'Add or remove slide groups' ),
					'choose_from_most_used'      	=> __( 'Choose from the most used slide groups' ),
					'not_found'                  	=> __( 'No slide groups found.' ),
					'menu_name'                  	=> __( 'Slide Groups' ),
				),
				'show_ui'               => true,
				'show_admin_column'     => true,
				'update_count_callback'	=> '_update_post_term_count',
				'query_var'             => true,
				'rewrite'               => array( 'slug' => 'slide-group' )
			)
		);

		// REGISTER THE SLIDE GROUP TAXONOMY WITH THE SLIDER POST TYPE
		register_taxonomy_for_object_type( 'bamboo_slide_group', 'bamboo_slide' );

	}

/******************************************************************/

	function bamboo_slides( $atts, $content = null )
	{

		// ESTABLISH THE ATTRIBUTE VALUES
		$mode = "jump";
		if ( isset( $atts["mode"] ) ) $mode = $atts["mode"];

		$timer = "0";
		if ( isset( $atts["timer"] ) ) $timer = $atts["timer"];

		$group = "";
		if ( isset( $atts["group"] ) ) $group = $atts["group"];

		$buttons = false;
		if ( isset( $atts["buttons"] ) ) $buttons = true;

		$start = "1";
		if ( isset( $atts["start"] ) ) $start = $atts["start"];

		// PROVIDE AN ACTION HOOK DIRECTLY BEFORE THE SLIDES
		do_action( 'before_bamboo_slides' );

		// START CONSTRUCTING THE HTML
		$html = "<div class=\"bamboo-slides color-0 mode-$mode timer-$timer start-$start\" style=\"visibility: hidden;\">";

		// QUERY THE SLIDES
		$args = array( 'post_type'=>'bamboo_slide', 'orderby'=>'title', 'order'=>'ASC', 'posts_per_page'=>'-1');
		if( ""!=$group ) {
			$args['bamboo_slide_group'] = $group;
		}

		// SLIDES LOOP
		$loop = new WP_Query( $args );
		while ($loop->have_posts()) : $loop->the_post();

			// GET THE ID OF THE SLIDE
			$id = get_the_ID();

			// ESTABLISH THE BACKGROUND STYLE FOR THE SLIDE
			$background = '';
			if( has_post_thumbnail( $id ) ) {
				$url = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'single-post-thumbnail' );
				$background = ' style="background: url(' . $url[0] . ') no-repeat center center; background-size: cover;" ';
			}

			// ESTABLISH IF THE SLIDE HAS A LINK URL
			$url = get_post_meta( $id, 'link_url', true );

			// CREATE THE HTML FOR THE SLIDE
			if( $url!='' ) {
				$html.= "<a class=\"bamboo-slide\"$background href=\"$url\">";
			} else {
				$html.= "<div class=\"bamboo-slide\"$background>";
			}

			$html.= do_shortcode( get_the_content() );

			if( $url!='' ) {
				$html.= "</a>";
			} else {
				$html.= "</div>";
			}

		endwhile;

		// RESET THE WP QUERY
		wp_reset_query();

		// ADD BUTTONS TO THE HTML IF REQUIRED
		if( $buttons ) {
			$html.= "<div class=\"bamboo-slides-prev-button\"><i class=\"fa fa-chevron-circle-left\"></i></div>";
			$html.= "<div class=\"bamboo-slides-next-button\"><i class=\"fa fa-chevron-circle-right\"></i></div>";
		}

		// FINISH CONSTRUCTING THE HTML
		$html.= "</div>";

	    	// PROVIDE AN ACTION HOOK DIRECTLY AFTER THE SLIDES
	    	do_action( 'after_bamboo_slides' );

		// ENQUEUE STYLESHEETS
		$path = plugins_url( '', __FILE__ );
		wp_enqueue_style( 'bamboo-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css' );
		wp_enqueue_style( 'bamboo-slides', 		 $path.'/bamboo-slides.css' );

        // ENQUEUE JAVASCRIPT
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery.velocity', $path.'/jquery.velocity.min.js' );
		wp_enqueue_script( 'bamboo-slides',	  $path.'/bamboo-slides.js' );

		// RETURN THE HTML
		return $html;

	}

/******************************************************************/

	function bamboo_slides_admin_init() {

		// ADD THE 'LINK URL', EDITOR TO THE BAMBOO SLIDE POST TYPE
		add_meta_box( 'link_editor', 'Link URL <em>(leave this blank if there are links in the banner content)</em>', 'bamboo_slides_render_link_editor', 'bamboo_slide', 'normal' );

	}

/******************************************************************/

	function bamboo_slides_save_post() {

		if ( sizeof($_POST)==0 ) {
			return;
		}

		if ( !isset( $_POST['post_type'] ) ) {
			return;
		}

		if ( 'bamboo_slide' != $_POST['post_type'] ) {
	        	return;
	    	}

		// IF THE POST CONTAINS A LINK SAVE THE CONTENT
        if( isset( $_REQUEST['link_url'] ) ) {
			update_post_meta( $_REQUEST['post_ID'], 'link_url', $_REQUEST['link_url'] );
		}

	}

/******************************************************************/

	function bamboo_slides_render_link_editor() {

		global $post;

		$value = get_post_meta( $post->ID, 'link_url', true );

		echo "<label class=\"selectit\" for=\"link_url\"></label><input type=\"text\" value=\"$value\" name=\"link_url\" id=\"link_url\" />";
		echo "<div style='clear:both; display:block;'></div>";

	}

/******************************************************************/
?>