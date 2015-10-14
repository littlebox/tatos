<?php
App::uses('AppController', 'Controller');
/**
 * Reserves Controller
 *
 * @property Reserve $Reserve
 * @property PaginatorComponent $Paginator
 */
class ReservesController extends AppController {

	public $components = array('Paginator');

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('api_add','mp_notification');
	}


	public function index() {
		$this->layout = 'metrobox';
		if (empty($this->Auth->user('winery_id'))) {
			throw new NotFoundException(__('Missed Winery ID in Argument'));
		}
		$wineryId = $this->Auth->user('winery_id');
		$this->loadModel('Winery', 'Language');
		if (!$this->Winery->exists($wineryId)) {
			throw new NotFoundException(__('Invalid Winery'));
		}
		$this->Winery->id = $wineryId;
		//To show Winery's Tours in view
		$tours = $this->Winery->Tour->find('list', array('contain' => false, 'conditions' => array('winery_id' => $wineryId)));
		$this->set('tours', $tours);
		//To use Tour's Lnaguajes and Reserves in view
		$toursData = $this->Winery->Tour->find('all', array('contain' => array('Language', 'Reserve', 'Time', 'Day'), 'conditions' => array('winery_id' => $wineryId)));
		$this->set('toursData', $toursData);
		//debug($toursData);die();
	}

	public function getQuotaAvailable($date, $tourId){
		//Render always as json
		$this->RequestHandler->renderAs($this, 'json');

		$options = array('conditions' => array('Tour.id' => $tourId), 'contain' => array('Time'), 'fields' => array('id'));
		$tour = $this->Winery->Tour->find('first', $options);

		foreach ($tour['Time'] as &$time) {
			$timeHour = $time['hour'];
			//Query to calculate quota available of tour un specific date y specific
			$query = $this->Winery->Tour->Time->query("SELECT (tours.quota - (SELECT COALESCE(SUM(reserves.number_of_adults)+SUM(reserves.number_of_minors), 0) FROM reserves WHERE reserves.tour_id = $tourId AND reserves.date = '$date' AND reserves.time = '$timeHour')) AS quota_available FROM tours WHERE tours.id = $tourId");
			$time['quota_available'] = $query[0][0]['quota_available'];
		}

		$this->set(compact('tour')); // Pass $data to the view
		$this->set('_serialize', 'tour'); // Let the JsonView class know what variable to use

	}

	public function add() {
		$this->request->allowMethod('ajax'); //Call only with .json at end on url

		//Check if request is post or put
		if ($this->request->is('post') || $this->request->is('put')) {

			if (!$this->Reserve->Tour->exists($this->request->data['Reserve']['tour_id'])) {
				throw new NotFoundException(__('Invalid Tour'));
			}

			$this->requestAction(Router::url(array('controller'=>'tours', 'action'=>'tourSecurityCheck')).'/'.$this->request->data['Reserve']['tour_id']);

			$data = array(
				'content' => '',
				'reserve' => '',
				'error' => '',
			);

			//Convert date d/m/Y to Y-m-d format tosave in DB
			$this->request->data['Reserve']['date'] = DateTime::createFromFormat('d/m/Y', $this->request->data['Reserve']['date'])->format('Y-m-d');

			if(!empty($this->request->data['Client']['birth_date'])){
				$this->request->data['Client']['birth_date'] = DateTime::createFromFormat('d/m/Y', $this->request->data['Client']['birth_date'])->format('Y-m-d');
			}


			//if the client exist, put the id in the request data array
			// if(!empty($client = $this->Reserve->Client->find('first', array('conditions' => array('Client.email' => $this->request->data['Client']['email']), 'contain' => false)))){
			// 	//WARING!! All Client data will be overwritten!!
			// 	$this->request->data['Client']['id'] = $client['Client']['id'];
			// }

			//debug($this->request->data);debug($client);die();
			$this->Reserve->create();
			if ($this->Reserve->saveAssociated($this->request->data)) {
				$data['content']['title'] = __('Good.');
				$data['content']['text'] = __('The reserve has been saved.');

				//Build the title for show reserve
				$title = '';
				$title = $title.$this->request->data['Client']['full_name'];
				$title = $title.' ('.$this->request->data['Reserve']['number_of_adults'].'a';
				if($this->request->data['Reserve']['number_of_minors'] > 0){
					$title = $title.' '.$this->request->data['Reserve']['number_of_minors'].'m';
				}
				$title = $title.')';

				//Bring tour for color
				$tour = $this->Reserve->Tour->find('first', array('fields' => array('color'), 'conditions' => array('Tour.id' => $this->request->data['Reserve']['tour_id'])));

				//Prepare array to show new reserve in view
				$data['reserve']['id'] = $this->Reserve->id;
				$data['reserve']['title'] = $title;
				$data['reserve']['start'] = $this->request->data['Reserve']['date'].' '.$this->request->data['Reserve']['time'];
				$data['reserve']['tour'] = $this->request->data['Reserve']['tour_id'];
				$data['reserve']['language'] = $this->request->data['Reserve']['language_id'];
				$data['reserve']['date'] = $this->request->data['Reserve']['date'];
				$data['reserve']['time'] = $this->request->data['Reserve']['time'];
				$data['reserve']['clientEmail'] = $this->request->data['Client']['email'];
				$data['reserve']['clientName'] = $this->request->data['Client']['full_name'];
				$data['reserve']['clientBirthDate'] = $this->request->data['Client']['birth_date'];
				$data['reserve']['clientCountry'] = $this->request->data['Client']['country'];
				$data['reserve']['clientPhone'] = $this->request->data['Client']['phone'];
				$data['reserve']['numberOfAdults'] = $this->request->data['Reserve']['number_of_adults'];
				$data['reserve']['numberOfMinors'] = $this->request->data['Reserve']['number_of_minors'];
				$data['reserve']['note'] = $this->request->data['Reserve']['note'];
				$data['reserve']['referer'] = $this->request->data['Reserve']['referer'];
				$data['reserve']['backgroundColor'] = $tour['Tour']['color'];
			} else {
				// debug($this->Reserve->validationErrors); die();
				$data['error'] = __('The reserve could not be saved. Please, try again.');
			}
		}

		$this->set(compact('data')); // Pass $data to the view
		$this->set('_serialize', 'data'); // Let the JsonView class know what variable to use
	}

	public function api_add() {
		// $this->request->allowMethod('ajax'); //Only Ajax
		header('Access-Control-Allow-Origin:*');

		//Render always as json
		$this->RequestHandler->renderAs($this, 'json');

		//Check if request is post or put
		if ($this->request->is('post') || $this->request->is('put')) {

			//Prepare response array
			$data = array(
				'content' => '',
				'error' => array(),
			);
			$hasError = false;

			//Decode all data recived
			$json = json_decode($this->request->data['json'], true);
			// debug($json);die();


			$this->request->data['Client'] = [];
			$this->request->data['Client']['email'] = $json['personalData']['email'];
			$this->request->data['Client']['full_name'] = $json['personalData']['fullName'];
			$this->request->data['Client']['birth_date'] = $json['personalData']['birthDate'];
			$this->request->data['Client']['country'] = $json['personalData']['country'];
			$this->request->data['Client']['phone'] = $json['personalData']['phone'];
			// $this->request->data['Client']['lodging'] = $json['personalData']['lodging'];

			$this->request->data['Reserve'] = [];
			$this->request->data['Reserve']['date'] = $json['reserves']['date'];
			$this->request->data['Reserve']['language_id'] = $json['reserves']['language'];
			$this->request->data['Reserve']['number_of_adults'] = $json['reserves']['adults'];
			$this->request->data['Reserve']['number_of_minors'] = $json['reserves']['minors'];
			$this->request->data['Reserve']['referer'] = 'Web Wineobs';
			$this->request->data['Reserve']['from_web'] = true;

			//TODO: Check quota available for each tour


			$items = [];
			$newIds = [];
			require_once(APP.'Vendor/mercadopago-sdk/lib/mercadopago.php');
			$mp = new MP('8915881018899740', 'VFVdIwFOZQLabpCDnN6AvgbTzVT2mqju');
			$mp->sandbox_mode(true);

			foreach ($json['reserves']['tours'] as $tour) {
				$this->request->data['Reserve']['tour_id'] = $tour['id'];
				$this->request->data['Reserve']['time'] = $tour['time'];
				$this->request->data['Reserve']['mp_status'] = 'pending';

				$this->Reserve->create();

				if ($this->Reserve->saveAssociated($this->request->data)) {
					$newIds[] = $this->Reserve->id;
				}else{
					// debug($this->Reserve->validationErrors); die();
					$hasError = true;
					$data['error'][] = array(
						'tour' => $tour['id'],
						'time' => $tour['time'],
						'text' => __('The reserve could not be saved.')
					);
				}
				if(!$hasError){
					$data['content']['title'] = __('Good.');
					$data['content']['text'] = __('The reserves were been realized.');
				}
				$tourData = $this->Reserve->Tour->find('first',array(
					'conditions' => array(
						'Tour.id' => $tour['id'],
					),
					'contains' => false,
				));

				$price = $json['reserves']['adults']*$tourData['Tour']['price'] + $json['reserves']['minors']*$tourData['Tour']['minors_price'];

				$title = $tourData['Tour']['name'].' ('.$json['reserves']['adults'].' adultos';
				if($json['reserves']['minors'] > 0){
					$title .=', '.$json['reserves']['minors'].' menores';
				}
				$title .=')';

				array_push($items, array(
					"title" => $title,
					"currency_id" => "ARS", // Available currencies at: https://api.mercadopago.com/currencies
					"unit_price" => $price,
					"quantity" => 1,
				));
			}

			$preference_data = array(
				'items' => $items,
				'payer' => array(
					'name' => $this->request->data['Client']['full_name'],
					'email' => $this->request->data['Client']['email'],
				),
				'notification_url' => 'http://reservas.wineobs.com/reserves/mp_notification',
				"external_reference" => $newIds,
				"back_urls" => array(
					"success" => 'http://alpha.wineobs.com/payment_success',
					"pending" => 'http://alpha.wineobs.com/payment_pending',
					"failure" => 'http://alpha.wineobs.com/payment_failure',
				)
			);
			$preference = $mp->create_preference($preference_data);

			$data['mp_url'] = $preference['response']['sandbox_init_point'];

			$this->set(compact('data')); // Pass $data to the view
			$this->set('_serialize', 'data'); // Let the JsonView class know what variable to use
		}else{
			throw new MethodNotAllowedException(__('Only POST or PUT'));
		}

	}

	public function mp_notification(){
		require_once(APP.'Vendor/mercadopago-sdk/lib/mercadopago.php');
		$this->autoRender = false;

		$mp = new MP('8915881018899740', 'VFVdIwFOZQLabpCDnN6AvgbTzVT2mqju');
		$mp->sandbox_mode(true);
		$params = ["access_token" => $mp->get_access_token()];
		$payment_info = $mp->get_payment_info($_GET["id"]);

		file_put_contents(APP.'/mp_notifications.txt', json_encode($payment_info), FILE_APPEND);

		$ids = $payment_info['response']['collection']['external_reference'];
		foreach ($ids as &$id) {
			$reserve = $this->Reserve->find('first',array(
				'conditions' => array('Reserve.id' => $id),
			));
			$reserve['Reserve']['mp_status'] = $payment_info['response']['collection']['status'];
			$this->Reserve->save($reserve);
			//Possibles mp_status:
			// pending: El usuario aún no completó el proceso de pago.
			// approved: El pago fue aprobado y acreditado.
			// in_process: El pago está siendo revisado.
			// in_mediation: Los usuarios tienen iniciada una disputa.
			// rejected: El pago fue rechazado. El usuario puede intentar pagar nuevamente.
			// cancelled: El pago fue cancelado por una de las partes, o porque el tiempo expiró.
			// refunded: El pago fue devuelto al usuario.
			// charged_back: Fue hecho un contracargo en la tarjeta del pagador.
		}

		//Email to agent
		$Email = new CakeEmail();
		$Email->config('smtp'); //read settings from config/email.php
		$Email->emailFormat('html');
		$Email->to($payment_info['response']['collection']['payer']['email']);

		$Email->viewVars(array('estateCity' => $estate['Estate']['city']));
		$Email->viewVars(array('estateViewUrl' => Router::url(array('controller' => 'estates', 'action' => 'view', $estate['Estate']['id']), true)));
		$Email->viewVars(array('estateImageUrl' => $estateImageUrl));

		$Email->viewVars(array('client_name' => $payment_info['response']['collection']['payer']['first_name'].$payment_info['response']['collection']['payer']['last_name']));
		$Email->viewVars(array('payment_id' => $payment_info['response']['collection']['id']));

		// $reserves = $this->Reserve

		switch ($payment_info['response']['collection']['status']) {
			case "approved":
				//Enviar mail
				$Email->template('wineobs_payment_approved', 'wineobs');
				$Email->subject(__('Pago procesado'));
				break;
			case "pending":
				//Enviar mail
				$Email->template('wineobs_payment_pending', 'wineobs');
				$Email->subject(__('Pago pendiente'));
				break;
			case "in_process":
				//Enviar mail
				$Email->template('wineobs_payment_in_process', 'wineobs');
				$Email->subject(__('Procesando pago'));
				break;
			case "rejected":
				//Enviar mail
				$Email->template('wineobs_payment_rejected', 'wineobs');
				$Email->subject(__('Pago rechazado'));
				break;
			case "cancelled":
				//Enviar mail
				$Email->template('wineobs_payment_cancelled', 'wineobs');
				$Email->subject(__('Pago cancelado'));
				break;
			case "refunded":
				//Enviar mail
				$Email->template('wineobs_payment_refunded', 'wineobs');
				$Email->subject(__('Pago reintegrado'));
				break;
			case "charged_back":
				//Enviar mail
				$Email->template('wineobs_payment_charged_back', 'wineobs');
				$Email->subject(__('Pago reintegrado'));
				break;
		}

		$Email->send();
	}

	public function edit() {
		$this->request->allowMethod('ajax'); //Call only with .json at end on url

		if (!$this->request->is(array('post', 'put'))) {
			throw new MethodNotAllowedException(__('Only POST or PUT methods allowed.'));
		}

		if (!$this->Reserve->exists($this->request->data['Reserve']['id'])) {
			throw new NotFoundException(__('Invalid reserve'));
		}

		$this->reserveSecurityCheck($this->request->data['Reserve']['id']);

		$data = array(
			'content' => '',
			'reserve' => '',
			'error' => '',
		);

		$hasClientData = !empty($this->request->data['Client']);

		//if the client exist, put the id in the request data array
		if($hasClientData){
			if(!empty($client = $this->Reserve->Client->find('first', array('conditions' => array('Client.email' => $this->request->data['Client']['email']), 'contain' => false)))){
				//WARING!! All Client data will be overwritten!!
				$this->request->data['Client']['id'] = $client['Client']['id'];
			}
		}

		//Convert date d/m/Y to Y-m-d format tosave in DB
		$this->request->data['Reserve']['date'] = DateTime::createFromFormat('d/m/Y', $this->request->data['Reserve']['date'])->format('Y-m-d');
		if($hasClientData){
			if(!empty($this->request->data['Client']['birth_date'])){
				$this->request->data['Client']['birth_date'] = DateTime::createFromFormat('d/m/Y', $this->request->data['Client']['birth_date'])->format('Y-m-d');
			}
		}

		if ($this->Reserve->saveAssociated($this->request->data)) {
			$data['content']['title'] = __('Good.');
			$data['content']['text'] = __('The reserve has been saved.');

			if($hasClientData){

				//Build the title for show reserve
				$title = '';
				$title = $title.$this->request->data['Client']['full_name'];
				$title = $title.' ('.$this->request->data['Reserve']['number_of_adults'].'a';
				if($this->request->data['Reserve']['number_of_minors'] > 0){
					$title = $title.' '.$this->request->data['Reserve']['number_of_minors'].'m';
				}
				$title = $title.')';
				//Bring tour for color
				$tour = $this->Reserve->Tour->find('first', array('fields' => array('color'), 'conditions' => array('Tour.id' => $this->request->data['Reserve']['tour_id'])));

				//Prepare array to show new reserve in view
				$data['reserve']['id'] = $this->Reserve->id;
				$data['reserve']['title'] = $title;
				$data['reserve']['start'] = $this->request->data['Reserve']['date'].' '.$this->request->data['Reserve']['time'];
				$data['reserve']['tour'] = $this->request->data['Reserve']['tour_id'];
				$data['reserve']['language'] = $this->request->data['Reserve']['language_id'];
				$data['reserve']['date'] = $this->request->data['Reserve']['date'];
				$data['reserve']['time'] = $this->request->data['Reserve']['time'];
				$data['reserve']['clientEmail'] = $this->request->data['Client']['email'];
				$data['reserve']['clientName'] = $this->request->data['Client']['full_name'];
				$data['reserve']['clientBirthDate'] = $this->request->data['Client']['birth_date'];
				$data['reserve']['clientCountry'] = $this->request->data['Client']['country'];
				$data['reserve']['clientPhone'] = $this->request->data['Client']['phone'];
				$data['reserve']['numberOfAdults'] = $this->request->data['Reserve']['number_of_adults'];
				$data['reserve']['numberOfMinors'] = $this->request->data['Reserve']['number_of_minors'];
				$data['reserve']['note'] = $this->request->data['Reserve']['note'];
				$data['reserve']['referer'] = $this->request->data['Reserve']['referer'];
				$data['reserve']['backgroundColor'] = $tour['Tour']['color'];

			}
		} else {
			$data['error'] = __('The reserve could not be saved. Please, try again.');
		}

		$this->set(compact('data')); // Pass $data to the view
		$this->set('_serialize', 'data'); // Let the JsonView class know what variable to use

	}

	//Return a JSON encode respons with reserves to show in calendar (http://fullcalendar.io/docs/event_data/events_json_feed/)
	public function get() {
		//$this->request->allowMethod('ajax'); //Call only with .json at end on url

		//Bring al IDs of user winery's tour
		$tours = $this->Reserve->Tour->find('all', array('conditions' => array('Tour.winery_id' => $this->Auth->user('winery_id')), 'fields' => array('id'), 'contain' => false));
		$toursIds = [];

		foreach ($tours as $tour) {
			$toursIds[] = $tour['Tour']['id'];
		}

		//If tour filter is seted
		if(!empty($this->params['url']['tour'])){
			//Bring only reserves of those tours
			$conditions = array(
				'Reserve.tour_id' => $this->params['url']['tour']
			);
		} else{
			//Bring only reserves of winery's tours
			$conditions = array(
				'Reserve.tour_id' => $toursIds,
				'Reserve.tour_id IS NOT NULL',
			);
		}

		//Get GET request parameters (start and end date)
		if(!empty($this->params['url']['start']) && !empty($this->params['url']['end'])){
			//If start and end exist in the request, set between dates conditions
			$startEndConditions = array(
				'Reserve.date BETWEEN ? AND ?' => array(
					$this->params['url']['start'],
					$this->params['url']['end'],
				)
			);
			$conditions = array_merge($conditions, $startEndConditions);
		}

		//Bring reserves from DB
		$reserves = $this->Reserve->find('all', array('conditions' => $conditions, 'contain' => array('Client','Tour.color')));
		//debug($reserves);die();

		//Prepare response for fullcalendar
		$response = [];
		foreach ($reserves as $reserve) {
			//Build the title for show reserve
			$title = '';
			$title = $title.$reserve['Client']['full_name'];
			$title = $title.' ('.$reserve['Reserve']['number_of_adults'].'a';
			if($reserve['Reserve']['number_of_minors'] > 0){
				$title = $title.' '.$reserve['Reserve']['number_of_minors'].'m';
			}
			$title = $title.')';

			$arrayToPush = array(
				'id' => $reserve['Reserve']['id'],
				'title' => $title,
				'start' => $reserve['Reserve']['date'].' '.$reserve['Reserve']['time'],
				'tour' => $reserve['Reserve']['tour_id'],
				'language' => $reserve['Reserve']['language_id'],
				'date' => $reserve['Reserve']['date'],
				'time' => $reserve['Reserve']['time'],
				'clientEmail' => $reserve['Client']['email'],
				'clientName' => $reserve['Client']['full_name'],
				'clientBirthDate' => $reserve['Client']['birth_date'],
				'clientCountry' => $reserve['Client']['country'],
				'clientPhone' => $reserve['Client']['phone'],
				'numberOfAdults' => $reserve['Reserve']['number_of_adults'],
				'numberOfMinors' => $reserve['Reserve']['number_of_minors'],
				'note' => $reserve['Reserve']['note'],
				'referer' => $reserve['Reserve']['referer'],
				'backgroundColor' => $reserve['Tour']['color'],
				'attended' => $reserve['Reserve']['attended'],
				'from_web' => $reserve['Reserve']['from_web'],
				'paid' => $reserve['Reserve']['paid'],
			);
			$response[] = $arrayToPush;
		}

		$this->set(compact('response')); // Pass $data to the view
		$this->set('_serialize', 'response'); // Let the JsonView class know what variable to use
	}

	public function checkAttend($id = null) {
		$this->request->allowMethod('ajax'); //Call only with .json at end on url

		if (!$this->request->is(array('post', 'put'))) {
			throw new MethodNotAllowedException(__('Only POST or PUT methods allowed.'));
		}

		if (!$this->Reserve->exists($id)) {
			throw new NotFoundException(__('Invalid reserve'));
		}

		$this->reserveSecurityCheck($id);

		$this->request->data['Reserve']['id'] = $id;

		$data = array(
			'content' => '',
			'error' => '',
		);

		if ($this->Reserve->save($this->request->data)) {
			$data['content'] = __('The reserve has been modified.');
		} else {
			$data['error'] = __('The reserve could not be modified. Please, try again.');
		}

		$this->set(compact('data')); // Pass $data to the view
		$this->set('_serialize', 'data'); // Let the JsonView class know what variable to use
	}

	public function delete($id = null) {
		$this->request->allowMethod('post');

		if($this->request->is('ajax')){
			$data = array(
				'content' => '',
				'error' => '',
			);

			//$this->autoRender = $this->layout = false;

			$this->Reserve->id = $id;
			if (!$this->Reserve->exists()) {
				$data['error'] = __('Invalid Reserve');
			} else {

				$this->reserveSecurityCheck($id);

				if ($this->Reserve->delete()) {
					$data['content'] = __('Reserve deleted');
				} else {
					$data['error'] = __('Reserve was not deleted');
				}
			}

			$this->set(compact('data')); // Pass $data to the view
			$this->set('_serialize', 'data'); // Let the JsonView class know what variable to use

		}else{

			$this->Reserve->id = $id;
			if (!$this->Reserve->exists()) {
				throw new NotFoundException(__('Invalid Reserve'));
			}

			$this->reserveSecurityCheck($id);

			if ($this->Reserve->delete()) {
				$this->Session->setFlash(__('Reserve deleted'), 'metrobox/flash_success');
				return $this->redirect(array('action' => 'index'));
			}
			$this->Session->setFlash(__('Reserve was not deleted'), 'metrobox/flash_danger');
			return $this->redirect(array('action' => 'index'));
		}
	}

	/* SECURITY CHECK */
	/* Verify if the logged user isn't admin and the reserve atempted to modify is inside a winery that he manages */
	private function reserveSecurityCheck($reserveId){


		//Bring al IDs of user winery's tour
		$tours = $this->Reserve->Tour->find('all', array('conditions' => array('Tour.winery_id' => $this->Auth->user('winery_id')), 'fields' => array('id'), 'contain' => false));
		$toursAllowedIds = [];

		foreach ($tours as $tour) {
			$toursAllowedIds[] = $tour['Tour']['id'];
		}

		$reserveToModify = $this->Reserve->find('first', array(
			'conditions' => array(
				'Reserve.id' => $reserveId,
			),
			'fields' => array('id', 'tour_id'),
			'contain' => false)
		);

		if ((AuthComponent::user('Group.id') != 1) && !in_array($reserveToModify['Reserve']['tour_id'], $toursAllowedIds)) {
			throw new ForbiddenException(__('Not allowed to touch this reserve.'));
		}

	}



}
