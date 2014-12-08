<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
	public $components = array(
		'RequestHandler',
		'Email',
		'DebugKit.Toolbar',
		'Session',
		'Cookie' => array(
			'key' => '?t]09u7!6Z#@|#4MKg>s&5=6y0lazuPPn`Z3G238g37V50xBIwUnv8kE1K7kvD"',
			'name' => 'lboxCookie',
			'type' => 'rijndael', //AES encryptation
		),
		'Auth' => array(
			'loginRedirect' => array(
				'controller' => 'pages',
				'action' => 'index'
			),
			'logoutRedirect' => array(
				'controller' => 'users',
				'action' => 'login'
			),
			'authenticate' => array(
				'Form' => array(
					'passwordHasher' => 'Blowfish',
					'userModel' => 'User',
					'fields' => array(
						'username' => 'email',
						'password' => 'password'
					),
				),
				'Cookie' => array(
					'passwordHasher' => 'Blowfish',
					'userModel' => 'User',
					'fields' => array(
						'username' => 'email',
						'password' => 'password'
					),
					'crypt' => 'rijndael', // Defaults to rijndael(safest), optionally set to 'cipher' if required
					'cookie' => array(
						'name' => 'RememberMe',
						'time' => '+1 month',
					)
				)

			)
		)
	);

	public function beforeFilter() {
		//Permite ver sin loguearse los siguientes métodos de todos los controladores
		// $this->Auth->allow('index', 'view');

	}

}
