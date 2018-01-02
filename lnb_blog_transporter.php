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

class lnbBlogTransporter {
    
    public function __construct() {
        add_action( 'admin_menu', array($this , 'lbt_menu_page') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_things') );
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
    
    //cURL Function 
    
    private function getHTML($url) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13");
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt( $ch, CURLOPT_REFERER, 'http://www.tastechase.com');
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        $content = curl_exec( $ch );
        return $content;
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
    
    private function stripHTML($html, $css_selector) {
        $selector_array = explode("," , trim($css_selector));
        foreach ($selector_array as $selectors) {
            foreach ($html->find($selectors) as $selector) {
                $selector->outertext = "";
                $html->load($html->save());
            }
        }
    }
    
    // Return URLs for All Images on Crawled Page
    private function getImages($html, $div, $remove, $url) {
        $this->stripHTML($html, $remove);
        $body = $html->getElementById($div);
       
       if ($body->find('img')) {
            foreach(@$body->find('img') as $element) {
                @$images .=  str_replace(" ", "%20" , $element->src) . "<br>" . " ";
            }
      
            return $images;
       }
    }
    
    // Return URLs for All Images on Crawled Page for Display on Back End
    private function getImagesForUpdatedDisplay($html, $div, $remove, $url) {
        $this->stripHTML($html, $remove);
        $body = $html->getElementById($div);
        if ($body->find('img')) {
            foreach($body->find('img') as $element) {
                $image_src = $this->cleanImageUrls($element->src, $url);
                @$image_for_updates .= site_url('/wp-content/uploads/') . pathinfo($image_src , PATHINFO_BASENAME) . "<br>";
            }
       
            return $image_for_updates;
        }
    }
    
    // Get the Page Title
    public function getPageTitle($html) {
        
        $page_title = $html->find('title',0);
        return $page_title->plaintext;
    }
    
    public function cleanImageUrls($img, $url) {
        $img_url = str_replace(" ", "%20" , $img);
        $img_url = str_replace("..", parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) , $img_url);
        $img_url = preg_replace('/\[.*\]/' , "" , $img_url);
        $img_url = preg_replace('/\?.*/', '', $img_url);

        
        return $img_url;
    }
    
    #### Does The Primary Parsing ####
    public function blogHTMLParser($html, $div, $remove, $url) {

        if (strlen($remove) > 1) {
            $this->stripHTML($html, $remove);
        }
        
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
    
        $body = $html->getElementById($div);
    
        //Error Handle Invalid Selector
        if (!isset($body) ) {
            wp_die('<h1>Invalid Selector.  <a href="http://www.tastechase.com/wp-admin/tools.php?page=blog-transporter-main"> Please Try Again </a></h1>');
            
        }
        
        //$url_parts = parse_url($url);
        
        // Rewrites IMG Source URLs to Destination Domain on Final Submit
        if ( isset($_POST["submit_final"] ) ) {
            foreach($body->find('img') as $element) {
                $image_src = $this->cleanImageUrls($element->src, $url);
                $element->src = site_url('/wp-content/uploads/') . pathinfo($image_src , PATHINFO_BASENAME);
                $element->srcset = null;
            }
        }
        
        // Rewrites IMG Source URLs to Destination Domain on Intial Submit but leaves srcset at original image URL so that they display on the preview.
        if ( isset($_POST["submit"] ) ) {
            foreach($body->find('img') as $element) {
                $image_src = $this->cleanImageUrls($element->src, $url);
                $element->srcset = $element->src;
                
                if (!parse_url($element->src, PHP_URL_SCHEME)) {
                    $element->srcset = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . parse_url(str_replace("..", "" ,$element->src), PHP_URL_PATH);
                } 
                
                $element->src = site_url('/wp-content/uploads/') . pathinfo($image_src , PATHINFO_BASENAME);
                
            }
        } 
        
        return $body;
        
        $body->clear();
        $html->clear();
    }
    
    public function imageFinagler ($images, $url, $post_id) {
        $image_array = explode(" " , $images);
        echo $images;
            foreach ($image_array as $image_links) {
                $src = str_replace("<br>" , "" , $this->cleanImageUrls($image_links, $url)); 
                
                if (null == parse_url($src, PHP_URL_HOST))  {
                    $file = str_replace("<br>" , "" , parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . $src);
                } elseif (!parse_url($src, PHP_URL_SCHEME)) {
                    $file = str_replace("<br>" , "" , parse_url($url, PHP_URL_SCHEME) . ":" . $src);
                }
                    
                $filename = basename($src);
                $file_upload = @file_get_contents($src);
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
                
            echo "<br /> <b> Image Processed - " . $src . "</b> <br />"   ;
            
            }
    }
    
    ##### Plugin Page and Form Submit Actions #####
    
    public function lbt_plugin_page() {
        
        ### Form Submit Actions ###
        
        // Initial Crawl Submit
        if ( isset($_POST["submit"]) ) {
            update_option( 'transporter_url' , $_POST["transporter_url"] ); 
            update_option( 'transporter_div' , $_POST["transporter_div"] );
            update_option( 'transporter_remove' , $_POST["transporter_remove"] );
            
            $div  = get_option('transporter_div');
                $elements_for_removal = get_option('transporter_remove');
            $url_array = preg_split('/\r\n|[\r\n]/', trim(get_option('transporter_url')));
            
            $crawl_headers = get_headers( $url_array[0], 1);
            if (strpos($crawl_headers[0], "200") == false) {
                wp_die("Response: " . $crawl_headers[0] . '<h1>Invalid URL. <a href="http://www.tastechase.com/wp-admin/tools.php?page=blog-transporter-main"> Please Try Again </a></h1>');
            }
          
            $string = $this->getHTML($url_array[0]);
            $html = @HtmlDomParser::str_get_html($string);
       
            
            $images = $this->getImages($html, $div, $elements_for_removal, $url_array[0]);
 
            $desination_images = $this->getImagesForUpdatedDisplay($html, $div, $elements_for_removal, $url_array[0]);
            $title = $this->getPageTitle($html);
            $crawl = $this->blogHTMLParser($html, $div, $elements_for_removal, $url_array[0]);
            
        } 
        
        
        // Create New Page Submit
        if ( isset($_POST["submit_final"])) {
            $url_array = preg_split('/\r\n|[\r\n]/', trim(get_option('transporter_url')));
            
            foreach ($url_array as $urls) {
                
                $string = $this->getHTML($urls);
                $html = @HtmlDomParser::str_get_html($string);
                $images = $this->getImages($html, get_option('transporter_div'), get_option('transporter_remove'), $urls);
                
                ob_start();
                echo $this->blogHTMLParser($html, get_option('transporter_div'), get_option('transporter_remove'), $urls);
                $post_body = ob_get_clean();
            
                if (isset($_POST["transporter_publish"])) {
                    $publish = "publish";
                } else {
                    $publish = "";
                }
            
                $page_title = $this->getPageTitle($html);
                $post_content = array(
                    'post_title' => $page_title,
                    'post_content' => $post_body,
                    'post_status' => $publish,
                        );
                $post_id = wp_insert_post( $post_content );
                
                
                if (isset($images)) {
                    $this->imageFinagler($images, $urls, $post_id);   
                } else {
                    echo "No Images";
                }
            
                echo " <br />  <b>Page Processed</b>" . " " .  $post_id . "<br /><br />";
                @$posts_created .= $post_id . "<br /> <br /> <br />" . "\n";

                // Execute Polite Crawl, If Enabled
                if (isset($_POST["transporter_polite"]) && $urls !== end($url_array) ) {
                    echo "<b>Polite Crawl Enabled: </b>Sleeping for 15 Seconds <br /><br />";
                    sleep(15);
                } 
                
            }
            
            delete_option('transporter_url');
        }
        
        
        ########################
        ### Plugin Page HTML ###
        #######################
        
        ?>
        
        <div class="lbt-main">
            <div class="lbt-main-form"
                <br /><br />
                <img class="plugin_logo" style="width: 450px" src="https://media-exp2.licdn.com/media/AAEAAQAAAAAAAAt5AAAAJDZmZjBjODAwLTFlNGItNDRlNy04NmFmLWYxNjhmZmNjNjdmMA.png">
                <h1>LNB Blog Transporter</h1>
                <h3><i>"Blog me up Scotty"</i></h3><br />
            
                <form id="main-form" method="post">
                    <span style="font-size: 16px">Blog URLs (Max 5)</span> &nbsp; &nbsp; <textarea class="main-inputs" type='textbox' name='transporter_url' required /><?php echo get_option('transporter_url'); ?></textarea><br /><br />
                    <span style="font-size: 16px">Content Div Selector</span> &nbsp; &nbsp; <input class="main-inputs" type='textbox' name='transporter_div' value="<?php echo get_option('transporter_div'); ?>" required /> <br /><br />
                    <span style="font-size: 16px">Elements to Remove(comma separated)</span> &nbsp; &nbsp; <input class="main-inputs" type='textbox' name='transporter_remove' value="<?php echo get_option('transporter_remove'); ?>"  /> <br /><br />
                    <?php if ( !isset($_POST["submit"])) { ?>
                        <input class="lbt-submit-button" type="submit" name="submit" value="Crawl"  />
                    <?php } else { ?>
                        <input class="lbt-submit-button" type="submit" name="submit" value="Try Again"  /><br /> <br /> <br /> 
                    <?php } ?>
                    
                </form> <br />
                
                <?php if ( isset($_POST["submit"])) { ?>
                    <form id="main-form" method="post">
                        <input type="hidden" name="transporter_title" value="<?php echo $title ?>" />
                        <br /><br /><span class="look-right" style="font-size: 18px"><b>If everything looks right, select options and click to Create </b></span><br />
                        <span style="font-size: 16px"><strong>Publish On Creation &nbsp; &nbsp;</strong></span><input type="checkbox" name="transporter_publish" /><br /><br />
                        <span style="font-size: 16px"><strong>Enable Polite Crawl &nbsp; &nbsp;</strong></span><input type="checkbox" name="transporter_polite" /><br /><br />
                        <span style="font-size: 16px"><strong>&nbsp; &nbsp;</strong></span><br /><br />
                        <div class="lbt-image-display"><span style="font-size: 16px"><b>Example Images URLs on Target:</b></span><br />
                        <?php echo $images ?><br /></div>
                        <div class="lbt-image-display"><span style="font-size: 16px"><b>Example Images URLs on Destination:</b></span><br />
                        <?php echo $desination_images ?></div><br />
                        <input class="lbt-submit-button" type="submit" name="submit_final" value="Create Posts" /><br />
                    </form>
                
                <?php } ?>
            </div>
        
            <div id="blog-contents">  
                <br />
                <h1 style="text">Preview:</h1>
                <h3 class="verify-page">Verify the Page Below</h3><br />
                
                <?php if ( isset($_POST["submit_final"])) { ?>
                    <h1 style="color: green; font-size: 40px;"> Posts Created - ID:  <?php echo $posts_created; ?><br /></h1>
                <?php } ?>
                
                <?php if ( isset($_POST["submit"])) {
                ?>
                <span style="font-size: 18px"> Page Title: <b><?php echo $title; ?></b> </span>  <br /><br />
                <div class="the-contents">
                    <span class="scraped-content"><?php echo $crawl ?></span><br /><br />
                </div>
            <?php } ?>
            </div>
        </div>
        
<?php

    }
    
}

$blog_transporter = new lnbBlogTransporter();