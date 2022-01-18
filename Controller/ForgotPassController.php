<?php
/**
 * パスワード再発行Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthAppController', 'Auth.Controller');
App::uses('NetCommonsMail', 'Mails.Utility');
App::uses('SiteSettingUtil', 'SiteManager.Utility');
App::uses('UserAttributeChoice', 'UserAttributes.Model');

/**
 * パスワード再発行Controller
 *
 * @property SecurityComponent $Security
 * @property ForgotPass $ForgotPass
 * @property User $User
 * @property SiteSetting $SiteSetting
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 */
class ForgotPassController extends AuthAppController {

/**
 * ウィザード定数(再発行の受付)
 *
 * @var string
 */
	const WIZARD_REQUEST = 'request';

/**
 * ウィザード定数(再発行受付確認画面)
 *
 * @var string
 */
	const WIZARD_CONFIRM = 'confirm';

/**
 * ウィザード定数(新しいパスワード登録)
 *
 * @var string
 */
	const WIZARD_UPDATE = 'update';

/**
 * 使用するComponents
 *
 * - [SecurityComponent](http://book.cakephp.org/2.0/ja/core-libraries/components/security-component.html)
 *
 * @var array
 */
	public $components = array(
		'Security',
	);

/**
 * 使用するModels
 *
 * - [Auth.ForgotPass](../../Auth/classes/ForgotPass.html)
 * - [Users.User](../../Users/classes/User.html)
 *
 * @var array
 */
	public $uses = array(
		'Auth.ForgotPass',
		'Users.User',
		'SiteManager.SiteSetting',
	);

/**
 * 使用するHelpers
 *
 * - [NetCommons.Wizard](../../NetCommons/classes/WizardHelper.html)
 *
 * @var array
 */
	public $helpers = array(
		'NetCommons.Wizard' => array(
			'navibar' => array(
				self::WIZARD_REQUEST => array(
					'url' => array(
						'controller' => 'forgot_pass', 'action' => 'request',
					),
					'label' => array('auth', 'Forgot your Password?'),
				),
				self::WIZARD_CONFIRM => array(
					'url' => array(
						'controller' => 'forgot_pass', 'action' => 'confirm',
					),
					'label' => array('auth', 'Authorization key confirm?'),
				),
				self::WIZARD_UPDATE => array(
					'url' => array(
						'controller' => 'forgot_pass', 'action' => 'update',
					),
					'label' => array('auth', 'Entry new password'),
				),
			),
			'cancelUrl' => array('controller' => 'auth', 'action' => 'login')
		),
	);

/**
 * beforeFilter
 *
 * @return void
 **/
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('request', 'confirm', 'update');

		//ページタイトル
		$this->set('pageTitle', __d('auth', 'Forgot your Password?'));

		SiteSettingUtil::setup('ForgotPass');
		if (! SiteSettingUtil::read('ForgotPass.use_password_reissue', '0')) {
			return $this->setAction('throwBadRequest');
		}

		//メール通知の場合、NetCommonsMailUtilityをメンバー変数にセットする。Mockであれば、newをしない。
		//テストでMockに差し替えが必要なための処理であるので、カバレッジレポートから除外する。
		//@codeCoverageIgnoreStart
		if (empty($this->mail) ||
				substr(get_class($this->mail), 0, 4) !== 'Mock') {
			$this->mail = new NetCommonsMail();
		}
		//@codeCoverageIgnoreEnd
	}

