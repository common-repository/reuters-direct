<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(ABSPATH . '/wp-admin/includes/post.php');
require_once(ABSPATH . '/wp-admin/includes/taxonomy.php');
require_once(ABSPATH . '/wp-admin/includes/import.php');
require_once(ABSPATH . '/wp-admin/includes/image.php');

ini_set('max_execution_time', 1800);
register_shutdown_function('shutdown');

function shutdown() {
	if (connection_status() == CONNECTION_TIMEOUT) {
		$logger = new Katzgrau\KLogger\Logger(__DIR__.'/logs', Psr\Log\LogLevel::DEBUG, array ('dateFormat' => 'Y-m-d G:i:s'));
		$lockfile = __DIR__. '/cron.lock';
		if(file_exists($lockfile)){
			unlink($lockfile);
			$logger->error("Cron job timeout");
		}
	}
}

class Reuters_Direct {

	private static $_instance = null;
	public $settings = null;
	public $version;
	public $token;
	public $file;
	public $dir;
	private $logfile;
	private $user_token;
	private $user_id;
	private $logger;
	private $lockfile;

	//	User setting variables
	private $stored_channel;
	private $stored_category;
	private $stored_taxonomy;
	private $stored_type;
	private $stored_status;
	private $stored_rendition;
	private $stored_author;

	//	Content ingestion variables
	private $channel_name;
	private $channel_alias;
	private $channel_type;
	private $channel_categories;
	private $olr_rendition;
	private $other_rendition;
	private $image_post = false;


	// CONSTRUCTOR FUNCTION
	public function __construct ( $file = '', $version = '' ) {
		$this->version = $version;
		$this->token = 'Reuters_Direct';
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->lockfile = __DIR__. '/cron.lock';

		register_activation_hook($this->file, array($this, 'install'));
		register_activation_hook($this->file, array($this, 'activate'));
		register_deactivation_hook($this->file, array($this, 'deactivate'));

		// Add filters
		add_filter('cron_schedules', array($this, 'customSchedules'));

		// Add cron actions
		add_action('rd_fetch', array($this, 'import'));
		add_action('rd_ping', array($this, 'ping'));

        // Add KLogger class
        $logger = new Katzgrau\KLogger\Logger(__DIR__.'/logs', Psr\Log\LogLevel::DEBUG, array ('dateFormat' => 'Y-m-d G:i:s'));
		$this->logger = $logger;
	}

	// MAIN INSTANCE
	public static function instance ( $file = '', $version = '' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	}

	// FUNCTION TO ADD VERSION
	public function install () {
		update_option( $this->token . '_version', $this->version );
	}

	// FUNCTION FOR CUSTOM CRON INTERVAL
	public function customSchedules($schedules) {
        $schedules['every_five_minutes'] = array(
            'interval' => 5*60,
            'display' => __('Every 5 minutes'));
        $schedules['every_fifteen_minutes'] = array(
            'interval' => 15*60,
            'display' => __('Every 15 minutes'));
	    return $schedules;
	}

	// FUNCTION TO ACTIVATE PLUGIN
	public function activate() {
		$this->logger->info("Plug-in activated");
		// Creating upload directory
		$upload_dir = wp_upload_dir();
  		$upload_loc = $upload_dir['basedir']."/Reuters_Direct_Media";
  		if (!is_dir($upload_loc)) {
  			$this->logger->notice("Creating local directory for media download");
    		wp_mkdir_p($upload_loc);
    	}

		// Adding fetching cron job
		if (!wp_next_scheduled('rd_fetch')) {
			wp_schedule_event(time(), 'every_fifteen_minutes', 'rd_fetch');
		}

		// Adding pinging cron job
		if (!wp_next_scheduled('rd_ping')) {
			wp_schedule_event(time(), 'every_five_minutes', 'rd_ping');
		}
	}

