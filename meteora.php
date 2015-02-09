<?php
/**
 * Plugin Name: Meteora Pixels
 * Plugin URI: http://meteora.co/pixels
 * Description: Easily integrate Meteora's Pixel code in your blog.
 * Version: 0.5
 * Author: Meteora
 * Author URI: http://meteora.co/
 * License: Apache-v2.0
 */

defined('ABSPATH') or die('You have been weighed, you have been measured, and you have been found wanting.');

final class Meteora {
	private $options = array();
	private static $tmpl = array(
		'step1' => '<span style="color:#3699de">Step 1)</span> Login to your Meteora account',
		'step2' => '<span style="color:#0e9400">CONGRATS! You\'re all done. Your visitor tracking has been engaged.</span>',
		'wooProducts' => 'Enable Products Tracking',
		'wooConv' => 'Enable Conversions Tracking',
	);

	public function __construct() {
		$this->options = get_option('meteora');

		if (is_admin()) {
			add_action('admin_menu', array($this, 'addPluginPage'));
			add_action('admin_init', array($this, 'pageInit'));
		}

		if($this->isLoggedIn()) add_action('plugins_loaded', array($this, 'initPlugins'));
	}

	public function initPlugins() {
		add_action('wp_head', array($this, 'addPixel'), 0);

		if(self::hasWooCommerce()) {
			include_once('woo.php');
			if($this->opt('wooProd') === '1') {
				add_action('woocommerce_single_product_summary', array('meteoraWoo', 'getProductJS'),  99);
			}
			if($this->opt('wooConv') === '1') {
				add_action('woocommerce_thankyou', array('meteoraWoo', 'getConvJS'),  99);
			}
		}
	}

	public function addPluginPage() {
		add_options_page('Settings Admin', 'Meteora Pixels', 'manage_options', 'meteora-settings', array($this, 'createAdminPage'));
	}
	public function createAdminPage() {
?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Meteora Pixels</h2>
			<form method="post" action="options.php" id="meteoraOptions">
<?php
		// This prints out all hidden setting fields
		settings_fields('meteora-settings');
		do_settings_sections('meteora-settings');
		submit_button();
?>
			</form>
		</div>
		<script src="<?php echo plugins_url('auth.js', __FILE__); ?>"></script>
<?php
	}
	public function pageInit() {
		$header = '<a href="https://meteora.co/"><img src="' .  plugins_url('static/meteora.png', __FILE__) . '"></a>';
		$loggedIn = $this->opt('aid') !== '';

		register_setting('meteora-settings', 'meteora', array($this, 'sanitize'));

		add_settings_section('meteoraAuth', $header, null, 'meteora-settings');
		add_settings_field('aid', $this::$tmpl[$loggedIn ? 'step2' : 'step1'], array($this, 'aidCB'),
			'meteora-settings', 'meteoraAuth');

		if ($loggedIn && $this->hasWooCommerce()) {
			add_settings_section('wooOpts', 'WooCommerce Options:', null, 'meteora-settings');
			add_settings_field('wooProd', $this::$tmpl['wooProducts'], array($this, 'wooProdCB'),
				'meteora-settings', 'wooOpts');
			add_settings_field('wooConv', $this::$tmpl['wooConv'], array($this, 'wooConvCB'),
				'meteora-settings', 'wooOpts');
		}
	}
	public function sanitize($input) {
		if(!is_array($input) || empty($input) || !isset($input['aid']) || empty($input['aid'])) return array();

		$input['aid'] = sanitize_text_field(trim($input['aid']));
		if (isset($input['wooProd'])) $input['wooProd'] = sanitize_text_field(trim($input['wooProd']));
		if (isset($input['wooConv'])) $input['wooConv'] = sanitize_text_field(trim($input['wooConv']));

		return $input;
	}

	public function aidCB() {
		$aid = $this->opt('aid');
		printf('<input type="hidden" id="aid" name="meteora[aid]" value="%s">', $aid);
		if (empty($aid)) {
			printf('<button onclick="return meteora.signIn(\'%s\')">Sign In With Meteora</button>' . PHP_EOL, plugins_url('check_redirect.html', __FILE__));
		} else {
			print('<button onclick="return meteora.signOut()">De-activate</button>' . PHP_EOL);
		}
	}

	public function addPixel() {
		$aid = $this->opt('aid');
		printf('<script type="text/javascript" src="//%s.meteora.us/pixel?id=%s" async="true"></script>' . PHP_EOL, $aid, $aid);
	}

	// WooComerce hooks
	public function wooProdCB() {
		$checked = $this->opt('wooProd') ? ' checked="checked"' : '';
		printf('<input type="checkbox" name="meteora[wooProd]" value="1"%s>', $checked);
	}

	public function wooConvCB() {
		$checked = $this->opt('wooConv') ? ' checked="checked"' : '';
		printf('<input type="checkbox" name="meteora[wooConv]" value="1"%s>', $checked);
	}

	public function wooTrackConv() {
		if($this->opt('wooConv') == '') return;


	}

	private function opt($name, $def = '') {
		return isset($this->options[$name]) ? esc_attr($this->options[$name]) : $def;
	}

	private function isLoggedIn() {
		return $this->opt('aid') !== '';
	}

	private static function hasWooCommerce() {
		return class_exists('WooCommerce');
	}
}

$meteora = new Meteora();
