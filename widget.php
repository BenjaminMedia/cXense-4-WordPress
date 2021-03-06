<?php

class Cxense_Widget extends WP_Widget {

    public function __construct(){
        parent::__construct(false, 'Cxense_widget');
    }

    function update ($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['widget_id'] = $new_instance['widget_id'];
        $instance['widget_title'] = $new_instance['widget_title'];
        return $instance;
    }

    function form ($instance) {
        $defaults = array('widget_id'=> '', 'widget_title'=> '');
        $instance = wp_parse_args( (array) $instance, $defaults ); ?>
        <p>
            <label for="<?php echo $this->get_field_id('widget_title'); ?>">Widget Title:</label>
            <input type="text" name="<?php echo $this->get_field_name('widget_title') ?>" id="<?php echo $this->get_field_id('widget_title') ?> " value="<?php echo $instance['widget_title'] ?>" size="20">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('widget_id'); ?>">Widget ID:</label>
            <input type="text" name="<?php echo $this->get_field_name('widget_id') ?>" id="<?php echo $this->get_field_id('widget_id') ?> " value="<?php echo $instance['widget_id'] ?>" size="20">
        </p>

    <?php
    }



    function widget($args, $instance) {
        /**
         * Global variables results in code error in PHPStorm
         * if you don't declare them like this
         * @var string $before_widget
         * @var string $after_widget
         * @var string $before_title
         * @var string $after_title
         */
        //Uncomment this if you want to enqueue a style for your widget.
        //wp_enqueue_style('cxense_style',plugin_dir_url(__FILE__)."css/style.css");
        extract($args);
        $title = isset($instance['widget_title']) ? $instance['widget_title'] : '';

        ?>

        <ul class="nav nav--stacked" id="cxenseWidget">
            <?php
            $data = json_decode($this->getCxenseContent(isset($instance['widget_id']) ? $instance['widget_id'] : ''),true);

            foreach ($data['items'] as $item) {
                ?>
                <li>
                    <article class="article-promo article-promo--separator push--bottom soft--bottom">
                    <a href="<?php echo isset($item['click_url']) ? $item['click_url'] : '' ?>">
                        <div class="media__body">
                            <h4 class="article-promo__title"><?php echo isset($item['title']) ? $item['title'] : '' ?></h4>
                            <span><?php echo isset($item['description']) ? $item['description'] : ''?></span>
                        </div>
                    </a>
                    </article>
                </li>
                <?php
            }
            ?>
        </ul>

        <?php
    }

    private function getCxenseContent($widgetID){

        $url = 'http://api.cxense.com/public/widget/data';
        if(get_transient("cxense_widget_".$widgetID) == false){
            $request = array( 'widgetId' => $widgetID);
            $request = json_encode($request);
            $response = wp_remote_post( $url, array(
                    'timeout' => 45,
                    'headers' => array('Content-Type' => 'application/json; charset=utf-8', 'Content-Length' => strlen($request)),
                    'body' => $request
                )
            );
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                error_log( "Something went wrong trying to get cxense data: $error_message");
            } else {
                set_transient("cxense_widget_".$widgetID,wp_remote_retrieve_body($response) , 1 *HOUR_IN_SECONDS );
                return wp_remote_retrieve_body($response);
            }
        } else {
            return get_transient("cxense_widget_".$widgetID);
        }
        return null;
    }
}
