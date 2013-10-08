<?php
App::uses ( 'AppController', 'Controller' );

App::uses ( 'Sanitize', 'Utility' );

/**
 * Users Controller
 *
 * @property User $User
 */
class IndexController extends AppController
{

	/**
	 * Access name of Controller
	 *
	 * @var string
	 */
	var $name = 'Index';

	/**
	 * DB Models used by this controller
	 *
	 * @var string
	 */
	var $uses = array ();

	/**
	 * Records Per Page
	 *
	 * @var string
	 */
	var $recordsPerPage = 10;

	/**
	 * Index search Filter options
	 *
	 * @var mixed
	 */
	var $filterCriteria = null;

	/**
	 * Functionality to be executed before execution of Controller actions
	 *
	 * @see UsersManagementAppController::beforeFilter()
	 */
	public function beforeFilter()
	{
		parent::beforeFilter ();

		Configure::write('records_per_page', $this->recordsPerPage);

		if($this->Session->read('JsonData') === null){

			$this->Session->write('JsonData', $this->_fetchJsonData());
		}

		$this->filterCriteria = array (
				0 => array (
						'index' => 'name',
						'type' => 'text',
						'title' => 'Name',
						'value' => null
				),
				1 => array (
						'index' => 'gender',
						'type' => 'select',
						'title' => 'Gender',
						'value' => array('male' => 'Male', 'female' => 'Female')
				),
				2 => array (
						'index' => 'age',
						'type' => 'text',
						'title' => 'Age',
						'value' => null
				),
				3 => array (
						'index' => 'phone',
						'type' => 'text',
						'title' => 'Phone',
						'value' => null
				),
				4 => array (
						'index' => 'email',
						'type' => 'text',
						'title' => 'Email',
						'value' => null
				),
				5 => array (
						'index' => 'address',
						'type' => 'text',
						'title' => 'Address',
						'value' => null
				),
				6 => array (
						'index' => 'friends',
						'type' => 'text',
						'title' => 'Friend',
						'value' => null
				),

		);

		$this->set ( 'filterCriteria', $this->filterCriteria );

	}

	/**
	 * index method
	 *
	 * @return void
	 */
	public function index()
	{
		// Assign title for the page
		$this->set ( 'title_for_layout', __ ( ' People Search', true ) );

		$field = null;
		$value = null;
		$type = null;
		$page = 1;

		if(!empty($this->request->params['page'])){
			$page = $this->request->params['page'];
			$pageArr = explode(':', $page);

			if(is_array($pageArr) && count($pageArr) == 2){
				$page = $pageArr[1];
			}
		}

		if(!empty($this->request->data['Search']['filterCriteria'])){

			if(!empty($this->request->data['Search']['filterField'])){

				if(!empty($this->request->data['Search']['filterType'])){

					$field = $this->request->data['Search']['filterCriteria'];

					$this->Session->write('Search.field', $field);

					$value = $this->request->data['Search']['filterField'];

					$this->Session->write('Search.value', $value);

					$type = $this->request->data['Search']['filterType'];

					$this->Session->write('Search.type', $type);

					$this->Session->delete('Search.data');
				}
			}

		} else {
			if(empty($this->request->params['page'])){
				$this->Session->delete('Search');
			}
		}

		if($this->Session->read('Search.field') != null){
			$field = $this->Session->read('Search.field');
		}

		if($this->Session->read('Search.value') != null){
			$value = $this->Session->read('Search.value');
		}

		if($this->Session->read('Search.type') != null){
			$type = $this->Session->read('Search.type');
		}

		if($this->Session->read('Search.data') == null){

			$data = $this->_getJsonRecords($page, $field, $value, $type);

		} else {


			$data = $this->_getPagingData($page, $this->Session->read('Search.data'));
		}

		$this->set('contentRecords', $data['records']);
		$this->set('page', $page);
		$this->set('total_records', $data['total_count']);


		if ($this->request->is ( 'Ajax' )) {
			$this->layout = 'ajax';
			$this->viewPath = 'Elements' . DS . 'index';
			$this->render ( 'index' );
		}

	}

	/**
	 * Search, parse the filter parameters as recieved from search section.
	 * View seach filters are populated accordingly.
	 *
	 * @author Atul Kumar Singh
	 */
	function search()
	{
		$this->layout = 'ajax';
		$this->viewPath = 'Elements' . DS . $this->request->plugin;
		$this->render ( 'search' );
	}

	private function _fetchJsonData()
	{

		$filePath = WWW_ROOT .  'files' . DS . 'people.json';

		$jsonObject = file_get_contents($filePath);

		$jsonObject = (object)json_decode($jsonObject,true);

		$result = $jsonObject->result;

		return $result;
	}

	private function _getPagingData($page = null, $records = null)
	{
		$curatedRecordsArr = array();

		$curatedRecordsArr['total_count'] = count($records);
		$curatedRecordsArr['records'] = null;

		$offset = ($page * $this->recordsPerPage) - $this->recordsPerPage;

		for ($i = 0, $j = $offset;  $i < count($records) ; $i++, $j++){

			if( $i == ($this->recordsPerPage)){

				break;

			} else{
				$curatedRecordsArr['records'][] = $records[$j];
			}
		}

		return $curatedRecordsArr;
	}

	private function _getJsonRecords($page = null, $field = null, $value = null, $type = null)
	{

		if($this->Session->read('JsonData') != null){

			$jsonData = $this->Session->read('JsonData');

			for ($i = 0;  $i < count($jsonData);  $i++){

				if(!empty($jsonData[$i])){

					if($field != null){

						if($value != null){

							foreach($jsonData[$i] as $key => $valuedata){

								if($field == $key){

									// If $valuedata is array
									if(is_array($valuedata)){

										foreach($valuedata as $key2 => $valuedata2){

											if($type == 'select'){

												if(strtolower($valuedata2) == strtolower($value)){
													$returnVal = true;
												}else{
													$returnVal = false;
												}

											} elseif($type == 'text'){

												$returnVal = stristr($valuedata2, $value);
											}

											if($returnVal){
												$records[] = $jsonData[$i];
												break;
											}
										}
									} else {

										if($type == 'select'){

											if(strtolower($valuedata) == strtolower($value)){
												$returnVal = true;
											}else{
												$returnVal = false;
											}

										} elseif($type == 'text'){

											$returnVal = stristr($valuedata, $value);
										}

										if($returnVal){
											$records[] = $jsonData[$i];
											break;
										}

									}

								}// End If required field matches

							}// Compare with all fields and values of json data
						}// End If Value to be matched is present

					} else {
						$records[] = $jsonData[$i];
					}
				}// If JsonData is available

			}// end for

		}// End If Json Data in Session Exists;

		$this->Session->write('Search.data', $records);

		$records = $this->_getPagingData($page, $records);

		return $records;
	}

}
