<?php
/**
 * ForgotPassController::confirm()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * ForgotPassController::confirm()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\ForgotPassController
 */
class ForgotPassControllerConfirmTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.auth.site_setting4auth',
		'plugin.auth.user4auth',
		'plugin.user_attributes.user_attribute_setting4test',
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
	protected $_controller = 'forgot_pass';

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
 * confirm()アクションのGetリクエストテスト
 *
 * @return void
 */
	public function testConfirmGet() {
		//テスト実行
		$this->_testGetAction(array('action' => 'confirm'), array('method' => 'assertNotEmpty'), null, 'view');

		//チェック
		$this->assertInput('input', 'data[ForgotPass][authorization_key]', null, $this->view);
	}

/**
 * POSTリクエストテストの事前準備
 *
 * @param bool $success 成否
 * @return void
 */
	private function __prepareConfirmPost($success) {
		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'Session' => array('read'),
			'NetCommons.NetCommons' => array('handleValidationError', 'setFlashNotification')
		)));

		$this->controller->mail = $this->getMock(
			'NetCommonsMail',
			array('sendMailDirect', 'initPlugin', 'to', 'setFrom'),
			array(), '', false
		);
		$this->controller->mail->mailAssignTag = $this->getMock(
			'NetCommonsMail',
			array('setFixedPhraseSubject', 'setFixedPhraseBody', 'initPlugin', 'assignTags'),
			array(), '', false
		);

		if ($success === true) {
			$exactly = 1;
			$this->controller->Components->Session
				->expects($this->exactly($exactly))->method('read')
				->will($this->returnCallback(function ($key) {
					if ($key === 'ForgotPass') {
						return array(
							'user_id' => '2',
							'username' => 'site_manager',
							'handlename' => 'Site Manager',
							'authorization_key' => 'test@test',
							'email' => 'site_manager@exapmle.com'
						);
					} else {
						return null;
					}
				}));

			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('setFixedPhraseSubject')
				->with('ForgotPass.request_mail_subject 2');
			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('setFixedPhraseBody')
				->with('ForgotPass.request_mail_body 2');
			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('assignTags')
				->with(array('X-HANDLENAME' => 'Site Manager', 'X-USERNAME' => 'site_manager'));
			$this->controller->mail->mailAssignTag
				->expects($this->once())->method('initPlugin')
				->with('2');

			$this->controller->mail
				->expects($this->once())->method('initPlugin')
				->with('2');
			$this->controller->mail
				->expects($this->once())->method('to')
				->with('site_manager@exapmle.com');
			$this->controller->mail
				->expects($this->once())->method('setFrom')
				->with('2');
		} else {
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('setFixedPhraseSubject')
				->will($this->returnValue(false));
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('setFixedPhraseBody')
				->will($this->returnValue(false));
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('assignTags')
				->will($this->returnValue(false));
			$this->controller->mail->mailAssignTag
				->expects($this->exactly(0))->method('initPlugin')
				->will($this->returnValue(false));

			$this->controller->mail
				->expects($this->exactly(0))->method('initPlugin')
				->will($this->returnValue(false));
			$this->controller->mail
				->expects($this->exactly(0))->method('to')
				->will($this->returnValue(false));
			$this->controller->mail
				->expects($this->exactly(0))->method('setFrom')
				->will($this->returnValue(false));
		}
	}

/**
 * confirm()アクションのPOSTリクエストテスト
 * - 正常
 *
 * @return void
 */
	public function testConfirmPost() {
		//事前準備
		$this->__prepareConfirmPost(true);
		$this->_mockForReturnTrue('Auth.ForgotPass', 'validateAuthorizationKey');

		$this->controller->mail
			->expects($this->once())->method('sendMailDirect')
			->will($this->returnValue(true));

		$this->controller->Components->NetCommons
			->expects($this->once())->method('setFlashNotification')
			->with(
				__d('auth', 'We have sent your login id to your registered e-mail address.'),
				array('class' => 'success')
			);

		//テスト実行
		$data = array('ForgotPass' => array('authorization_key' => 'test@test'));
		$this->_testPostAction('post', $data, array('action' => 'confirm'), null, 'view');

		//チェック
		$header = $this->controller->response->header();
		$pattern = '/auth/forgot_pass/update';
		$this->assertTextContains($pattern, $header['Location']);
	}

/**
 * confirm()アクションのPOSTリクエストテスト
 * - sendMailDirect()エラーテスト
 *
 * @return void
 */
	public function testConfirmPostOnSendError() {
		//事前準備
		$this->__prepareConfirmPost(true);
		$this->_mockForReturnTrue('Auth.ForgotPass', 'validateAuthorizationKey');

		$this->controller->mail
			->expects($this->once())->method('sendMailDirect')
			->will($this->returnValue(false));
		$this->controller->Components->NetCommons
			->expects($this->once())->method('handleValidationError')
			->with(array('SendMail Error'));

		$this->controller->Components->NetCommons
			->expects($this->exactly(0))->method('setFlashNotification')
			->will($this->returnValue(false));

		//テスト実行
		$data = array('ForgotPass' => array('authorization_key' => 'test@test'));
		$this->_testPostAction('post', $data, array('action' => 'confirm'), null, 'view');
	}

/**
 * confirm()アクションのPOSTリクエストテスト
 * - validationError
 *
 * @return void
 */
	public function testConfirmPostValidationError() {
		//事前準備
		$this->__prepareConfirmPost(false);

		$this->generateNc(Inflector::camelize($this->_controller), array('components' => array(
			'NetCommons.NetCommons' => array('handleValidationError', 'setFlashNotification')
		)));
		$this->controller->mail = $this->getMock(
			'NetCommonsMail',
			array('sendMailDirect', 'initPlugin', 'to', 'setFrom'),
			array(), '', false
		);

		$this->controller->mail
			->expects($this->exactly(0))->method('sendMailDirect')
			->will($this->returnValue(false));

		$this->controller->Components->NetCommons
			->expects($this->once())->method('handleValidationError')
			->with(
				array(
					'authorization_key' => array(__d('net_commons', 'Please input %s.', __d('auth', 'Authorization key')))
				)
			);

		//テスト実行
		$data = array('ForgotPass' => array('authorization_key' => ''));
		$this->_testPostAction('post', $data, array('action' => 'confirm'), null, 'view');

		//チェック
		$expected = '<div class="has-error"><div class="help-block">' .
						__d('net_commons', 'Please input %s.', __d('auth', 'Authorization key')) .
					'</div></div>';
		$this->assertTextContains($expected, $this->view);
	}

}