	// FUNCTION TO DEACTIVATE PLUGIN
	public function deactivate() {
		// Removing cron job
		wp_clear_scheduled_hook('rd_fetch');
		wp_clear_scheduled_hook('rd_ping');

		// Removing lock file
		if(file_exists($this->lockfile)){
			unlink($this->lockfile);
		}

		// Deleteing options
		delete_option('rd_username_field');
		delete_option('rd_password_field');
		delete_option('rd_channel_checkboxes');
		delete_option('rd_category_checkboxes');
		delete_option('rd_taxonomy_radiobuttons');
		delete_option('rd_type_radiobuttons');
		delete_option('rd_status_radiobuttons');
		delete_option('rd_image_radiobuttons');
		delete_option('rd_author_radiobuttons');
		delete_option('rd_cronI_radiobuttons');
		delete_option('rd_purgeI_radiobuttons');
		$this->logger->info("Plug-in deactivated");
	}

	// FUNCTION TO PING wp-cron.php
	public function ping(){
		$site_address = home_url() . '/wp-cron.php';
		sleep(300);
		wp_remote_get($site_address, array('timeout' => 10));
	}

	// FUNCTION TO FETCH CONTENT
	public function import() {
		$this->clearLogs();
		$this->rd_purge();
		if(!get_option('rd_username_field')){
			$this->logger->error("User not logged in");
			return;
		}
		if(!file_exists($this->lockfile)){
			$this->logger->info("Cron job started");
			fopen($this->lockfile, 'w');
			$this->user_token = $this->getToken();
			$this->user_id = $this->getUserId();
			if($this->user_token!="")
			{
				if(!get_option('rd_channel_checkboxes')){
					$this->logger->error("No channels selected");
				}
				else{
					$this->getPosts();
				}
			}
			$this->logger->info("Cron job complete");
			if(file_exists($this->lockfile))
				unlink($this->lockfile);
		}
		else{
			$this->logger->notice("Cron job skipped");
		}
	}

	// FUNCTION TO GET TOKEN
	public function getToken(){
		$token = '';
		$username = get_option('rd_username_field');
		$password = get_option('rd_password_field');
	  	$token_url = "https://commerce.reuters.com/rmd/rest/xml/login?username=$username&password=$password";
	  	$response = wp_remote_get($token_url, array('timeout' => 10, 'sslverify'   => false));

	  	if (!is_wp_error($response)){
		   $response_xml = simplexml_load_string(wp_remote_retrieve_body($response));
		   if(!$response_xml->error)
		   		$token = $response_xml;
		}
		else{
		   $this->logger->error($response->get_error_message());
		}
	  	return $token;
	}

	// FUNCTION TO CLEAR LOG FILES
	public function clearLogs(){
		// Clearing log files
		$log_dir = (__DIR__.'/logs');
		$logs = opendir($log_dir);
		while (($log = readdir($logs)) !== false)
		{
			if ($log == '.' || $log == '..')
				continue;

			if (filectime($log_dir.'/'.$log) <= time() - 7 * 24 * 60 * 60){
				unlink($log_dir.'/'.$log);
				$this->logger->notice("$log file removed");
			}
		}
		closedir($logs);

		// Clearing lock files
		if(file_exists($this->lockfile)){
			if (filectime($this->lockfile) <= time() - 15){
				unlink($this->lockfile);
				$this->logger->notice("Lock file removed");
			}
		}
	}

	// FUNCTION TO GET USER ID
	public function getUserId(){
		$this->stored_author = get_option('rd_author_radiobuttons');
		if($this->stored_author == 'Reuters'){
			$user_name = 'Reuters';
			$user_email = 'liaison@thomsonreuters.com';
			$user_id = username_exists('Reuters');
			if(!$user_id && !email_exists($user_email)){
				$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$user_id = wp_create_user( $user_name, $random_password, $user_email);
			}
			return $user_id;
		}
		else{
			$user_id = 1;
			return $user_id;
		}
	}

	// FUNCTION TO GET XML
	public function getXml($content_url){
		$content_xml = '';
	  	$response = wp_remote_get($content_url, array('timeout' => 10));

	  	if (!is_wp_error($response)){
		   $content_xml = simplexml_load_string(wp_remote_retrieve_body($response));
		}
		else{
		   $this->logger->error($response->get_error_message());
		}
  		return $content_xml;
	}

	// FUNCTION TO CREATE DIRECTORY
	public function createDirectory(){
		$upload_dir = wp_upload_dir();
		$upload_loc = $upload_dir['basedir']."/Reuters_Direct_Media/".$this->channel_name;
  		if (!is_dir($upload_loc)) {
  			$this->logger->notice("Creating local directory for ".$this->channel_name);
    		wp_mkdir_p($upload_loc);
    	}
    }

