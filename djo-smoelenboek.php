<?php
/*
 * Plugin Name: DJO Smoelenboek
 * Plugin URI: https://github.com/rmoesbergen/djo-smoelenboek
 * Description: Plugin voor de DJO smoelenboek koppeling met de ledenadministratie
 * Author: Ronald Moesbergen
 * Version: 0.1.0
 */

defined('ABSPATH') or die('Go away');

if (!class_exists('DJO_Smoelenboek')) {
  class DJO_Smoelenboek {

    private static $db = null;

    public static function init() {
      add_shortcode('djo_smoelenboek', array('DJO_Smoelenboek', 'smoelenboek'));
      if ( is_admin() ) {
        add_action( 'admin_init', array('DJO_Smoelenboek', 'register_settings' ));
        add_action( 'admin_menu', array('DJO_Smoelenboek', 'adminmenu'));
      }
    }

    public static function register_settings() {
      $options = array(
        'type' => 'string',
        'default' => ''
      );

      register_setting( 'djo-smoelenboek', 'djo-smoelen-url', $options );
    }

    public static function adminmenu() {
      add_options_page( 'DJO Smoelenboek settings', 'DJO Smoelenboek', 'manage_options', 'DJO_Smoelenboek', array('DJO_Smoelenboek', 'admin_options') );
    }

    public static function admin_options() {
      if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
      }
      echo '<div class="wrap">';
      echo '<h1>DJO Smoelenboek instellingen</h1>';
      echo '<form method="post" action="options.php">';
      settings_fields( 'djo-smoelenboek' );
      do_settings_sections( 'djo-smoelenboek' ); ?>

      <table class="form-table">
        <tr valign="top">
        <th scope="row">Ledenadministratie Smoelenboek API URL</th>
        <td><input type="text" name="djo-smoelen-url" value="<?php echo esc_attr( get_option('djo-smoelen-url') ); ?>" /></td>
        </tr>
      </table>
    <?php
      submit_button();
      echo '</form>';
      echo '</div>';
    }

    public static function smoelenboek($args) {
      $params = shortcode_atts(array('dag' => 'vrijdag'), $args);
      $dag = $params['dag'];

      $idp_access_token = get_user_meta(get_current_user_id(), 'woi_idp_access_token', true);
      $options = array('headers' => array('Authorization' => "IDP $idp_access_token"));
      $url = get_option('djo-smoelen-url');
      $response = wp_remote_get("$url/$dag/", $options);

      if (is_wp_error($response)) return "Error receiving smoelenboek response: " . $response->get_error_message();
      $json = wp_remote_retrieve_body($response);
      $smoelenboek = json_decode($json);

      $output = "<div id='gallery-1' class='gallery gallery-columns-4 gallery-size-thumbnail'>\n";
      $counter = 1;
      foreach ($smoelenboek->{$dag} as $row) {
        $id = $row->id;
        $voornaam = $row->first_name;
        $imgurl = $row->photo;

        $output .= "<dl class='gallery-item'>\n";
        $output .= "<dt class='gallery-icon portrait'>\n";
        $output .= "<img width='100' height='150' src='$imgurl' class='attachment-thumbnail size-thumbnail' alt='' aria-describedby='gallery-1-$id' />\n";
        $output .= "</dt>\n";
        $output .= "<dd class='wp-caption-text gallery-caption' id='gallery-1-$id'>$voornaam</dd>\n";
        $output .= "</dl>\n";

        if ($counter++ == 4) {
          $counter = 1;
          $output .= '<br style="clear: both" />';
        }
      }
      $output .= '<br style="clear: both"/></div>';

      return $output;
    }
  }

  DJO_Smoelenboek::init();
}
