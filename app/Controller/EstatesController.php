<?php
App::uses('AppController', 'Controller');
/**
 * Estates Controller
 *
 * @property Estate $Estate
 * @property PaginatorComponent $Paginator
 * @property SessionComponent $Session
 */
class EstatesController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'Session','DataTable');

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->layout = 'metrobox';
		$this->Estate->recursive = 0;

		$this->paginate = array(
			'fields' => array('Estate.id','Estate.street_number','Estate.street_name','Estate.city','Type.name'),
			'recursive' => true //lee el de arriba, creo.
		);

		debug($this->DataTable->getResponse());die();

		$this->DataTable->mDataProp = true;
		$this->set('response', $this->DataTable->getResponse());
		$this->set('_serialize','response');
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->Estate->exists($id)) {
			throw new NotFoundException(__('Invalid estate'));
		}
		$options = array('conditions' => array('Estate.' . $this->Estate->primaryKey => $id));
		$this->set('estate', $this->Estate->find('first', $options));
	}

	public function add() {
		$this->layout = 'metrobox';
		if ($this->request->is('post')) {

			// debug($this->request->data);die();

			$this->Estate->create();
			if ($this->Estate->save($this->request->data)) {
				$this->Session->setFlash(__('The estate has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The estate could not be saved. Please, try again.'));
			}
		}
		$currencies = $this->Estate->Currency->find('list');
		$this->set(compact('currencies'));
		$conditions = $this->Estate->Condition->find('list');
		$this->set(compact('conditions'));
		$dispositions = $this->Estate->Disposition->find('list');
		$this->set(compact('dispositions'));
		$buildingTypes = $this->Estate->BuildingType->find('list');
		$this->set(compact('buildingTypes'));
		$buildingConditions = $this->Estate->BuildingCondition->find('list');
		$this->set(compact('buildingConditions'));
		$buildingCategories = $this->Estate->BuildingCategory->find('list');
		$this->set(compact('buildingCategories'));
		$services = $this->Estate->Service->find('list');
		$this->set(compact('services'));
		$subtypes_casa = $this->Estate->Subtype->find('list', array('conditions' => array('Subtype.type_id' => 1)));
		$this->set(compact('subtypes_casa'));
		$subtypes_departamento = $this->Estate->Subtype->find('list', array('conditions' => array('Subtype.type_id' => 2)));
		$this->set(compact('subtypes_departamento'));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		if (!$this->Estate->exists($id)) {
			throw new NotFoundException(__('Invalid estate'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Estate->save($this->request->data)) {
				$this->Session->setFlash(__('The estate has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The estate could not be saved. Please, try again.'));
			}
		} else {
			$options = array('conditions' => array('Estate.' . $this->Estate->primaryKey => $id));
			$this->request->data = $this->Estate->find('first', $options);
		}
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->Estate->id = $id;
		if (!$this->Estate->exists()) {
			throw new NotFoundException(__('Invalid estate'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Estate->delete()) {
			$this->Session->setFlash(__('The estate has been deleted.'));
		} else {
			$this->Session->setFlash(__('The estate could not be deleted. Please, try again.'));
		}
		return $this->redirect(array('action' => 'index'));
	}

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->layout = 'metrobox';
		$this->Estate->recursive = 0;
		$this->set('estates', $this->Paginator->paginate());
	}

/**
 * admin_view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		if (!$this->Estate->exists($id)) {
			throw new NotFoundException(__('Invalid estate'));
		}
		$options = array('conditions' => array('Estate.' . $this->Estate->primaryKey => $id));
		$this->set('estate', $this->Estate->find('first', $options));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->Estate->create();
			if ($this->Estate->save($this->request->data)) {
				$this->Session->setFlash(__('The estate has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The estate could not be saved. Please, try again.'));
			}
		}
	}

/**
 * admin_edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!$this->Estate->exists($id)) {
			throw new NotFoundException(__('Invalid estate'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Estate->save($this->request->data)) {
				$this->Session->setFlash(__('The estate has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The estate could not be saved. Please, try again.'));
			}
		} else {
			$options = array('conditions' => array('Estate.' . $this->Estate->primaryKey => $id));
			$this->request->data = $this->Estate->find('first', $options);
		}
	}

/**
 * admin_delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		$this->Estate->id = $id;
		if (!$this->Estate->exists()) {
			throw new NotFoundException(__('Invalid estate'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Estate->delete()) {
			$this->Session->setFlash(__('The estate has been deleted.'));
		} else {
			$this->Session->setFlash(__('The estate could not be deleted. Please, try again.'));
		}
		return $this->redirect(array('action' => 'index'));
	}
}
