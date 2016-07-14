<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Message Document 
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class MessageDoc
 */
class MessageDoc extends AbstractService
{
    public function replace($message, $document)
    {
        $m_message_doc = $this->getModel()->setMessageId($message);

        $ret = [];
        if (null !== $document) {
            if (!is_array($document)) {
                $document = [$document];
            }
            $this->getMapper()->delete($m_message_doc);

            $m_message_doc->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
            foreach ($document as $d) {
                $m_message_doc->setToken($d['token'])->setName($d['name']);

                $ret[$d['token']] = $this->getMapper()->insert($m_message_doc);
            }
        }

        return $ret;
    }

    public function getList($message)
    {
        $m_message_doc = $this->getModel()->setMessageId($message);

        return $this->getMapper()->select($m_message_doc);
    }
}
