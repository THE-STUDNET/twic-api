<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Dal\Db\ResultSet\ResultSet;

class ModuleMaterialDocumentRelation extends AbstractService
{
    public function replace($material_documents, $module_id)
    {
        if (!is_array($material_documents)) {
            $material_documents = array($material_documents);
        }

        $m_module_matetrial_document = $this->getModel()->setModuleId($module_id);
        $this->getMapper()->delete($m_module_matetrial_document);

        foreach ($material_documents as $material_document) {
            $m_module_matetrial_document->setMaterialDocument($material_document);
            $this->getMapper()->insert($m_module_matetrial_document);
        }

        return true;
    }

    public function deleteByModule($module_id)
    {
        return $this->getMapper()->delete($this->getModel()->setModuleId($module_id));
    }

    /**
     * @param array $module
     *
     * @return ResultSet
     */
    public function getListIdByModuleId($module)
    {
        return $this->getMapper()->getListIdByModuleId($module);
    }
}
