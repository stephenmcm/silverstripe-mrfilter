<?php

abstract class ListFilterWidget extends Controller {
	private static $hide_ancestor = 'ListFilterWidget';

	/**
	 * A custom set list to use for the widget
	 */
	protected $list = null;

	/** 
	 * @var ListFilterForm
	 */
	protected $form;

	/**
	 * @var DataObject
	 */
	protected $record = null;

	/**
	 * Extra CSS classes
	 *
	 * @var array
	 */
	protected $extraClasses;

	public function __construct() {
		parent::__construct();
		$this->addExtraClass('js-listfilter-widget');
	}

	/** 
	 * @return void
	 */
	public function onBeforeRender() {
	}

	/**
	 * @return ListFilterSet
	 */
	public function getListFilterSet() {
		$form = $this->getForm();
		return ($form) ? $form->getRecord() : null;
	}

	/** 
	 * @return ListFilterWidget
	 */
	public function setForm(ListFilterForm $form) {
		$this->form = $form;
		return $this;
	}

	/** 
	 * @return ListFilterForm
	 */
	public function getForm() {
		return $this->form;
	}

	/**
	 * Get current page
	 *
	 * @return SiteTree
	 */
	public function getPage() {
		$form = $this->getForm();
		if (!$form) {
			return null;
		}
		return $form->getPage();
	}

	/**
	 * @return DataObject
	 */
	public function setRecord(DataObjectInterface $record) {
		$this->record = $record;
		return $this;
	}

	/**
	 * @return DataObject
	 */
	public function getRecord() {
		return $this->record;
	}

	/**
	 * @return SS_List
	 */
	public function BaseList() {
		$list = $this->getList();
		if (!$list) {
			$filterSetRecord = $this->getListFilterSet();
			$list = $filterSetRecord->BaseList();
		}
		return $list;
	}

	/**
	 * @return SS_List
	 */
	public function FilteredList($data = array()) {
		$filterSetRecord = $this->getListFilterSet();
		
		$list = $this->BaseList();
		$list = $filterSetRecord->applyFilterToList($list, $data, $this);
		return $list;
	}

	/**
	 * Override the list used for the widget.
	 *
	 * @return ListFilterWidget
	 */
	public function setList(SS_List $list) {
		$this->list = $list;
		return $this;
	}

	/**
	 * @return SS_List
	 */ 
	public function getList() {
		return $this->list;
	}
	
	/**
	 * @return array
	 */
	public function getDataAttributes() {
		$attributes = array();
		$listFilterSet = $this->getListFilterSet();
		if ($listFilterSet) {
			// NOTE(Jake): This is required to link the <form> and map <div> together (2016-08-23)
			$attributes['listfilter-id'] = $listFilterSet->ID;
		}
		return $attributes;
	}

	/**
	 * @return array
	 */
	final public function getDataAttributesAll() {
		$attributes = $this->getDataAttributes();
		$this->extend('updateDataAttributes', $attributes);
		return $attributes;
	}

	/**
	 * @return string
	 */
	protected $_cache_data_attributes_html = null;
	public function DataAttributesHTML() {
		if ($this->_cache_data_attributes_html !== null) {
			return $this->_cache_data_attributes_html;
		}
		$result = '';
		$attributes = $this->getDataAttributesAll();
		foreach ($attributes as $attribute => $value) {
			if ($value !== null) {
				if (is_array($value)) {
					$value = Convert::raw2att(json_encode($value));
				}
				$result .= 'data-'.$attribute.'="'.$value.'" ';
			}
		}
		return $this->_cache_data_attributes_html = rtrim($result);
	}

	/**
	 * If visiting the page with GET parameterss.
	 *
	 * @return array
	 */
	public function getVarData() {
		$data = $this->getRequest()->getVars();
		if (!$data) {
			// Fallback to form
			$data = $this->getForm()->getVarData();
		}
		return $data;
	}

	/** 
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links($this->getForm()->Link('doWidget'), $action);
	}

	/**
	 * Compiles all CSS-classes set on this.
     *
	 * @return string
	 */
	public function extraClass() {
		$classes = array();
		if($this->extraClasses) {
			$classes = array_merge(
				$classes,
				array_values($this->extraClasses)
			);
		}
		return implode(' ', $classes);
	}

	/**
	 * Add one or more CSS-classes
	 *
	 * Multiple class names should be space delimited.
	 *
	 * @param string $class
	 *
	 * @return $this
	 */
	public function addExtraClass($class) {
		$classes = preg_split('/\s+/', $class);
		foreach ($classes as $class) {
			$this->extraClasses[$class] = $class;
		}
		return $this;
	}

	/**
	 * Remove one or more CSS-classes
	 *
	 * @param string $class
	 *
	 * @return $this
	 */
	public function removeExtraClass($class) {
		$classes = preg_split('/\s+/', $class);
		foreach ($classes as $class) {
			unset($this->extraClasses[$class]);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getTemplates($templateName, $recordOrClasses = null) {
		if ($recordOrClasses === null) {
			$recordOrClasses = $this->getRecord();
			if (!$recordOrClasses) {
				$recordOrClasses = $this->getPage();
			}
		}
		$result = ListFilterUtility::get_templates($templateName, $recordOrClasses);
		// todo(Jake): Add and test
		// $this->extend('updateTemplates', $result, $templateName, $recordOrClasses);
		return $result;
	}

	/** 
	 * @return HTMLText
	 */
	public function forTemplate() {
		$this->onBeforeRender();
		$this->extend('onBeforeRender', $this);
		$result = $this->renderWith(array($this->class, __CLASS__));
		return $result;
	}
}