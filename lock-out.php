<?php
/*
Plugin Name: Lock Out
Plugin URI: http://www.skullbit.com
Description: Lock out users from accessing your website while performing upgrades or maintenance to your website, while still allowing certain user roles access.
Author: Skullbit
Author URI: http://www.skullbit.com
Version: 1.1

CHANGLOG

1.1 - April 3 2008 
	* Added missing include file required to update htaccess file.
	* Disabled Theme option as it is not yet available.
*/
include_once(ABSPATH.'wp-admin/admin-functions.php');
if( !class_exists( 'LockOutPlugin' ) ){
	class LockOutPlugin{
		
		function LockOutPlugin(){
			add_action( 'admin_menu', array($this,'AddPanel') );
			if( $_POST['action'] == 'lock_out_update' )
				add_action( 'init', array($this,'SaveSettings') );
			if( get_option('lockout_status') )
				add_action('template_redirect', array($this, 'TemplateLock'));
			if( $_GET['page'] == 'lock-out' ){
				wp_enqueue_script('jquery');
				add_action( 'admin_head', array($this, 'LockOutJS') );
			}
			if( get_option('lockout_save_file') )
				add_filter( 'mod_rewrite_rules', array($this, 'RewriteRules') );
				
				add_action( 'init', array($this, 'DefaultSettings') );
			
			register_deactivation_hook( __FILE__, array($this, "UnsetSettings") );
		}
		
		function AddPanel(){
			add_options_page( 'Lock Out', 'Lock Out', 10, 'lock-out', array($this, 'LockOutSettings') );
		}
		function DefaultSettings () {
			if( !get_option("lockout_status") )
			 	add_option("lockout_status", "0");
			if( !get_option("lockout_allow_role") )
			 	add_option("lockout_allow_role", "8");
			if( !get_option("lockout_msg_template") )
			 	add_option("lockout_msg_template", "1");
			if( !get_option("lockout_save_file") )
			 	add_option("lockout_save_file", "0");
			if( !get_option("lockout_title") ){
				add_option("lockout_title", get_option('blogname') . ' | Offline');
			}
			if( !get_option("lockout_head") ){
				add_option("lockout_head", '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\n" . '<title>' . get_option('lockout_title') . '</title>');
			}
			if( !get_option("lockout_body") ){
				add_option("lockout_body", '<p>This site is currently undergoing site-wide maintenance and is temporarily unavailable.</p>');
			}
			delete_option("lockout_html");
		}
		function UnsetSettings () {
			  delete_option("lockout_status");
			  delete_option("lockout_allow_role");
			  delete_option("lockout_msg_template");
			  delete_option("lockout_save_file");
			  delete_option("lockout_title");
			  delete_option("lockout_body");
			  delete_option("lockout_head");
			  save_mod_rewrite_rules();
		}
		function SaveSettings(){			
			check_admin_referer('lock-out-update-options');
			update_option("lockout_status", $_POST['lockout_status']);
			update_option("lockout_allow_role", $_POST['lockout_allow_role']);
			update_option("lockout_msg_template", $_POST['lockout_msg_template']);
			update_option("lockout_save_file", $_POST['lockout_save_file']);
			if( $_POST['lockout_save_file'] )
				$save = $this->SaveFile();
			if( $save != 1 ) $err = $save;
			if( $_FILES['lockout_upload']['name'] ){
				$upload = $this->LockOutUpload();
				$_POST['lockout_title'] = $upload[0];
				$_POST['lockout_head'] = $upload[1];
				$_POST['lockout_body'] = $upload[2];
			}
			
			update_option("lockout_title", $_POST['lockout_title']);
			$_POST['lockout_head'] = $this->Title2Head();
			update_option("lockout_head", $_POST['lockout_head']);
			update_option("lockout_body", $_POST['lockout_body']);
			
			save_mod_rewrite_rules();
			
			if( $_POST['lockout_status'] )
				$_POST['notice'] = $err . __('Lock Out Enabled. Settings saved.','stealthlogin');
			else
				$_POST['notice'] = $err . __('Lock Out Disabled. Settings saved.','stealthlogin');
		}	
		
		function LockOutJS(){
			?>
            <script type="text/javascript">
			<!--
				jQuery(document).ready(function() {
				<?php if ( get_option('lockout_msg_template') ){?>
					jQuery('#lockout_custom').hide();
				<?php }else{ ?>
					jQuery('#lockout_theme').hide();
				<?php } ?>
					jQuery('.upload').hide();
					jQuery('input#msg_custom').click(function() {
						jQuery('#lockout_theme').hide();
						jQuery('#lockout_custom').show(400);
						return true;
					});
					jQuery('input#msg_theme').click(function() {
						jQuery('#lockout_custom').hide();
						jQuery('#lockout_theme').show(400);
						return true;
					});
					jQuery('#showupload').click(function() {
						jQuery('.noupload').hide();
						jQuery('.upload').css({"display" : "table-row" });
						return true;
					});
					jQuery('#shownoupload').click(function() {		
						jQuery('.upload').hide();
						jQuery('input#upload').attr("value", "");
						jQuery('.noupload').css({"display" : "table-row" });
						return true;
					});
					
				});
			-->
			</script>
            <style type="text/css">
				#lockout_custom, #lockout_theme{ margin-left:2em; }
				#lockout_body{ width:99%; height:400px; }
				#lockout_head{ width: 99%; height:200px; }
			</style>
            <?php
		}
		function LockOutSettings(){
			if( $_POST['notice'] )
				echo '<div id="message" class="updated fade"><p><strong>' . $_POST['notice'] . '</strong></p></div>';
			?>
            <div class="wrap">
            	<h2><?php _e('Lock Out Settings', 'stealthlogin')?></h2>
                <form method="post" action="" enctype="multipart/form-data">
                	<?php if( function_exists( 'wp_nonce_field' )) wp_nonce_field( 'lock-out-update-options'); ?>
                    <table class="form-table">
                        <tbody>
                        	<tr valign="top">
                       			 <th scope="row"><label for="status"><?php _e('Lock Out Status', 'lockout');?></label></th>
                        		<td><label><input type="radio" name="lockout_status" id="status" value="1" <?php if ( get_option('lockout_status') ) echo 'checked="checked"';?> /> <?php _e('Enabled', 'lockout');?></label><br />
                                <input type="radio" name="lockout_status" value="0" <?php if ( !get_option('lockout_status') ) echo 'checked="checked"';?> /> <?php _e('Disabled', 'lockout');?></label></td>
                        	</tr>
                            <tr valign="top">
                       			 <th scope="row"><label for="allow_role"><?php _e('Allow User Role', 'lockout');?></label></th>
                        		<td><select name="lockout_allow_role" id="allow_role">
                                		<option value="level_8" <?php if(get_option('lockout_allow_role') == "level_8")echo 'selected="selected"';?>>Administrators</option>
                                        <option value="level_5" <?php if(get_option('lockout_allow_role') == "level_5")echo 'selected="selected"';?>>Editors</option>
                                        <option value="level_2" <?php if(get_option('lockout_allow_role') == "level_2")echo 'selected="selected"';?>>Authors</option>
                                        <option value="level_1" <?php if(get_option('lockout_allow_role') == "level_1")echo 'selected="selected"';?>>Contributors</option>
                                        <option value="level_0" <?php if(get_option('lockout_allow_role') == "level_0")echo 'selected="selected"';?>>Subscribers</option>
                                    </select>
                                </td>
                        	</tr>
                            <tr valign="top">
                       			 <th scope="row"><label for="msg_theme"><?php _e('Message Template:', 'lockout');?></label></th>
                        		<td><label><input type="radio" disabled="disabled" name="lockout_msg_template" id="msg_theme" value="1" <?php if ( get_option('lockout_msg_template') ) echo 'checked="checked"';?> /> <?php _e('Use Current Theme - coming soon', 'lockout');?></label><br />
                                <input type="radio" name="lockout_msg_template" id="msg_custom" value="0" <?php if ( !get_option('lockout_msg_template') ) echo 'checked="checked"';?> /> <?php _e('Custom', 'lockout');?></label></td>
                            </tr>
						</tbody>
                 	</table>
				 <div id="lockout_theme">
                	<h3><?php _e('Current Theme Message Template Settings', 'lockout');?></h3>
                    <table class="form-table">
                        <tbody>
                        	<tr valign="top">
                       			 <th scope="row"><label for="status"><?php _e('Lock Out Status', 'lockout');?></label></th>
                        		<td></td>
                        	</tr>
                        </tbody>
                    </table>
                </div>                    
                <div id="lockout_custom">
                	<h3><?php _e('Custom Message Template Settings', 'lockout');?></h3>
                    <table class="form-table">
                        <tbody>
                        	<tr valign="top">
                       			 <th scope="row"><label for="save_file"><?php _e('Save Template as File', 'lockout');?></label></th>
                        		<td><input type="checkbox" id="save_file" name="lockout_save_file" value="1" <?php if( get_option('lockout_save_file') ) echo 'checked="checked"';?> /><small><?php _e('If checked this will save your Message Template to a file so that any changes or errors you make while in Lock Out mode will not prevent the Lock Out Message from showing.', 'lockout');?></small></td>
                        	</tr>
                            
                             <tr valign="top">
                            	<th scope="row"><label for="upload"><?php _e('Message Creation', 'lockout');?></label></th>
                                <td><label><input id="shownoupload" type="radio" name="create" checked="checked" /> Create Custom Template</label> <strong> -OR- </strong> <label><input type="radio" name="create" id="showupload" /> Upload Template File</label></td>
                            </tr>
                            
                            <tr valign="top" class="upload">
                            	<th scope="row"><label for="upload"><?php _e('Upload Template File', 'lockout');?></label></th>
                                <td><input type="file" name="lockout_upload" id="upload" /><br /><small><?php _e('Upload an HTML file for use as your Lock Out Message Template', 'lockout');?></small>
                                </td>
                            </tr>
                            
                            <tr valign="top" class="noupload">
                            	<th scope="row"><label for="title"><?php _e('Title', 'lockout');?></label></th>
                                <td><input type="text" name="lockout_title" id="title" value="<?php echo get_option('lockout_title');?>" /><br /><small><?php _e('Title will not show if Save Template as File is not checked', 'lockout');?></small></td>
                            </tr>
                            <tr valign="top" class="noupload">
                            	<th scope="row"><label for="lockout_head"><?php _e('Head', 'lockout');?></label></th>
                                <td><textarea type="text" name="lockout_head" id="lockout_head" rows="10" cols="40"><?php echo stripslashes( get_option('lockout_head') );?></textarea></td>
                            </tr>
                            <tr valign="top" class="noupload">
                            	<th scope="row"><label for="lockout_body"><?php _e('Body', 'lockout');?></label></th>
                                <td><?php the_editor( stripslashes(get_option('lockout_body')), 'lockout_body'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                    <p class="submit"><input name="Submit" value="<?php _e('Save Changes','lockout');?>" type="submit" />
                    <input name="action" value="lock_out_update" type="hidden" />
                </form>
              
            </div>
           <?php
		 }
		 
		 function TemplateLock(){
		 	if( !current_user_can( get_option('lockout_allow_role') ) && !strstr($_SERVER['PHP_SELF'], 'feed/') && !strstr($_SERVER['PHP_SELF'], 'wp-admin/')) {
				if( get_option('lockout_msg_template') ){
					echo '';
				}else{
					
					if( get_option( 'lockout_save_file' ) )
						header( 'Location: ' . trailingslashit(get_option('siteurl')) . 'lockout.php' );
					else
						echo $this->FinalHTML();
				}
				exit();    
			} else if(strstr($_SERVER['PHP_SELF'], 'feed/') || strstr($_SERVER['PHP_SELF'], 'trackback/')) {
				header("HTTP/1.0 503 Service Unavailable");  
				exit();    
			}
		 }
		 
		 function FinalHTML(){
		 	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n" .
							'<html xmlns="http://www.w3.org/1999/xhtml">' . "\n" .
							'<head>' . "\n" .
							stripslashes(get_option( 'lockout_head' )) . "\n" .
							'</head>' . "\n" .
							'<body>' . "\n" .
							stripslashes(get_option( 'lockout_body' )) . "\n" .
							'</body>' . "\n" .
							'</html>';
			return $html;
		 }
		 
		 function LockOutUpload(){
		 	$upload_dir = ABSPATH . get_option('upload_path');
			$upload_file = trailingslashit($upload_dir) . basename($_FILES['lockout_upload']['name']);
			if( !is_dir($upload_dir) )
				wp_upload_dir();
			if( move_uploaded_file($_FILES['lockout_upload']['tmp_name'], $upload_file) ){
				chmod($upload_file, 0777);
				
				$file = file_get_contents($upload_file);

				$chead = preg_match('/<head>([^]]+)<\/head>/',$file, $hmatches);
				$cbody = preg_match('/<body>([^]]+)<\/body>/',$file, $bmatches);
				if( $chead )
					$head = $hmatches[1];
				if( $cbody )
					$body = $bmatches[1];
				$ctitle = preg_match('/<title>([^]]+)<\/title>/',$head, $tmatches);
				if( $ctitle )
					$title = $tmatches[1];
				
				
				return array($title, $head, $body);
			}else{
				echo 'Upload Failed :(';
			}
			
		 }
		 
		 
		 function Title2Head(){
		 	$head = $_POST['lockout_head'];
			$title = '<title>' . get_option('lockout_title') . '</title>';
			$pattern = '/<title>([^]]+)<\/title>/';
			$newhead = preg_replace($pattern, $title, $head);
			return $newhead;
		 }
		
		function SaveFile(){
			chmod(ABSPATH, 0755);	
			$html = $this->FinalHTML();
			$lo_handle = fopen(ABSPATH . 'lockout.php', 'w+t') or $error="Cannot create/edit 'lockout.php'";
			ftruncate($lo_handle, 4);
			$lo_write = fwrite($lo_handle, $html) or $error="Cannot write to file";
			fclose($lo_handle);
			if( $error ) return $error;
			else return true;
		}
		
		function RewriteRules($rewrite){
			
			$insert = "# LOCK_OUT\n" . 
					  "RewriteCond %{HTTP_REFERER} !^" . get_option('siteurl') . "\n" . //if did not come from another Skullbit Page			
					  "RewriteRule ^/index\.php$ " . get_option('siteurl') .  "/lockout.php [L]\n" . //Send to lockout page	  
				  	  "# END LOCK-OUT\n" .
					  "RewriteCond %{REQUEST_FILENAME} !-f";
			
			$lines = explode('RewriteCond %{REQUEST_FILENAME} !-f', $rewrite);
				
			$rewrite = $lines[0] . $insert . $lines[1];
			return $rewrite;
		}
	}
} // END Class LockOutPlugin	

if( class_exists( 'LockOutPlugin' ) ){
	$lockout = new LockOutPlugin();
}

if (!function_exists('file_get_contents')) {
		 function file_get_contents($filename, $incpath = false, $resource_context = null){
			  if (false === $fh = fopen($filename, 'rb', $incpath)) {
				  trigger_error('file_get_contents() failed to open stream: No such file or directory', E_USER_WARNING);
				  return false;
			  }
	 
			  clearstatcache();
			  if ($fsize = @filesize($filename)) {
				  $data = fread($fh, $fsize);
			  } else {
				  $data = '';
				  while (!feof($fh)) {
					  $data .= fread($fh, 8192);
				  }
			  }
	 
			  fclose($fh);
			  return $data;
      	}
}
?>