<?php
/**
 * ログインテンプレート
 *
 * 独自ログインテンプレートを使いたい場合、
 * AuthXxxx/View/Element/login.ctpファイル作成すれば、自動的に読み込む
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @author Kazunori Sakamoto <exkazuu@willbooster.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
echo $this->NetCommonsHtml->script('/auth/js/auth.js');
echo $this->element('Auth.meta');
?>

<h2>
	<?php echo __d('auth', 'Login'); ?>
</h2>

<?php echo $this->element('NetCommons.javascript_alert'); ?>
<?php foreach ($authenticators as $plugin): ?>
	<article class="panel panel-default" ng-controller="Auth" ng-init="initialize('<?php echo Inflector::camelize($plugin); ?>')" ng-cloak>
		<?php if ($plugin !== 'auth_general') : ?>
			<div class="panel-heading">
				<strong>
					<?php echo __d($plugin, Inflector::humanize($plugin)); ?>
				</strong>
			</div>
		<?php endif; ?>

		<?php if ($this->elementExists(Inflector::camelize($plugin) . '.login')) : ?>
			<?php echo $this->element(Inflector::camelize($plugin) . '.login', array(
				'plugin' => $plugin,
			)); ?>

		<?php else : ?>
			<?php echo $this->NetCommonsForm->create('User', array(
					'id' => Inflector::camelize($plugin),
					'url' => array(
						'plugin' => $plugin,
						'controller' => $plugin,
						'action' => 'login')
				)
			); ?>

				<div class="panel-body">
					<?php echo $this->NetCommonsForm->input('username', array(
						'label' => __d('auth', 'Username'),
						'placeholder' => __d('auth', 'Please enter your username.'),
						'required' => true,
						'class' => 'form-control allow-submit',
					)); ?>

					<?php echo $this->NetCommonsForm->input('password', array(
						'label' => __d('auth', 'Password'),
						'placeholder' => __d('auth', 'Please enter your password.'),
						'required' => true,
						'class' => 'form-control allow-submit',
					)); ?>

					<button class="btn btn-primary btn-block" type="submit">
						<?php echo __d('auth', 'Login'); ?>
					</button>

					<hr>

					<?php if ($isMailSend && ! SiteSettingUtil::read('App.close_site') && SiteSettingUtil::read('ForgotPass.use_password_reissue')) : ?>
						<div>
							<?php echo $this->NetCommonsHtml->link(
									__d('auth', 'Forgot your Password? Please click here.'),
									array('plugin' => 'auth', 'controller' => 'forgot_pass', 'action' => 'request')
								); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php echo $this->NetCommonsForm->end(); ?>
		<?php endif; ?>
	</article>
<?php endforeach;
