<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class ItemMaterialDocumentRelation extends AbstractService
{
	public function getListByItemId($item)
	{
		return $this->getMapper()->select($this->getModel()->setItemId($item));
	}
	
	public function addByItem($item, $material_documents)
	{
		$ret = array();
		foreach ($material_documents as $material_document) {
			$ret[] = $this->_add($item, $material_document);
		}
	
		return $ret;
	}
	
	public function add($datas)
	{
		$ret = array();
		foreach ($datas as $data) {
			$ret[] = $this->_add($data['item'], $data['material_document']);
		}
		
		return $ret;
	}
	
	public function _add($item, $material_document)
	{
		$m_item_material_document_relation = $this->getModel()->setItemId($item)->setMaterialDocumentId($material_document);
	
		return $this->getMapper()->insert($m_item_material_document_relation);
	}
	
	public function replaceByItem($item, $material_documents)
	{
		$this->deleteByItem($item);
		return $this->addByItem($item, $material_documents);
	}
	
	public function deleteByItem($item)
	{
		return $this->getMapper()->delete($this->getModel()->setItemId($item));
	}
}
