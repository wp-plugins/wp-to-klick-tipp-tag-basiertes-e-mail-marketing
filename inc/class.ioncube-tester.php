<?php
//if (basename($_SERVER['SCRIPT_FILENAME']) == 'class.ioncube-tester.php') { die ("Please do not access this file directly. Thanks!"); }
if ( ! defined( 'ICT_PLUGIN_URL' ) )
define ("ICT_PLUGIN_URL", plugin_dir_url(__FILE__));

class ioncubetester
{
  function __construct() {
	$this->IC_admin_notices();
  }
  private function IC_admin_notices() {
	if ($this->check_ioncube_loaders() == true) {
		if ( function_exists('ioncube_loader_iversion') ) {
			$ioncube_loader_iversion = ioncube_loader_iversion();
			$ioncube_loader_version_major = (int)substr($ioncube_loader_iversion,0,1);
			$ioncube_loader_version_minor = (int)substr($ioncube_loader_iversion,1,2);
			$ioncube_loader_version_revision = (int)substr($ioncube_loader_iversion,3,2);
			$ioncube_loader_version = "$ioncube_loader_version_major.$ioncube_loader_version_minor.$ioncube_loader_version_revision";
		} else {
			$ioncube_loader_version = ioncube_loader_version();
			$ioncube_loader_version_major = (int)substr($ioncube_loader_version,0,1);
			$ioncube_loader_version_minor = (int)substr($ioncube_loader_version,2,1);
		}
		echo '<img src="'.WP_TO_KLICK_TIPP_URL.'/assets/img/accepted_32.png" alt=""/><br />';
		echo 'IonCube loaders v' . $ioncube_loader_version . '&nbsp;';
		_e('are available on this web server. You can start the installation of our premium version', 'wptkt');
	} else {
		$ioncube_phpini_exists = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'php.ini';
		if (!file_exists($ioncube_phpini_exists)) {
			echo '<img src="'.WP_TO_KLICK_TIPP_URL.'/assets/img/cancel_32.png" alt=""/><br />';
			_e('IonCube loaders are <span style="color:red;">not available</span> on this web server.', 'wptkt');
			echo '&nbsp;<a href="http://www.ioncube.com/loaders.php' . '" target="_blank">'.__('Please click here to start the installation wizard', 'wptkt').'</a>&nbsp;';
			_e('which offers an interactive tutorial on how to install the required "ionCube Loader" on your web server.','wptkt');
		} else {
			echo '<img src="'.WP_TO_KLICK_TIPP_URL.'/assets/img/cancel_32.png" alt=""/><br />';
			echo sprintf(__('You already ran the <a href="%1s" target="_blank">ioncube installation wizard</a> and copied the created php.ini file to <strong>%2s</strong><br/>To finish the ioncube installation, please also copy the file <strong>php.ini</strong> to the directory <strong>%3s</strong><br/>Afterwards the ioncube installation is finished and this admin message should turn green.','wptkt'), plugin_dir_url(__FILE__) . 'inc/loader-wizard.php', plugin_dir_path(__FILE__) . '/', ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR );
		}
	}
  }
  private function check_ioncube_loaders() {
	if (extension_loaded('ionCube Loader')) {
		return true;
	}
	if ( function_exists('ioncube_file_is_encoded') ) {
		return true;
	}
	if ( function_exists('phpinfo') ) {
		ob_start();
		phpinfo(8);
		$phpinfo = ob_get_clean();
		if ( false !== strpos($phpinfo, 'ionCube') ) {
			return true;
		}
	}
	return false;
  }
}  //info: end class
?>