/**
 * パスワード再発行の受付
 *
 * @return void
 **/
	public function request() {
		if ($this->request->is('post')) {
			$forgotPass = $this->ForgotPass->validateRequest($this->request->data);
			if ($forgotPass) {
				$forgotPass = $forgotPass['ForgotPass'];
				$this->Session->write('ForgotPass', $forgotPass);

				//対象のユーザがいる場合は、メール送る。
				//成否を出すと、悪意ある人がやった場合、メールアドレスがバレてしまうため、送ったことにする。
				//⇒有名どころのWebサービスでは、エラーを表示している。
				//　かなり昔では、送らない方が良かったかもしれないが、新規登録などで既に登録されているなどの
				//　エラーを返すことから、この回避方法は意味がないため、エラーを返すようにする。
				if (Hash::get($forgotPass, 'user_id')) {
					$this->mail->mailAssignTag->setFixedPhraseSubject(
						SiteSettingUtil::read('ForgotPass.issue_mail_subject')
					);
					$this->mail->mailAssignTag->setFixedPhraseBody(
						SiteSettingUtil::read('ForgotPass.issue_mail_body')
					);
					$this->mail->mailAssignTag->assignTags(array(
						'X-AUTHORIZATION_KEY' => Hash::get($forgotPass, 'authorization_key'),
					));
					$this->mail->mailAssignTag->initPlugin(Current::read('Language.id'));
					$this->mail->initPlugin(Current::read('Language.id'));

					$this->mail->to(Hash::get($forgotPass, 'email'));
					$this->mail->setFrom(Current::read('Language.id'));
					if (! $this->mail->sendMailDirect()) {
						return $this->NetCommons->handleValidationError(array('SendMail Error'));
					}
				}

				$this->NetCommons->setFlashNotification(
					__d(
						'auth',
						'We have sent you the key to obtain a new password to your registered e-mail address.'
					),
					array('class' => 'success')
				);

				return $this->redirect('/auth/forgot_pass/confirm');
			}
			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		} else {
			$this->request->data['ForgotPass']['email'] = Hash::get($this->request->query, 'email');
		}
	}

/**
 * パスワード再発行
 *
 * @return void
 **/
	public function confirm() {
		if ($this->request->is('post')) {
			if ($this->ForgotPass->validateAuthorizationKey($this->request->data)) {
				$this->Session->write('ForgotPass.isValidAuthorizationKey', true);
				$forgotPass = $this->Session->read('ForgotPass');

				$this->mail->mailAssignTag->setFixedPhraseSubject(
					SiteSettingUtil::read('ForgotPass.request_mail_subject')
				);
				$this->mail->mailAssignTag->setFixedPhraseBody(
					SiteSettingUtil::read('ForgotPass.request_mail_body')
				);
				$this->mail->mailAssignTag->assignTags(array(
					'X-HANDLENAME' => $forgotPass['handlename'],
					'X-USERNAME' => $forgotPass['username'],
				));
				$this->mail->mailAssignTag->initPlugin(Current::read('Language.id'));
				$this->mail->initPlugin(Current::read('Language.id'));

				$this->mail->to(Hash::get($forgotPass, 'email'));
				$this->mail->setFrom(Current::read('Language.id'));
				if (! $this->mail->sendMailDirect()) {
					return $this->NetCommons->handleValidationError(array('SendMail Error'));
				}

				$this->NetCommons->setFlashNotification(
					__d('auth', 'We have sent your login id to your registered e-mail address.'),
					array('class' => 'success')
				);
				return $this->redirect('/auth/forgot_pass/update');
			}
			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		}
	}

/**
 * パスワード登録
 *
 * @return void
 **/
	public function update() {
		if ($this->Session->read('ForgotPass.isValidAuthorizationKey') !== true) {
			$this->Session->delete('ForgotPass');
			return $this->throwBadRequest();
		}

		if ($this->request->is('put')) {
			// 承認済ならば、利用可へ
			$userId = $this->request->data[$this->User->alias]['id'] ?? null;
			$user = $this->User->find('first', array(
				'recursive' => -1,
				'conditions' => array('id' => $userId)
			));

			if ($user && $user[$this->User->alias]['status'] === UserAttributeChoice::STATUS_CODE_APPROVED) {
				$this->request->data[$this->User->alias]['status']
					= UserAttributeChoice::STATUS_CODE_ACTIVE;
			}

			if ($this->ForgotPass->savePassowrd($this->request->data)) {
				$this->NetCommons->setFlashNotification(
					__d('net_commons', 'Successfully saved.'), array('class' => 'success')
				);
				$this->Session->delete('ForgotPass');

				$this->Auth->authenticate['all']['scope'] = array(
					'User.status' => '1'
				);
				if ($this->Auth->login()) {
					$this->User->updateLoginTime($this->Auth->user('id'));
					Current::write('User', $this->Auth->user());
					$this->Auth->loginRedirect = $this->SiteSetting->getDefaultStartPage();
					return $this->redirect($this->Auth->redirect());
				}

				return $this->redirect('/auth/auth/login');
			}

			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		} else {
			$this->request->data['User']['id'] = $this->Session->read('ForgotPass.user_id');
		}
	}
}
