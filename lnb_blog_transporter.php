<?php 
/*
Plugin Name: LeadsNearby Blog Transporter
Plugin URI: http://www.leadsnearby.com
Description: Beam Me Up Scotty
Version: 0.1.0
Author: LeadsNearby
Author URI: http://www.leadsnearby.com
License: GPLv3
*/

require 'vendor/autoload.php';
use Sunra\PhpSimple\HtmlDomParser;

ini_set('LNB','Mozilla/4.0 (compatible; MSIE 6.0)');


class lnbBlogTransporter {
    
    public function __construct() {
        add_action( 'admin_menu', array($this , 'lbt_menu_page') );
        add_action('admin_enqueue_scripts', array($this, 'enqueue_things') );
    }

    // Add Scripts and Styles
    function enqueue_things() {
        wp_enqueue_style('lbt-styles', plugins_url('css/lbt-style.css', __FILE__));
    }

    // Add Menu Page
    function lbt_menu_page() {
        if (is_admin()) {
            add_management_page ( 
                'LNB Blog Transporter', 
                'LNB Blog Transporter', 
                'manage_options', 
                'blog-transporter-main', 
                array($this, 'lbt_plugin_page') 
            );
        }
    }
    
    // Strip Classes
    private function stripClasses($html, $css_selector) {
        foreach ($html->find($css_selector) as $selector) {
            $selector->class = null;
        }
        $html->load($html->save());
    }
    
    //Strip Ids
    private function stripIds($html, $css_selector) {
        foreach ($html->find($css_selector) as $selector) {
            $selector->id = null;
        }
        $html->load($html->save());
    }
    
    //Strip Inline Styles
    private function stripStyles($html, $css_selector) {
        foreach ($html->find($css_selector) as $selector) {
            $selector->style = null;
        }
        $html->load($html->save());
    }
    
    
    private function stripElements($html, $element) {
        $element_item = $html->find($element);
        foreach ($html->find($element) as $element_item) {
            $element_item->innertext = null;
            
        }
        $html->load($html->save());
    }
    
    private function stripDIV($url, $div, $element) {
        $page = $url;
        $html = HtmlDomParser::file_get_html( $page );
        $body = $html->getElementById($div);
        foreach ($body ->find($element) as $selector) {
            $selector = null;
        }
        //$body->load($body->save());
    }
    
    // Return URLs for All Images on Crawled Page
    private function getImages($url, $div) {
        $page = $url;
        $html = HtmlDomParser::file_get_html( $page );
        $body = $html->getElementById($div);
        
       foreach($body->find('img') as $element) {
            @$images .=  $element->src . "<br>" . " ";
       }
       
       return $images;
    }
    
    // Return URLs for All Images on Crawled Page for Display on Back End
    private function getImagesForUpdatedDisplay($url, $div) {
        $page = $url;
        $html = HtmlDomParser::file_get_html( $page );
        $body = $html->getElementById($div);
        
        foreach($body->find('img') as $element) {
            $image_src = $element->src;
            @$image_for_updates .= site_url('/wp-content/uploads/') . pathinfo($image_src , PATHINFO_BASENAME) . "<br>";
        }
       
       return $image_for_updates;
    }
    
    // Get the Page Title
    public function getPageTitle ($url) {
        $page = $url;
        $html = HtmlDomParser::file_get_html( $page );
        
        $page_title = $html->find('title',0);
        return $page_title->plaintext;
    }
    