    // FUNCTION TO CHECK IF STORY ID ALREADY EXISTS
	public function storyIdExists($story_id) {
		global $wpdb;
		$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='story_id'";
		$args = array();

		if ( !empty ( $story_id ) ) {
		     $query .= " AND meta_value LIKE '%s' ";
		     $args[] = $story_id;
		}

		if ( !empty ( $args ) )
		     return $wpdb->get_var( $wpdb->prepare($query, $args) );

		return 0;
	}

	// FUNCTION TO CHECK IF ATTACHMENT ALREADY EXISTS
	public function getAttachId($attachment_url) {
	 	global $wpdb;
		$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_wp_attached_file'";
		$args = array();

		if ( !empty ( $attachment_url ) ) {
		     $query .= " AND meta_value LIKE '%s' ";
		     $args[] = $attachment_url;
		}

		if ( !empty ( $args ) )
		     return $wpdb->get_var( $wpdb->prepare($query, $args) );

		return 0;
	}

	// FUNCTION TO RETURN PREVIOUS RENDITION
	public function getPrevRend($rendition){
		$previous_rendition = array("rend:thumbnail"=>"rend:thumbnail", "rend:viewImage"=>"rend:thumbnail", "rend:baseImage"=>"rend:viewImage", "rend:filedImage"=>"rend:baseImage");
		return $previous_rendition[$rendition];
	}

	// FUNCTION TO HANDLE DIFFERNT CHANNEL TYPES
	public function getPosts() {
		global $wpdb;
		$index = 0;
		$this->stored_channel = get_option('rd_channel_checkboxes');
		$this->stored_category = get_option('rd_category_checkboxes');
		$this->stored_taxonomy = get_option('rd_taxonomy_radiobuttons');
		$this->stored_type = get_option('rd_type_radiobuttons');
		$this->stored_status = get_option('rd_status_radiobuttons');
		$this->stored_rendition = get_option('rd_image_radiobuttons');

		$this->olr_rendition = $this->stored_rendition;
		$this->other_rendition = $this->stored_rendition;
		if($this->stored_rendition == 'rend:filedImage'){
			$this->other_rendition = 'rend:baseImage';
		}

		if($this->stored_status == 'publish images'){
			$this->stored_status = 'publish';
			$this->image_post = true;
		}

		foreach( $this->stored_channel as $channel => $detail )
		{
			$channel_detail = explode(':', $detail);
			$this->channel_alias = $channel_detail[0];
			$this->channel_type = $channel_detail[1];
			$this->channel_name = str_replace(' ', '', $channel_detail[2]);
			$this->channel_categories = array();
			if(!empty($channel_detail[3]))
				$this->channel_categories = explode(',', $channel_detail[3]);

			if($this->channel_type == 'OLR')
			{
				$this->createDirectory();
				$content_url = 'http://rmb.reuters.com/rmd/rest/xml/packages?channel='.$this->channel_alias.'&limit=20&token='.$this->user_token;
				$content_xml = $this->getXml($content_url);
				if(!empty($content_xml))
					$this->getOLR($content_xml);
			}
			else if($this->channel_type == 'TXT')
			{
				$content_url = 'http://rmb.reuters.com/rmd/rest/xml/items?channel='.$this->channel_alias.'&limit=20&token='.$this->user_token;
				$content_xml = $this->getXml($content_url);
				if(!empty($content_xml))
					$this->getTexts($content_xml);
			}
			else if(($this->channel_type == 'PIC')||($this->channel_type == 'GRA'))
			{
				$this->createDirectory();
				$content_url = 'http://rmb.reuters.com/rmd/rest/xml/items?channel='.$this->channel_alias.'&limit=10&token='.$this->user_token;
				$content_xml = $this->getXml($content_url);
				if(!empty($content_xml))
					$this->getImages($content_xml);
			}
		}
	}

