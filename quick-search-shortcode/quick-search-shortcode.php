<?php
/**
 * Plugin Name: Quick Search Shortcode
 * Description: How to use AJAX from a shortcode handler named <code>[quick_search_vehicle]</code>.
 */

add_action( 'wp_loaded', array ( 'Quick_Search_Shortcode', 'get_instance' ) );

class Quick_Search_Shortcode
{
    /**
     * Current plugin instance
     *
     * @type NULL|object
     */
    protected static $instance = NULL;

    /**
     * Unique action name to trigger our callback
     *
     * @type string
     */
    protected $ajax_action = 'load_demo_data';

    /**
     * CSS class for the shortcode, reused as JavaScript handle.
     *
     * Must be unique too.
     *
     * @type string
     */
    protected $shortcode_class = 'ajaxdemo';

    /**
     * Remeber if we had regsitered a script on a page already.
     *
     * @type boolean
     */
    protected $script_registered = FALSE;

    /**
     * Create a new instance.
     *
     * @wp-hook wp_loaded
     * @return  object $this
     */
    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    /**
     * Constructor. Register shortcode and AJAX callback handlers.
     */
    public function __construct()
    {
        add_shortcode( 'quick_search_vehicle', array ( $this, 'shortcode_handler' ) );

        // register the AJAX callback
        $callback = array ( $this, 'ajax_callback' );
        // user who are logged in
        add_action( "wp_ajax_$this->ajax_action", $callback );
        // anonymous users
        add_action( "wp_ajax_nopriv_$this->ajax_action", $callback );
    }


    /**
     * Render the shortcode.
     */
    public function shortcode_handler()
    {
        $this->register_scripts();
       
        if ( isset( $_POST['qs'] )) { 
            // echo $_POST['brand'];
            if($_POST['myear']) $woo_cat_id = (int) $_POST['myear'];
            else if($_POST['model']) $woo_cat_id = (int) $_POST['model'];
            else $woo_cat_id = (int) $_POST['brand'];

            $link = get_term_link( $woo_cat_id, 'product_cat' );
            ?>
            <script type="text/javascript">window.location.href = "<?php echo $link;?>";</script>         
        <? }
        ?>
        <style type="text/css">
            .qs-list-block li{
                list-style: none; 
                display: inline-block;
                padding: 1%;               
            }
            .qs-list-block li .fancy-select-wrap{
                width: 14em;
            }
            .qs-list-block label{
                display: inline-block;
            }
            .qs-list-block input[type=submit]{
                padding: 8px 25px !important;
                margin: 0 0 10px;
            }
        </style>
        <form method="post">
            <ul class="qs-list-block">
                <li><label>BRAND</label> </li>
                <li><?=$this->get_brands()?></li> 
                <li><label>MODEL</label> </li>
                <li class="<?=$this->shortcode_class?>"><?=$this->select_model()?></li>       
                <li><label>YEAR</label></li>
                <li class="<?=$this->shortcode_class?>year"><?=$this->select_year()?></li>                    
                <li><input type="submit" name="qs" class="nectar-button large regular accent-color regular-button" value="SEARCH"/>
               </li>
            </ul>
        </form>
    <?php }

    public function select_model(){
       
        $opt = '<select name="model" class="'.$this->shortcode_class.'model">';
        $opt .= '<option value="">--All Models--</option>';

        $amodels = json_decode($this->get_children());        
        if(count($amodels) > 0){
            foreach ($amodels as $key => $sub) {
                $opt .= '<option value="'.$sub->term_id.'">'.$sub->name.'</option>';  
            }
        }
        $opt .= '</select>';

        return $opt;
    }

    public function select_year(){
       
        $opt = '<select name="myear">';
        $opt .= '<option value="">--All Years--</option>';

        $years = json_decode($this->get_children());        
        if(count($years) > 0){
            foreach ($years as $key => $sub) {
                $opt .= '<option value="'.$sub->term_id.'">'.$sub->name.'</option>';  
            }
        }
        $opt .= '</select>';

        return $opt;
    }

	public function get_brands(){

        $orderby = 'name';
        $order = 'asc';
        $hide_empty = false ;
        $cat_args = array(
            'orderby'    => $orderby,
            'order'      => $order,
            'hide_empty' => $hide_empty,
            'parent'     => 0
        );
     
        $product_categories = get_terms( 'product_cat', $cat_args );
	   
	    $html = '';

	    if( !empty($product_categories) ){
	        $html .='<select name="brand" class="'.$this->shortcode_class.'brand" required>';
            $html .= '<option value="">--Brands--</option>';

	        foreach ($product_categories as $key => $category) {
	            $html .='<option value="'.$category->term_id.'">'.$category->name.'</option>';            
	        }

	        $html .='</select>';
	    }
	    return $html;
	}

    /**
     * Return AJAX result.
     *
     * Must 'echo' and 'die'.
     *
     * @wp-hook wp_ajax_$this->ajax_action
     * @wp-hook wp_ajax_nopriv_$this->ajax_action
     * @return int
     */
    public function ajax_callback()
    {
        echo $this->get_children();
        exit;
    }

    /**
     * Random number.
     *
     * @return int
     */
    protected function get_children()
    {   
        $parentId = 0;
        if(isset($_POST['brandid']) && $_POST['brandid']){
            $parentId = $_POST['brandid'];
        }
        else if(isset($_POST['modelid']) && $_POST['modelid']){
            $parentId = $_POST['modelid'];
        }

        $args = array(
           'hierarchical' => 1,
           'show_option_none' => '',
           'hide_empty' => 0,
           'parent' => $parentId,
           'taxonomy' => 'product_cat'
        );

	    $subcats = get_categories($args);

		$returnarr = [];
		foreach ($subcats as $key => $value) {
			if($value->parent == $parentId && $parentId <> 0){
				$returnarr[] = $value;
			}
		}

	    // encode the $return_array as a JSON object and echo it        
	    return json_encode($returnarr);    
    }

    /**
     * Register script and global data object.
     *
     * The data will be printent before the linked script.
     */
    protected function register_scripts()
    {
        if ( $this->script_registered )
            return;

        $this->script_registered = TRUE;

       // add_action( 'wp_footer', 'misha_custom_internal_css' );
 

        wp_register_script(
            // unique handle
            $this->shortcode_class,
            // script URL
            plugin_dir_url( __FILE__ ) . '/model-populate-ajax.js',
            // dependencies
            array ( 'jquery'),
            // version
            'v1',
            // print in footer
            TRUE
        );

        wp_enqueue_script( $this->shortcode_class );

        $data = array (
            // URL address for AJAX request
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            // action to trigger our callback
            'action'    => $this->ajax_action,
            // selector for jQuery
            'democlass' => $this->shortcode_class
        );

        wp_localize_script( $this->shortcode_class, 'AjaxDemo', $data );
    }
}