    #### Does The Primary Parsing ####
    public function blogHTMLParser($url, $div) {
        
        $page = $url;
        $html = HtmlDomParser::file_get_html( $page );
    
        
        $this->stripClasses($html, 'ul');
        $this->stripClasses($html, 'li');
        $this->stripClasses($html, 'a');
        $this->stripClasses($html, 'span');
        $this->stripClasses($html, 'h1');
        $this->stripClasses($html, 'h2');
        $this->stripClasses($html, 'h3');
        $this->stripClasses($html, 'h4');
        $this->stripClasses($html, 'img');
        $this->stripClasses($html, 'p');
        
        $this->stripStyles($html, 'div');
        $this->stripStyles($html, 'ul');
        $this->stripStyles($html, 'li');
        $this->stripStyles($html, 'a');
        $this->stripStyles($html, 'span');
        $this->stripStyles($html, 'h1');
        $this->stripStyles($html, 'h2');
        $this->stripStyles($html, 'h3');
        $this->stripStyles($html, 'h4');
        $this->stripStyles($html, 'img');
        $this->stripStyles($html, 'p');
        

        $this->stripIds($html, 'ul');
        $this->stripIds($html, 'li');
        $this->stripIds($html, 'a');
        $this->stripIds($html, 'span');
        $this->stripIds($html, 'h1');
        $this->stripIds($html, 'h2');
        $this->stripIds($html, 'h3');
        $this->stripIds($html, 'h4');
        $this->stripIds($html, 'img');
        $this->stripIds($html, 'p');
        
        // if ( isset($_POST["transporter_strip"])) {
        //     $this->stripElements($html, $strip);
        // }
    
        $body = $html->getElementById($div);
        $url_parts = parse_url($url);
        
        // Rewrites IMG Source URLs to Destination Domain
        if ( isset($_POST["submit_final"])) {
            foreach($body->find('img') as $element) {
                $image_src = $element->src;
                $element->src = site_url('/wp-content/uploads/') . pathinfo($image_src , PATHINFO_BASENAME);
            }
        }
    
        return $body;
        
        $body->clear();
        $html->clear();
    }
    
    #####  Plugin Page and Form Submit Actions #####
    
    // Form Submit Actions
    public function lbt_plugin_page() {
        
        if ( isset($_POST["submit"]) ) {
           $url =  $_POST["transporter_url"];
           $div = $_POST["transporter_div"];
           $title = $this->getPageTitle($url);
           $images = $this->getImages($url, $div);
           $url_parts = parse_url($url);
           
        } 
        
        if ( isset($_POST["submit_final"])) {
            
            ob_start();
            $url = $_POST["transporter_url"];
            $div = $_POST["transporter_div"];
            $title = $_POST["transporter_title"];
            $images = $_POST["transporter_images"];
            $element_strip = $_POST["transporter_strip"];
            
            if (isset($_POST["transporter_publish"])) {
                $publish = "publish";
            } else {
                $publish = "";
            }
            
            echo $this->blogHTMLParser($url, $div);
            $post_body = ob_get_clean();
            
        
        $post_content = array(
            'post_title' => $title,
            'post_content' => $post_body,
            'post_status' => $publish
            );
            
        $post_id = wp_insert_post( $post_content );
        
        if (isset($_POST["transporter_images"])) {
                $image_array = explode(" " , $images);
                foreach ($image_array as $image_links) {
                    $file = str_replace("<br>" , "" , $image_links);
                    $headers = @get_headers($image_links);
                    if (@getimagesize($file) < 1)  {
                        $file = str_replace("<br>" , "" , parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . $image_links);
                    }
                    $filename = basename($file);
                    $file_upload = @file_get_contents($file);
                    $upload_file = wp_upload_bits($filename, null, $file_upload );
                    
                    if (!$upload_file['error']) {
	                    $wp_filetype = wp_check_filetype($filename, null );
	                    $attachment = array(
		                'post_mime_type' => $wp_filetype['type'],
		                'post_parent' => $post_id,
		                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
		                'post_content' => '',
		                'post_status' => 'inherit'
	                    );
	               
	                require_once( ABSPATH . 'wp-admin/includes/image.php' );     
	                $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $post_id );
	                $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
	                wp_update_attachment_metadata( $attachment_id, $attach_data );
	                
                }
                
            //echo $file;
            
            }
        }
    }
        