	// FUNCTION TO GET OLR
	public function getOLR($content_xml){
		$newpost = 0;
		$oldpost = 0;
		foreach ($content_xml->result as $item)
		{
			$story_id = sanitize_title((string) $item->guid);
			$pubDate = (string) $item->dateCreated;
			$post_date_unix = strtotime($pubDate);
			$post = $this->getOLRArray($item, $post_date_unix);

			// Handling existing story
			if ($post_id = $this->storyIdExists($story_id))
			{
				$latest_timestamp = get_post_meta( $post_id, 'unix_timestamp', true );
				if($post_date_unix > $latest_timestamp)
				{
					// Updating the post contents
					$image_content = $post['image_content'];
					$post['ID'] = $post_id ;
					$post['post_status'] = get_post_status($post_id);

					// Update Post with Images
					if(count($image_content)>=1)
					{
						$image_tag = $this->addImages($post_id, $image_content);
						$post['post_content'] = $post['post_content'].$image_tag;
						wp_update_post($post);
						wp_set_post_tags( $post_id, '', false );
					}
					// Update Post without Images
					else if (count($image_content)==0 && $this->image_post)
					{
						$post['post_status'] = 'draft';
						wp_update_post($post);
					}
					else
					{
						wp_update_post($post);
					}
					wp_set_post_tags( $post_id, 'Updated', true );
					update_post_meta($post_id, 'unix_timestamp', $post_date_unix);
					$oldpost++;
				}

			}
			// Handling new story
			else
			{
				//Getting post content
				$categories = $post['categories'];
				$image_content = $post['image_content'];

				// Posting the post contents
				$post_id = wp_insert_post($post);
				if ( is_wp_error( $post_id ) )
					return $post_id;
				if (!$post_id)
					return;

				// Post with Images
				if(count($image_content)>=1)
				{
					$image_tag = $this->addImages($post_id, $image_content);
					$post['post_content'] = $post['post_content'].$image_tag;
					$post['ID'] = $post_id ;
					wp_update_post($post);
				}
				//Post without Images
				else if (count($image_content)==0 && $this->image_post)
				{
					$post['ID'] = $post_id ;
					$post['post_status'] = 'draft';
					wp_update_post($post);
					wp_set_post_tags( $post_id, 'Reuters OLR - no image', false );
				}

				if(count($categories) > 0) {
					wp_set_post_terms($post_id, $categories, $this->stored_taxonomy);
				}

				add_post_meta($post_id, 'story_id', $story_id);
				add_post_meta($post_id, 'unix_timestamp', $post_date_unix);
				$newpost++;
			}
		}
		$this->logger->info("Reuters OLR: ".$this->channel_name);
		$this->logger->notice($newpost." New & ".$oldpost." Updated");
    }

   // FUNCTION TO GET OLR ARRAY
    public function getOLRArray($item, $post_date_unix){
		$post_title = (string) $item->headline;
    	$post_name = implode(' ', array_slice(explode(' ', $post_title), 0, 4));
		$post_date_gmt = gmdate('Y-m-d H:i:s', $post_date_unix);
		$post_date = get_date_from_gmt( $post_date_gmt );
		$post_author = $this->user_id;
		$categories = array();
		$image_content = array();
		$text_content = "";
		$post_type = $this->stored_type;
		$post_status = $this->stored_status;

		foreach($item->mainLinks->link as $links)
		{
			$mediaType = (string) $links->mediaType;
			$id = (string) $links->id;
			$item_url = 'http://rmb.reuters.com/rmd/rest/xml/item?id='.$id.'&channel='.$this->channel_alias.'&token='.$this->user_token;
			$item_xml = $this->getXml($item_url);
			if(empty($item_xml))
				continue;

			if($mediaType == "T")
			{
				$text_content = $item_xml->itemSet->newsItem->contentSet->inlineXML->html->body->asXML();
				$categories = $this->getCategories($item_xml);
			}
			else if($mediaType == "P")
			{
				$image_ref = "";
				$image_detail = array();
				$image_detail['headline'] = (string) $item_xml->itemSet->newsItem->contentMeta->headline;
				$image_detail['description'] = (string) $item_xml->itemSet->newsItem->contentMeta->description;
				$prev_rendition = $this->getPrevRend($this->olr_rendition);

				foreach($item_xml->itemSet->newsItem->contentSet->remoteContent as $remoteContent)
				{
					$image_type = (string) $remoteContent->attributes()->rendition;
					if(($image_type == $prev_rendition)||($image_type == $this->olr_rendition))
					{
						$image_ref = (string) $remoteContent->attributes()->href;
						$image_detail['url'] = $image_ref.'?token='.$this->user_token;
					}
				}
				$image_content[$image_ref] = $image_detail;
			}
		}
		$post_content = $text_content;
		$post = compact('post_name', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'image_content', 'post_type', 'categories');
    	return $post;
    }

