<?php
/**
 * AuthController::login()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');
App::uses('TestAuthGeneral', 'AuthGeneral.TestSuite');

/**
 * AuthController::login()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\AuthController
 */
class AuthControllerLoginTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array();

/**
 * Plugin name
 *
 * @var array
 */
	public $plugin = 'auth';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'auth';

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		TestAuthGeneral::logout($this);
	}

/**
 * ログイン状態と判定させるMock生成する
 *
 * @return void
 */
	protected function _mockLoggedIn() {
		$this->generateNc(Inflector::camelize($this->_controller),
			array(
				'components' => array(
					'Auth' => array('login'),
				),
				'uses' => array(
					'Users.User' => array('updateLoginTime'),
				)
			)
		);

		if (CakePlugin::loaded('Nc2ToNc3')) {
			CakePlugin::unload('Nc2ToNc3');
		}
		$this->controller->Auth
			->expects($this->once())
			->method('login')
			->will($this->returnCallback(function () {
				TestAuthGeneral::login($this);
				return true;
			}));
	}

/**
 * ログインのテスト(GET)
 *
 * @return void
 */
	public function testLoginOnGet() {
		$this->_testNcAction('/auth/auth/login', array(
			'method' => 'get'
		));
		$this->assertEqual('login', $this->controller->view);
	}

/**
 * ログインのテスト(GET)
 *
 * @return void
 */
	public function testLoginByShortActionOnGet() {
		$this->_testNcAction('/auth/login', array(
			'method' => 'get'
		));
		$this->assertEqual('login', $this->controller->view);
	}

/**
 * POST用DataProvider
 *
 * #### 戻り値
 *  - data: リクエストdata
 *
 * @return array
 */
	public function dataProvider() {
		$results = array();

		//POSTデータ
		$results[0] = array(
			'User' => array(
				'username' => 'admin',
				'password' => 'admin',
			),
		);
		return $results;
	}

/**
 * ログインのテスト
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLogin($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		//ログイン状態と判定させるMock生成
		$this->_mockLoggedIn();

		$this->_testNcAction('/auth/auth/login', array(
			'method' => 'post',
			'data' => $data
		));

		$this->assertTrue($this->controller->Auth->loggedIn());
	}

/**
 * ログインのテスト
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLoginByShortAction($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		//ログイン状態と判定させるMock生成
		$this->_mockLoggedIn();

		$this->_testNcAction('/auth/login', array(
			'method' => 'post',
			'data' => $data
		));

		$this->assertTrue($this->controller->Auth->loggedIn());
	}

/**
 * ログインのテスト
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLoginError($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		$this->_testNcAction('/auth/auth/login', array(
			'method' => 'post',
			'data' => $data
		));

		$this->assertFalse($this->controller->Auth->loggedIn());
		$this->assertEquals('login', $this->controller->view);
	}

/**
 * ログインのテスト(Auth%sAppController::_getAuthenticatorの読み込み)
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLoginCalleeLogin($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		//ログイン状態と判定させるMock生成
		$this->_mockLoggedIn();

		$this->_testNcAction('/auth_general/auth_general/login', array(
			'method' => 'post',
			'data' => $data,
		));
		$this->assertTrue($this->controller->Auth->loggedIn());
	}

}