        ### Plugin Page HTML ###
        ?>
        
        <div class="lbt-main">
            <div class="lbt-main-form"
                <br /><br />
                <img class="plugin_logo" style="width: 450px" src="https://media-exp2.licdn.com/media/AAEAAQAAAAAAAAt5AAAAJDZmZjBjODAwLTFlNGItNDRlNy04NmFmLWYxNjhmZmNjNjdmMA.png">
                <h1>LNB Blog Transporter</h1>
                <h3><i>"Blog me up Scotty"</i></h3><br />
            
                <form id="main-form" method="post">
                    <span style="font-size: 16px">Blog URL</span> &nbsp; &nbsp; <input class="main-inputs" type='textbox' name='transporter_url' value="<?php if (isset($_POST["transporter_url"])) { echo $_POST["transporter_url"]; } ?>" required /> <br /><br />
                    <span style="font-size: 16px">Content Div Selector</span> &nbsp; &nbsp; <input class="main-inputs" type='textbox' name='transporter_div' value="<?php if (isset($_POST["transporter_url"])) { echo $_POST["transporter_div"]; } ?>"  /> <br /><br />
                    <?php if ( !isset($_POST["submit"])) { ?>
                        <input type="submit" name="submit" value="Crawl"  />
                    <?php } else { ?>
                        <input type="submit" name="submit" value="Try Again"  /><br /> <br /> <br /> 
                    <?php } ?>
                    

                </form> <br />
                
                <?php if ( isset($_POST["submit"])) { ?>
                    <form id="main-form" method="post">
                        <input type="hidden" name="transporter_url" value="<?php echo $url ?>" />
                        <input type="hidden" name="transporter_div" value="<?php echo $div ?>" />
                        <input type="hidden" name="transporter_title" value="<?php echo $title ?>" />
                        <input type="hidden" name="transporter_images" value="<?php echo $images ?>" />
                        <br /><br /><span class="look-right" style="font-size: 18px"><b>If this looks right, select options and click to Create ---></b></span><br />
                        <span style="font-size: 16px">Publish On Creation &nbsp; &nbsp;</span><input type="checkbox" name="transporter_publish" /><br /><br />
                        <span style="font-size: 16px">Ending Element to Strip</span> &nbsp; &nbsp; <input class="main-inputs" type='textbox' name='transporter_strip'  /> <br /><br />
                        <div class="lbt-image-display"><span style="font-size: 16px"><b>Images URLs on Target:</b></span><br />
                        <?php echo $images ?><br /></div>
                        <div class="lbt-image-display"><span style="font-size: 16px"><b>Images URLs on Destination:</b></span><br />
                        <?php echo $this->getImagesForUpdatedDisplay($url, $div); ?></div><br />
                        <input type="submit" name="submit_final" value="Create Post" /><br />
                    </form>
                
                <?php } ?>
            </div>
        
            <div id="blog-contents">  
                <br />
                <h1 style="text">Blog Contents:</h1>
                <h3 class="verify-page">Verify the Page Below</h3><br /><br />
                
                <?php if ( isset($_POST["submit_final"])) { ?>
                    <h1 style="color: green; font-size: 40px;"> Post Created - ID:  <?php echo $post_id; ?><br /><?php echo $_POST["transporter_strip"]; ?></h1>
                <?php } ?>
                
                <?php if ( isset($_POST["submit"])) {
                ?>
                <span style="font-size: 18px"> Page Title: <b><?php echo $title; ?></b> </span>  <br /><br /><br />
                <div class="the-contents">
                    <span class="scraped-content"><?php echo $this->blogHTMLParser($url, $div); ?></span><br /><br />
                    <?php print_r( $url_parts); ?>
                </div>
            <?php } ?>
            </div>
        </div>
        
<?php
        
        

    }
    
}


$blog_transporter = new lnbBlogTransporter();