    // FUNCTION TO GET CATEGORIES
    public function getCategories($item_xml) {
    	$categories = array();
    	$categories_id = array();
    	if($this->stored_category) {
    		// Category Codes: SUBJ, N2, MCC, MCCL, RIC, A1312
	    	foreach($item_xml->itemSet->newsItem->contentMeta->subject as $subject) {
				$category_code = (string) $subject->attributes()->qcode;
				if(empty($category_code))
					continue;
				list($type, $code) = explode(':', $category_code);
				if(in_array( $type, $this->stored_category )){
					array_push($categories, $category_code);
				}
			}
			// Cagtegory Code: Agency_Labels
			if(in_array('Agency_Labels', $this->stored_category)) {
				foreach($item_xml->itemSet->newsItem->itemMeta->memberOf as $memberOf) {
					$category_code = (string) $memberOf->name;
					array_push($categories, $category_code);
				}
			}
			// Category Code: User_Defined
			if(in_array('User_Defined', $this->stored_category)) {
				$categories = array_merge($categories, $this->channel_categories);
			}
    	}
    	// Find or Create Categories
    	foreach($categories as $category) {
    		$term = null;
    		if(intval($category) > 0) {
    			$term = term_exists(intval($category), $this->stored_taxonomy);
    			if($term == null)
    				continue;
    		}
    		else {
    			$term = term_exists(intval($category), $this->stored_taxonomy);
    			if($term == null) {
    				$term = wp_create_term($category, $this->stored_taxonomy);
    			}
    		}
    		if($term != null) {
    			array_push($categories_id, $term['term_id']);
    		}
    	}
		return $categories_id;
    }

    // FUNCITON TO GET OLR IMAGES
	public function addImages($post_id, $image_content) {
		$upload_dir = wp_upload_dir();
		$image_count = 0;
		$image_tag = '';
		foreach($image_content as $image_ref => $image_detail)
		{
			$basename = basename($image_ref);
			$filename = sanitize_file_name($basename);
			$file_id = "Reuters_Direct_Media/" . $this->channel_name ."/". $filename . '.jpg';
			$file = $upload_dir['basedir'].'/'.$file_id;
			$attach_id = '';
			// Handling New Image
			if (!file_exists($file))
			{
				$image_data = '';
				$image_url = $image_detail['url'];
				$headline = $image_detail['headline'];
				$description = $image_detail['description'];
			  	$response = wp_remote_get($image_url, array('timeout' => 10));

			  	if (!is_wp_error($response)){
				   $image_data = wp_remote_retrieve_body($response);
				}
				else{
				   $this->logger->error($response->get_error_message());
				}

				$image_content = file_put_contents($file, $image_data);
				if($image_content === false){
					$this->logger->error($filename . ' download failed');
					continue;
				}
				// Making a post entry
				$attachment = array(
				    'post_mime_type' => 'image/jpg',
				    'post_author' => $this->user_id,
				    'post_title' => implode(' ', array_slice(explode(' ', $headline), 0, 10)),
				    'post_content' => $description,
				    'post_excerpt' => $headline,
				    'guid' => $upload_dir['basedir']."/Reuters_Direct_Media/" . $this->channel_name ."/". $filename . '.jpg',
				    'post_status' => 'inherit'
				);
				// Attaching Images to Post
				$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
				wp_update_attachment_metadata( $attach_id, $attach_data );
			}
			// Handling Old Image
			else
			{
				$attach_id = $this->getAttachId($file_id);
			}
			// Handling Multiple Images
			$url = wp_get_attachment_url( $attach_id );
			$attach_link = get_attachment_link( $attach_id );
			$image_tag .= '<p><a href="'.$attach_link.'"><img src="'.$url.'" alt="'.$filename.'"></a></p>';
			// Setting Featured Image
			if($image_count == 0)
				set_post_thumbnail( $post_id, $attach_id );
			$image_count++;
		}
		return $image_tag;
	}

