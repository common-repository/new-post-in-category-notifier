<?php
/*
Plugin Name: New Post In Tag Notifier
Description: Allows you to send mail to specified address when creating a post under specified tag.
Version: 0.1
Author: Harri Paavola
Author Email: harri.paavola@gmail.com
License: MIT License
*/

class NewPostInTagNotifier {

  const name = 'New Post In Tag Notifier';
  const slug = 'new_post_in_tag_notifier';
  
  function __construct() {
    register_activation_hook(__FILE__, array(
        &$this, 'install_new_post_in_tag_notifier'
      )
    );

    add_action('init', array(&$this, 'init_new_post_in_tag_notifier'));
  }
  
  function install_new_post_in_tag_notifier() {
    global $wpdb;
    $table_name = $wpdb->prefix."postintagnotify";

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      recipient text NOT NULL,
      title text NOT NULL,
      tag text NOT NULL,
      UNIQUE KEY id (id)
    );";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $row = $wpdb->get_row("SELECT * FROM ".$table_name);

    if (!$row) {
      $recipient = "example@domain.invalid";
      $title = "hello";
      $tag = "interesting-tag";
      $wpdb->insert($table_name, array(
        'id'=>$id,
        'recipient' => $recipient,
        'title' => $title,
        'tag' => $tag)
      );
    }
  }
  
  function init_new_post_in_tag_notifier() {
    add_action('publish_post', array(&$this, 'send_notification_email'));
    add_action('admin_menu', array(&$this, 'notify_menu'));
    add_action('admin_init', array(&$this, 'register_notify_settings'));
  }

  public function send_notification_email($post_ID) {
    if ($_POST['post_status'] == 'publish' && $_POST['original_post_status'] != 'publish'){
      global $wpdb;
      $table_name = $wpdb->prefix."postintagnotify";
      $row = $wpdb->get_row("SELECT * FROM ".$table_name);
      $recipient = $row->recipient;
      $tag = $row->tag;
      $title = $row->title;
      $tags = wp_get_post_tags($post_ID, array('fields' => 'names'));
      if (in_array($tag, $tags)) {
        $url = get_permalink($post_ID);
        $post_title = get_the_title($post_ID);
        wp_mail($recipient, $title, $post_title.': '.$url);
      }
    }
  }

  public function register_notify_settings() {
    register_setting('notify_options_group', 'notify_recipient', array(&$this, 'store_notify_settings'));
    register_setting('notify_options_group', 'notify_tag', array(&$this, 'store_notify_settings'));
    register_setting('notify_options_group', 'notify_title', array(&$this, 'store_notify_settings'));
  }

  public function notify_menu() {
    add_options_page(
      'New Post In Tag Notifier Options',
      'New Post In Tag Options',
      'manage_options',
      'notify_options_id',
      array(&$this, 'notify_options')
    );
  }

  public function notify_options() {
    if (!current_user_can('manage_options')) {
      wp_die(__( 'You do not have sufficient permissions to access this page.'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix."postintagnotify";
    $row = $wpdb->get_row("SELECT * FROM ".$table_name);
    $recipient = $row->recipient;
    $tag = $row->tag;
    $title = $row->title;    
    echo '<div class="wrap">';
    echo '<h2>Settings for New Post In Tag Notifier plugin.</h2>';
    echo '<p><strong>Email</strong>: Email address where notifications are sent.<br>
    <strong>Title</strong>: Title for the email (content will be title-of-the-post: url-of-the-post).<br>
    <strong>Tag</strong>: Posts that have this tag will be sent out.</p>';
    echo '<form method="post" action="options.php"> ';
    settings_fields('notify_options_group');
    echo '<label for="notify_recipient">Email:</label>';
    echo '<input type="text" id="notify_recipient" name="notify_recipient" value="'.$recipient.'">';
    echo '<br><label for="notify_title">Title:</label>';
    echo '<input type="text" id="notify_title" name="notify_title" value="'.$title.'">';
    echo '<br><label for="notify_tag">Tag:</label>';
    echo '<input type="text" id="notify_tag" name="notify_tag" value="'.$tag.'">';
    submit_button();
    echo '</form>';
    echo '</div>';
  }

  public function store_notify_settings() {
    global $wpdb;
    $recipient = $_POST['notify_recipient'];
    $tag = $_POST['notify_tag'];
    $title = $_POST['notify_title'];
    $table_name = $wpdb->prefix."postintagnotify";
    $data = array('recipient' => $recipient, 'title' => $title, 'tag' => $tag);
    $where = array('id'=>1);
    $wpdb->update($table_name, $data, $where);

  }

} // end class
if (is_admin()) {
  new NewPostInTagNotifier();
}