<?php
/*
Plugin Name: Backup DB to Telegram Channel
Plugin URI: https://instagram.com/m4tinbeigi
Description: This plugin takes a backup of the WordPress database and sends it to a Telegram channel through a Telegram bot.
Version: 1.0
Author: Rick Sanchez & ChatGPT
Author URI: https://twitter.com/m4tinbeigi
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Backup_To_Telegram {
  public function __construct() {
    add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
    add_action( 'admin_init', array( $this, 'register_settings' ) );
    add_action( 'admin_init', array( $this, 'check_manual_backup' ) );
    
  }
  
  public function check_manual_backup(){
      if(isset($_POST['backup-to-telegram-manual-backup'])){
          self::backup_and_send_to_telegram();
      }
  }

  public function add_settings_page() {
    add_options_page(
      'Backup to Telegram',
      'Backup to Telegram',
      'manage_options',
      'backup-to-telegram',
      array( $this, 'settings_page' )
    );
  }

  public function register_settings() {
    register_setting( 'backup-to-telegram-settings-group', 'backup-to-telegram-bot-token' );
    register_setting( 'backup-to-telegram-settings-group', 'backup-to-telegram-channel-id' );
  }

  public function settings_page() {
    ?>
    <div class="wrap">
      <h1>Backup to Telegram Settings</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields( 'backup-to-telegram-settings-group' );
        do_settings_sections( 'backup-to-telegram-settings-group' );
        ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row">Bot Token</th>
            <td><input type="text" name="backup-to-telegram-bot-token" value="<?php echo esc_attr( get_option( 'backup-to-telegram-bot-token' ) ); ?>" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Channel ID</th>
            <td><input type="text" name="backup-to-telegram-channel-id" value="<?php echo esc_attr( get_option( 'backup-to-telegram-channel-id' ) ); ?>" /></td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <form method="post" action="">
        <input type="hidden" name="backup-to-telegram-manual-backup" value="1">
<?php submit_button( 'Backup Now' ); ?>
</form>
    </div>
    <?php
  }

  public static function backup_and_send_to_telegram() {
    global $wpdb;

    $backupFile = ABSPATH . 'wp-content/backups/' . $wpdb->dbname . '_' . date( 'Y-m-d_H-i-s' ) . '.sql';

    $command = "    mysqldump --user=" . DB_USER . " --password=" . DB_PASSWORD . " --host=" . DB_HOST . " " . $wpdb->dbname . " > " . $backupFile;
    exec( $command );

    $botToken = get_option( 'backup-to-telegram-bot-token' );
    $channelId = get_option( 'backup-to-telegram-channel-id' );
    $postData = array(
      'chat_id' => $channelId,
      'caption' => $wpdb->dbname . ' backup ' . date( 'Y-m-d H:i:s' ),
    );
    $file = new CURLFile( $backupFile );
    $postData['document'] = $file;

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, "https://api.telegram.org/bot" . $botToken . "/sendDocument" );
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $postData );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $ch );
    curl_close( $ch );

    unlink( $backupFile );
  }
  
  
}

new Backup_To_Telegram();