    // FUNCTION TO GET PIC
	public function getImages($content_xml){
		$newpost = 0;
		$upload_dir = wp_upload_dir();
		$image_rendition = $this->other_rendition;
		// GRA format correction
		if(($this->other_rendition =="rend:baseImage")&&($this->channel_type =="GRA")){
			$image_rendition = "rend:viewImage";
		}

		foreach ($content_xml->result as $item)
		{
			$story_id = sanitize_title((string) $item->guid);
			if (!$this->storyIdExists($story_id))
			{
				$id = (string) $item->id;
				$item_url = 'http://rmb.reuters.com/rmd/rest/xml/item?id='.$id.'&channel='.$this->channel_alias.'&token='.$this->user_token;
				$item_xml = $this->getXml($item_url);
				if(empty($item_xml))
					continue;

				$headline = (string) $item_xml->itemSet->newsItem->contentMeta->headline;
				$description = (string) $item_xml->itemSet->newsItem->contentMeta->description;

				foreach($item_xml->itemSet->newsItem->contentSet->remoteContent as $remoteContent)
				{
					$image_type = (string) $remoteContent->attributes()->rendition;
					if($image_type == $image_rendition)
					{
						$image_data = '';
						$image_ref = (string) $remoteContent->attributes()->href;
						$image_url = $image_ref.'?token='.$this->user_token;
					  	$response = wp_remote_get($image_url, array('timeout' => 10));

					  	if (!is_wp_error($response)){
						   $image_data = wp_remote_retrieve_body($response);
						}
						else{
						   $this->logger->error($response->get_error_message());
						}
					  	// Saving the images
						$basename = basename($image_ref);
						$filename = sanitize_file_name($basename);
						$file = $upload_dir['basedir']."/Reuters_Direct_Media/" . $this->channel_name ."/". $filename . '.jpg';
						$image_content = file_put_contents($file, $image_data);
						if($image_content === false){
							$this->logger->error($filename . ' download failed');
							continue;
						}
						// Making a post entry
						$attachment = array(
					    'post_mime_type' => 'image/jpg',
					    'post_author' => $this->user_id,
					    'post_title' => implode(' ', array_slice(explode(' ', $headline), 0, 10)),
					    'post_content' => $description,
					    'post_excerpt' => $headline,
					    'guid' => $upload_dir['basedir']."/Reuters_Direct_Media/" . $this->channel_name ."/". $filename . '.jpg',
					    'post_status' => 'inherit'
						);
						$attach_id = wp_insert_attachment( $attachment, $file);
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
						wp_update_attachment_metadata( $attach_id, $attach_data );
						add_post_meta($attach_id, 'story_id', $story_id);
						$newpost++;
					}
				}
			}
		}
		$this->logger->info("Reuters ".$this->channel_type.": ".$this->channel_name);
		$this->logger->notice($newpost." New");
    }

    // FUNCTION TO GET TXT
	public function getTexts($content_xml){
		$newpost = 0;
		$oldpost = 0;
		foreach ($content_xml->result as $item)
		{
			$story_id = sanitize_title((string) $item->guid);
			$pubDate = (string) $item->dateCreated;
			$post_date_unix = strtotime($pubDate);
			$post = $this->getTXTArray($item, $post_date_unix);
			// Handling existing story
			if ($post_id = $this->storyIdExists($story_id))
			{
				$latest_timestamp = get_post_meta( $post_id, 'unix_timestamp', true );
				if($post_date_unix > $latest_timestamp)
				{
					// Updating the post contents
					$post['ID'] = $post_id ;
					$post['post_status'] = get_post_status($post_id);
					wp_update_post($post);
					wp_set_post_tags( $post_id, 'Updated', true );
					update_post_meta($post_id, 'unix_timestamp', $post_date_unix);
					$oldpost++;
				}
			}
			// Handling new story
			else
			{
				// Posting the post contents
				$categories = $post['categories'];
				$post_id = wp_insert_post($post);
				if ( is_wp_error( $post_id ) )
					return $post_id;
				if (!$post_id)
					return;
				if(count($categories) > 0) {
					wp_set_post_terms($post_id, $categories, $this->stored_taxonomy);
				}

				add_post_meta($post_id, 'story_id', $story_id);
				add_post_meta($post_id, 'unix_timestamp', $post_date_unix);
				wp_set_post_tags( $post_id, 'Reuters TXT', false );
				$newpost++;
			}
		}
		$this->logger->info("Reuters TXT: ".$this->channel_name);
		$this->logger->notice($newpost." New & ".$oldpost." Updated");
    }

