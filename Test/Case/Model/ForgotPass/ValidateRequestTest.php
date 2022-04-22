<?php
/**
 * ForgotPass::validateRequest()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsModelTestCase', 'NetCommons.TestSuite');

/**
 * ForgotPass::validateRequest()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Model\ForgotPass
 */
class ForgotPassValidateRequestTest extends NetCommonsModelTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
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
 * Model name
 *
 * @var string
 */
	protected $_modelName = 'ForgotPass';

/**
 * Method name
 *
 * @var string
 */
	protected $_methodName = 'validateRequest';

/**
 * validateRequest()テストのDataProvider
 *
 * ### 戻り値
 *  - email メールアドレス
 *  - expected 期待値
 *
 * @return array データ
 */
	public function dataProvider() {
		//0: emailが一致する場合
		$index = 0;
		$result[$index] = array();
		$result[$index]['email'] = 'site_manager@exapmle.com';
		$result[$index]['expected'] = array(
			'ForgotPass' => array(
				'user_id' => '2',
				'username' => 'site_manager',
				'handlename' => 'Site Manager',
				'authorization_key' => 'a',
				'email' => $result[$index]['email'],
			)
		);

		//1: mobile_emailが一致する場合
		$index = 1;
		$result[$index] = array();
		$result[$index]['email'] = 'system_admin_2@exapmle.com';
		$result[$index]['expected'] = array(
			'ForgotPass' => array(
				'user_id' => '1',
				'username' => 'system_administrator',
				'handlename' => 'System Administrator',
				'authorization_key' => 'a',
				'email' => $result[$index]['email'],
			)
		);

		//2: 存在しないメールアドレス
		$index = 2;
		$result[$index] = array();
		$result[$index]['email'] = 'aaaaa@exapmle.com';
		$result[$index]['expected'] = false;

		//3: 削除されたユーザ、emailアドレス
		$index = 3;
		$result[$index] = array();
		$result[$index]['email'] = 'deleted_1@exapmle.com';
		$result[$index]['expected'] = false;

		//4: 削除されたユーザ、mobile_emailアドレス
		$index = 4;
		$result[$index] = array();
		$result[$index]['email'] = 'deleted_2@exapmle.com';
		$result[$index]['expected'] = false;

		//5: validationエラー
		$index = 5;
		$result[$index] = array();
		$result[$index]['email'] = 'aaaaaa';
		$result[$index]['expected'] = false;

		return $result;
	}

/**
 * validateRequest()のテスト
 *
 * @param string $email メールアドレス
 * @param array|bool $expected 期待値
 * @dataProvider dataProvider
 * @return void
 */
	public function testValidateRequest($email, $expected) {
		$model = $this->_modelName;
		$methodName = $this->_methodName;

		//データ生成
		$data = array(
			'ForgotPass' => array('email' => $email)
		);

		//テスト実施
		$this->$model->randamstr = 'a';
		$result = $this->$model->$methodName($data);

		//チェック
		$this->assertEquals($result, $expected);
	}

}
