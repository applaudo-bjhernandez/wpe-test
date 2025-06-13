<?php

namespace DeliciousBrains\WPMDB\Pro\TPF;

use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Transfers\Files\FileProcessor;
use DeliciousBrains\WPMDB\Data\Stage;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\PluginHelper;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\TransferManager;
use DeliciousBrains\WPMDB\Pro\Transfers\Receiver;
use Exception;

/**
 * Handles AJAX requests for theme and plugin (and other) files lists from
 * remote sites.
 *
 * @phpstan-import-type StageName from Stage
 */
class ThemePluginFilesRemote {
	/**
	 * @var Util
	 */
	public $transfer_util;

	/**
	 * @var TransferManager
	 */
	public $transfer_manager;

	/**
	 * @var FileProcessor
	 */
	public $file_processor;

	/**
	 * @var Manager
	 */
	public $queueManager;

	/**
	 * @var Receiver
	 */
	public $receiver;

	/**
	 * @var PluginHelper
	 */
	private $plugin_helper;

	public function __construct(
		Util $util,
		FileProcessor $file_processor,
		Manager $queue_manager,
		TransferManager $transfer_manager,
		Receiver $receiver,
		PluginHelper $plugin_helper
	) {
		$this->queueManager            = $queue_manager;
		$this->transfer_util           = $util;
		$this->file_processor          = $file_processor;
		$this->transfer_manager        = $transfer_manager;
		$this->receiver                = $receiver;
		$this->plugin_helper           = $plugin_helper;
	}

	public function register() {
		add_action(
			'wp_ajax_nopriv_wpmdbtp_respond_to_get_remote_themes',
			array( $this, 'ajax_tp_respond_to_get_remote_themes' )
		);
		add_action(
			'wp_ajax_nopriv_wpmdbtp_respond_to_get_remote_plugins',
			array( $this, 'ajax_tp_respond_to_get_remote_plugins' )
		);
		add_action(
			'wp_ajax_nopriv_wpmdbtp_respond_to_get_remote_muplugins',
			array( $this, 'ajax_tp_respond_to_get_remote_muplugins' )
		);
		add_action(
			'wp_ajax_nopriv_wpmdbtp_respond_to_get_remote_others',
			array( $this, 'ajax_tp_respond_to_get_remote_others' )
		);
		add_action(
			'wp_ajax_nopriv_wpmdbtp_respond_to_get_remote_root',
			array( $this, 'ajax_tp_respond_to_get_remote_root' )
		);

		add_action( 'wp_ajax_nopriv_wpmdbtp_transfers_send_file', array( $this, 'ajax_tp_respond_to_request_files', ) );
		add_action( 'wp_ajax_nopriv_wpmdbtp_transfers_receive_file', array( $this, 'ajax_tp_respond_to_post_file' ) );
	}

	public function ajax_tp_respond_to_get_remote_themes() {
		$this->respond_to_get_remote_folders( Stage::THEMES );
	}

	public function ajax_tp_respond_to_get_remote_plugins() {
		$this->respond_to_get_remote_folders( Stage::PLUGINS );
	}

	public function ajax_tp_respond_to_get_remote_muplugins() {
		$this->respond_to_get_remote_folders( Stage::MUPLUGINS );
	}

	public function ajax_tp_respond_to_get_remote_others() {
		$this->respond_to_get_remote_folders( Stage::OTHERS );
	}

	/**
	 * AJAX handler to respond with remote root file list.
	 *
	 * @return void
	 */
	public function ajax_tp_respond_to_get_remote_root() {
		$this->respond_to_get_remote_folders( Stage::ROOT );
	}

	/**
	 * Generic AJAX handler to respond to remote file lists.
	 *
	 * @param StageName $stage
	 *
	 * @return void
	 */
	public function respond_to_get_remote_folders( $stage ) {
		$this->plugin_helper->respond_to_get_remote_folders( $stage );
	}

	/**
	 *
	 * Fired off a nopriv AJAX hook that listens to pull requests for file batches
	 *
	 * @return void
	 */
	public function ajax_tp_respond_to_request_files() {
		$this->plugin_helper->respond_to_request_files();
	}

	/**
	 * Respond to post of a file.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function ajax_tp_respond_to_post_file() {
		$this->plugin_helper->respond_to_post_file();
	}
}