    // FUNCTION TO GET TXT ARRAY
    public function getTXTArray($item, $post_date_unix){
    	$post_title = (string) $item->headline;
    	$post_name = implode(' ', array_slice(explode(' ', $post_title), 0, 4));
		$post_date_gmt = gmdate('Y-m-d H:i:s', $post_date_unix);
		$post_date = get_date_from_gmt( $post_date_gmt );
		$post_author = $this->user_id;
		$post_type = $this->stored_type;
		$post_status = 'draft';
		$post_content = '';
		$categories = $post = array();
		// Getting the text contents
		$id = (string) $item->id;
		$item_url = 'http://rmb.reuters.com/rmd/rest/xml/item?id='.$id.'&channel='.$this->channel_alias.'&token='.$this->user_token;
		$item_xml = $this->getXml($item_url);
		if(!empty($item_xml)){
			$post_content = $item_xml->itemSet->newsItem->contentSet->inlineXML->html->body->asXML();
			// Getting the categories
			$categories = $this->getCategories($item_xml);
			// Forming the post array
			$post = compact('post_name', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'post_type', 'categories');
		}
    	return $post;
    }

    // FUNCTION TO PURGE CONTENT
    public function rd_purge(){
	    global $wpdb;
	    $posts = $wpdb->posts;
	    $postmeta = $wpdb->postmeta;
	    $term_relationships = $wpdb->term_relationships;
	    $terms = $wpdb->terms;
	    $purgeI = get_option('rd_purgeI_radiobuttons');
	    if($purgeI != 'none' && $purgeI != null)
        {
        	$result1 = $wpdb->get_results("SELECT $posts.ID, $posts.post_parent, $posts.post_title FROM $posts INNER JOIN $postmeta ON $postmeta.post_id = $posts.ID WHERE ($postmeta.meta_value LIKE 'tagreuters-com%' OR $postmeta.meta_value LIKE 'Reuters_Direct_Media%') AND $posts.post_status != 'publish' AND $posts.post_date < (Now() - INTERVAL ".$purgeI." Day)");
        	$result2 = $wpdb->get_results("SELECT $posts.post_content FROM $posts WHERE $posts.post_content LIKE '%[caption id=%'");
            $result3 = $wpdb->get_results("SELECT $posts.ID FROM $posts INNER JOIN $term_relationships ON $term_relationships.object_id = $posts.ID WHERE $term_relationships.term_taxonomy_id LIKE (SELECT $terms.term_id FROM $terms WHERE $terms.name = 'Archive')");

            // Capture all posts taged with Archive
            $NoPurgeItems = array();
            foreach($result3 as $item3)
            {
            	array_push($NoPurgeItems, $item3->ID);
            }

            // Capture all attachments within post content
        	$attachments = array();
        	foreach($result2 as $item2)
    		{
    			preg_match_all('/attachment_([\d]+)/', $item2->post_content, $matches);
    			if(sizeof($matches) > 0){
    				foreach($matches[1] as $match){
    					$attachment_id = intval($match);
    					if(!in_array($attachment_id, $attachments)){
    						array_push($attachments, $attachment_id);
    					}
    				}
    			}
    		}
    		// Delete Post: Draft, Attachment: Unused and not containing tag Archive
        	foreach($result1 as $item1)
            {
                if((!in_array($item1->ID, $attachments) && $item1->post_parent == 0) && (!in_array($item1->ID, $NoPurgeItems)))
                {
                	wp_delete_post($item1->ID, true);
                	$this->logger->info("Deleting post: ".$item1->post_title);
                }
		    }
        }

    }
}
?>