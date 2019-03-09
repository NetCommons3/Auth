<?php
/**
 * AutoUserRegistController::beforeFilter()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * AutoUserRegistController::beforeFilter()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\AutoUserRegistController
 */
class AutoUserRegistControllerBeforeFilterWSecretKeyTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.auth.site_setting4auto_regist_w_secret_key',
		'plugin.user_attributes.user_attribute4test',
		'plugin.user_attributes.user_attribute_choice4test',
		'plugin.user_attributes.user_attribute_layout',
		'plugin.user_attributes.user_attribute_setting4test',
		'plugin.user_attributes.user_attributes_role4test',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'auth';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'auto_user_regist';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
	}

/**
 * BeforeFileter()のテスト
 * - entry_keyアクション
 *
 * @return void
 */
	public function testActionEntryKey() {
		//事前準備
		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'Session' => array('read', 'write', 'delete'),
		)));

		$exactly = 1;
		$this->controller->Components->Session
			->expects($this->exactly($exactly))->method('read')
			->will($this->returnCallback(function ($key) {
				if ($key === 'AutoUserRegistKey') {
					return false;
				} else {
					return null;
				}
			}));
		$this->controller->Components->Session
			->expects($this->once())->method('delete')->with('AutoUserRegistKey');
		$this->controller->Components->Session
			->expects($this->once())->method('write')->with('AutoUserRegistRedirect', 'request');

		//テスト実行
		$this->_testGetAction(array('action' => 'entry_key'), array('method' => 'assertNotEmpty'), null, 'view');

		//チェック
		$this->__assert();
	}

/**
 * BeforeFileter()のテスト
 * - confirmアクション
 *
 * @return void
 */
	public function testActionConfirm() {
		//事前準備
		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'Session' => array('read', 'write'),
		)));

		$exactly = 1;
		$this->controller->Components->Session
			->expects($this->exactly($exactly))->method('read')
			->will($this->returnValue(null));

		$this->controller->Components->Session
			->expects($this->once())->method('write')->with('AutoUserRegistRedirect', 'confirm');

		//テスト実行
		$this->_testGetAction(array('action' => 'confirm'), array('method' => 'assertNotEmpty'), null, 'view');

		//チェック
		$this->__assert();
	}

/**
 * テストの評価
 *
 * @return void
 */
	private function __assert() {
		//チェック
		$this->assertEquals($this->vars['pageTitle'], __d('auth', 'Sign up'));

		$expected = array(
			'navibar' => array(
				'entry_key' => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'entry_key',
					),
					'label' => array('auth', 'Entry secret key?'),
				),
				'request' => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'request',
					),
					'label' => array(
						0 => 'auth', 1 => 'Registration?',
					),
				),
				'confirm' => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'confirm',
					),
					'label' => array(
						0 => 'auth', 1 => 'Entry confirm.',
					),
				),
				'completion' => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'update',
					),
					'label' => array(
						0 => 'auth', 1 => 'Complete registration.',
					),
				),
			),
			'cancelUrl' => null,
		);
		$this->assertEquals($this->controller->helpers['NetCommons.Wizard'], $expected);

		$this->assertEquals($this->controller->params['action'], 'entry_key');
	}

}
