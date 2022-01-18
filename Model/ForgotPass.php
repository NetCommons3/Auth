<?php
/**
 * パスワード再発行Model
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppModel', 'Model');
App::uses('UserAttributeChoice', 'UserAttributes.Model');

/**
 * パスワード再発行Model
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Model
 */
class ForgotPass extends AppModel {

/**
 * 認証キー用のランダム文字列
 * ※テストで書き換えるため、constではなく、メンバ変数とする
 *
 * @var string
 */
	public $randamstr = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#$%=-~+*?@_';

/**
 * テーブル名
 *
 * @var bool
 */
	public $useTable = false;

/**
 * 使用するBehaviors
 *
 * - [Mails.MailQueueBehavior](../../Mails/classes/MailQueueBehavior.html)
 *
 * @var array
 */
	public $actsAs = array(
		'Mails.IsMailSend',
		'Mails.MailQueue' => array(),
	);

/**
 * Called during validation operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		$forgotPass = CakeSession::read('ForgotPass');
		if (! $forgotPass) {
			$forgotPass = array();
		}

		$this->validate = ValidateMerge::merge($this->validate, array(
			'email' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => __d('net_commons', 'Please input %s.', __d('auth', 'email')),
					'required' => false
				),
				'email' => array(
					'rule' => array('email'),
					'message' => sprintf(
						__d('net_commons', 'Unauthorized pattern for %s. Please input the data in %s format.'),
						__d('auth', 'email'),
						__d('auth', 'email')
					)
				)
			),
			'authorization_key' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => __d('net_commons', 'Please input %s.', __d('auth', 'Authorization key')),
					'required' => false
				),
				'equalTo' => array(
					'rule' => array('equalTo', Hash::get($forgotPass, 'authorization_key')),
					'message' => __d('auth', 'Failed on validation errors. Please check the authorization key.'),
					'required' => false
				),
			),
		));

		return parent::beforeValidate($options);
	}

/**
 * パスワード再発行通知のチェック
 *
 * @param array $data リクエストデータ
 * @return mixed ForgotPassデータ配列
 */
	public function validateRequest($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//バリデーション
		$this->set($data);
		if (! $this->validates()) {
			return false;
		}

		//メールアドレスのチェック
		$email = trim($data['ForgotPass']['email']);
		// 削除済 or 承認待ち or 利用不可ならメール送信しない
		$conditions = array(
			'is_deleted' => false,
			'status' => [UserAttributeChoice::STATUS_CODE_ACTIVE, UserAttributeChoice::STATUS_CODE_APPROVED]
		);

		$fields = $this->User->getEmailFields();
		foreach ($fields as $field) {
			$conditions['OR'][$field] = $email;
		}
		$user = $this->User->find('first', array(
			'recursive' => -1,
			'conditions' => $conditions,
		));

		if (empty($user['User']['id'])) {
			$this->invalidate(
				'email',
				__d('auth', 'The email address entered is invalid.')
			);
			return false;
		}

		$forgotPass = $this->create(array(
			'user_id' => Hash::get($user, 'User.id', '0'),
			'username' => Hash::get($user, 'User.username'),
			'handlename' => Hash::get($user, 'User.handlename'),
			'authorization_key' => substr(str_shuffle($this->randamstr), 0, 10),
			'email' => $email
		));

		return $forgotPass;
	}

/**
 * パスワード再発行処理
 *
 * @param array $data リクエストデータ
 * @return bool
 * @throws InternalErrorException
 */
	public function validateAuthorizationKey($data) {
		$forgotPass = CakeSession::read('ForgotPass');

		//バリデーション
		$this->set($data);
		if (! $this->validates()) {
			return false;
		}
		$data['ForgotPass']['authorization_key'] = trim($data['ForgotPass']['authorization_key']);
		if (! $forgotPass || ! Hash::get($forgotPass, 'user_id')) {
			$this->invalidate(
				'authorization_key',
				__d('auth', 'Failed on validation errors. Please check the authorization key.')
			);
			return false;
		}

		return true;
	}

/**
 * パスワード再登録処理
 *
 * @param array $data リクエストデータ
 * @return bool
 * @throws InternalErrorException
 */
	public function savePassowrd($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		$forgotPass = CakeSession::read('ForgotPass');

		//トランザクションBegin
		$this->begin();

		//バリデーション
		if ($data['User']['username'] !== Hash::get($forgotPass, 'username')) {
			$this->User->invalidate(
				'username',
				__d('auth', 'Failed on validation errors. Please check the login id.')
			);
			return false;
		}

		$data['User']['id'] = Hash::get($forgotPass, 'user_id');
		unset($data['User']['username']);
		$this->User->set($data);
		if (! $this->User->validates(array('validatePassword' => true))) {
			$this->validationErrors = Hash::merge(
				$this->validationErrors, $this->User->validationErrors
			);
			return false;
		}

		try {
			//不要なビヘイビアを一時的にアンロードする
			$this->User->Behaviors->unload('Users.SaveUser');
			$this->User->Behaviors->unload('Files.Attachment');
			$this->User->Behaviors->unload('Users.Avatar');

			//Userデータの登録
			if (! $this->User->save(null, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			//不要なビヘイビアをロードし直す
			$this->User->Behaviors->load('Users.SaveUser');
			$this->User->Behaviors->load('Files.Attachment');
			$this->User->Behaviors->load('Users.Avatar');

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return true;
	}

}
