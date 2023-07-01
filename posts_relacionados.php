<?php
/**
 * Plugin Name: Posts relacionados
 * Description: Nas páginas de posts, cats e tags, exibe os posts relacionados a partir das categorias
 * Version: 0.2
 * Author: pi
 */

class Relacionados_Widget extends WP_Widget {
	private $titulo_padrao = 'Posts relacionados';
	public function __construct() {
		$widget_ops = array(
				'classname' => 'Relacionados_Widget',
				'description' => 'Widget que exibe os posts relacionados',
		);
		parent::__construct( 'Relacionados_Widget', 'Posts Relacionados', $widget_ops ); //nome do widget
	}
	public function widget( $args, $instance ) {
		$cat_id = null; $tag_id = null; $rel = null; $post__not_in =null;
		global $wp_query, $post;

		if (is_category()) {
			$cat_id = absint(get_query_var('cat')); //página de categoria
			if (have_posts()) { while (have_posts()) { the_post(); 
				$post__not_in[]= $post->ID;
			}}
		}elseif(is_tag()) {
			$tag_id = get_queried_object()->term_id;  //página de tag
			if (have_posts()) { while (have_posts()) { the_post(); 
				$post__not_in[]= $post->ID;
			}}
		}elseif (is_single()){ ///página de post. pega a categoria
			$cats = get_the_category($post->ID);
			$post__not_in = array($post->ID);
			//pega o último nível, mais específica, menos posts
			if(array_key_exists(0,$cats)){if(property_exists($cats[0],'term_id'))$cat_id = $cats[0]->term_id ;}
		}

		if(!empty($cat_id) or !empty($tag_id)){ //pega os posts relacionados pela taxonomia (tag ou cat)
			$querie =  array(  'posts_per_page'      => $instance['qtd'],
							'no_found_rows'       => true, //pula contagem de todos posts (inútil quando não tem paginação), pra melhorar perfomance.
							'post_type' => 'post',
	 //                       'orderby' => 'date',
						   'orderby' => 'rand', //ordem randômica
	 //                       'order' => 'DESC',
							'post_status'         => 'publish',
							'ignore_sticky_posts' => true, //pular posts fixos na página inicial
			);

			if($post__not_in) $querie['post__not_in'] = $post__not_in; //retirar posts já exibidos
			
			//retirar repetidos das páginas de tag

			if(!empty($cat_id)) $querie['category__in'] = array($cat_id);
			else $querie['tag__in'] = array($tag_id);

			$rel = new WP_Query($querie);
		}
		if(is_object($rel)){if ($rel->have_posts()){
			echo $args['before_widget'];
			$titulo = apply_filters( 'widget_title', $instance['title'] );
			if (empty($titulo)) $titulo = $this->titulo_padrao;
			echo $args['before_title'] , $titulo , $args['after_title'];

			$format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';
			$format = apply_filters( 'navigation_widgets_format', $format );

			if ( 'html5' === $format ) {
					// The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
					$title      = trim( strip_tags( $title ) );
					$aria_label = $title ? $title : $default_title;
					echo '<nav aria-label="' . esc_attr( $aria_label ) . '">';
			}
			?>

			<ul>
			<?php foreach ( $rel->posts as $rel_post ) :
				$post_title   = get_the_title( $rel_post->ID );
				$title        = ( ! empty( $post_title ) ) ? $post_title : __( '(no title)' );
				$aria_current = '';
				if ( get_queried_object_id() === $rel_post->ID ) $aria_current = ' aria-current="page"';
			?>
				<li>
					<a href="<?php the_permalink( $rel_post->ID ); ?>"<?php echo $aria_current; ?>><?php echo $title; ?></a>
				</li>
			<?php endforeach; ?>
			</ul>
			<?php
			if ( 'html5' === $format ) echo '</nav>';

			echo $args['after_widget'];
		}}
	}
	public function form( $instance ) {
		$title =  isset( $instance[ 'title' ] )? $instance[ 'title' ] : $this->titulo_padrao;
		$qtd_posts    = isset( $instance['qtd'] ) ? absint( $instance['qtd'] ) : 5;
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		<label for="<?php echo $this->get_field_id( 'qtd' ); ?>"><?php _e( 'Qtd de posts:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'qtd' ); ?>" name="<?php echo $this->get_field_name( 'qtd' ); ?>" type="number" step="1" min="1" value="<?php echo $qtd_posts; ?>" size="3" />
		</p>
		<?php
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : $old_instance['title'];
		$instance['qtd'] = ( ! empty( $new_instance['qtd'] ) ) ? absint( $new_instance['qtd'] ) : $old_instance['qtd'];

		return $instance;
	}
}

add_action( 'widgets_init', function(){
	register_widget( 'Relacionados_Widget' );
});
