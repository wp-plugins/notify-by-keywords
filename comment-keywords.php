<?php
/*
Plugin Name: Comment Keywords
Description: When a comment contains any of specified words in its content it will be sent to administrator with highlighted keywords.
Version: 0.1
Author: Limeira Studio
Author URI: http://www.limeirastudio.com/
Copyright: Limeira Studio
License: GPL2
*/

class Comment_Keywords	{
	
	private $plugname;
	private $version;
	private $text_domain;
	private $defaults;
	
	public function __construct()	{
			
		$this->plugname = 'Comment Keywords';
		$this->version = '0.1';
		$this->text_domain = 'ck';
		$this->defaults = array('state'=>'on','keywords'=>array('excellent article', 'you need a job', 'Lorem ipsum dolor sit amet'));
		
		add_action('wp_insert_comment', array(&$this,'comment_inserted'));
		add_action('admin_menu', array(&$this, 'add_menu'));			

	}
	
	public function comment_inserted($comment_id) {
		$comment_object = get_comment($comment_id);
		$opt = $this->get_options();
		if($opt['state'] == 'off') return;	
		$res = '';
		foreach($opt['keywords'] as $kw)	{
			$search_in = (!$res) ? $comment_object->comment_content : $res;	
			if(stristr($search_in, $kw))	{			
				$res = preg_replace("/($kw)/i", '<span style="background-color:yellow">$1</span>', $search_in);
			}
		}

		if($res)	{			
			$body = '';	
			$body .=  __('Author : '). $comment_object->comment_author . '<br/>';
			$body .=  __('IP : '). $comment_object->comment_author_IP . '<br/>';
			$body .=  __('E-mail : '). $comment_object->comment_author_email  . '<br/>';
			
			$subject = __('Important# ').get_bloginfo('name');
					
			$body .= $res.'<p>'.get_permalink($comment_object->comment_post_ID).'#comments</p>';
			
			add_filter('wp_mail_content_type', function($content_type) {return 'text/html';});	
			wp_mail(get_option('admin_email'), $subject, $body);
			remove_filter('wp_mail_content_type', function($content_type) {return 'text/html';});			
		}
	}
	
	public function ck_options_page()	{
		
		if(isset($_POST['cmd']) && $_POST['cmd'] == 'ck_save_opt')	{
			$keywords = explode("\n", trim($_POST['keywords']));
			$options = array('state'=>$_POST['state'],'keywords'=>array_map(function($e){if($e)return trim($e);}, $keywords));
	    	update_option('ck_options', $options);
	    	?>
		<div class="updated"><p><strong><?php echo __('Settings saved',$this->text_domain); ?></strong></p></div>
		<?php } 
		$opt = $this->get_options(); ?>
		<div class="wrap">
		<h2><?=$this->plugname;?> Options</h2>   
		    
		<form method="post" action="">
		<table class="form-table">
		<tbody>
			<tr>
		<th>
		<label for="state"><?php echo _e('Enable',$this->text_domain);?></label>
		</th>
		<td>
		On <input type="radio" <?php checked($opt['state'], 'on'); ?> name="state" value="on" />
		Off <input type="radio" <?php checked($opt['state'], 'off'); ?> name="state" value="off" />
		</td>
		</tr>
		<tr class="keywords-wrap">
		<th>
		<label for="keywords"><?php echo _e('Keywords',$this->text_domain);?> <p class="description">When a comment contains any of these words or phrase in its content it will be sent to administrator with highlighted keywords, in despite of disabled global option about comments notifications.<br/>One word or phrase per line.</p>
		</th>
		<td>
			<textarea name="keywords" cols="70" rows="8"><?php 
				foreach($opt['keywords'] as $k)	{
					echo $k."\n";
				} ?></textarea>
		</td>
		</tr>
		</tbody>
		</table>
		<input type="hidden" name="cmd" value="ck_save_opt">
		<?php @submit_button(); ?>
		</form>
		</div>
		<?php
	}

	private function get_options()	{
		return (!get_option('ck_options')) ? $this->defaults : get_option('ck_options');
	}
	
	public function add_menu()	{
		add_options_page($this->plugname, $this->plugname, 'manage_options', 'ck_options_page_unique', array(&$this,'ck_options_page'));
	}
	
	public static function activate() {
	}
	
	public static function deactivate()	{
	    delete_option('ck_options');
	}
			 
}

register_activation_hook(__FILE__, 'Comment_Keywords::activate');
register_deactivation_hook(__FILE__, 'Comment_Keywords::deactivate');
		
$ck = new Comment_Keywords();

?>
