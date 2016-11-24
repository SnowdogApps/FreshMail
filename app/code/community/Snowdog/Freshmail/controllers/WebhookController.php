<?php

class Snowdog_Freshmail_WebhookController extends Mage_Core_Controller_Front_Action
{
    /**
     * Unsubscribe event code
     */
    const EVENT_UNSUBSCRIBE = 'unsubscribe';

    /**
     * Check secure key before all actions
     */
    public function preDispatch()
    {
        $secureKey = $this->getRequest()->getParam('secure_key', false);
        if (!$secureKey || $secureKey != Mage::getSingleton('snowfreshmail/config')->getWebhooksSecureKey()) {
            $this->_getLogger()->log('invalid or empty secure key');
            return $this->_fault(401);
        }
        return parent::preDispatch();
    }

    /**
     * Handle webhooks calls
     */
    public function handleAction()
    {
        $post = $this->getRequest()->getRawBody();
        if (!$post) {
            $this->_getLogger()->log('empty request body');
            return $this->_fault(400);
        } else {
            $this->_getLogger()->log('request body: ' . $post);
        }

        $data = json_decode($post, true);
        if (is_null($data)) {
            $this->_getLogger()->log('unable to decode json data');
            return $this->_fault(400);
        }

        // Handle test requests
        if (!empty($data['test'])) {
            $this->_getLogger()->log('test request detected');
            return $this->_json(array('status' => 'ok'));
        }

        $hashes = array();
        $unsubscribe = array();
        foreach ($data as $item) {
            if (isset($item['event']) && $item['event'] == self::EVENT_UNSUBSCRIBE) {
                $unsubscribe[$item['hash']] = $item['email'];
            }
            $hashes[] = $item['hash'];
        }

        $toRetry = array();
        if ($unsubscribe) {
            try {
                $this->_unsubscribeEmails(array_values($unsubscribe));
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getLogger()->log($e->getMessage());
                $toRetry = array_keys($unsubscribe);
            }
        } else {
            $this->_getLogger()->log('no unsubscribe events');
        }

        $data = array();
        foreach (array_diff($hashes, $toRetry) as $hash) {
            $data[] = array('hash' => $hash, 'status' => true);
        }

        $this->_getLogger()->log('success response');

        return $this->_json($data);
    }

    /**
     * Unsubscribe specified emails from newsletter
     *
     * @param array $emails
     * @return $this
     */
    protected function _unsubscribeEmails(array $emails)
    {
        /** @var Mage_Core_Model_Resource $resourceModel */
        $resourceModel = Mage::getSingleton('core/resource');
        $connection = $resourceModel->getConnection('core_write');
        $bind = array(
            'change_status_at' => Mage::getSingleton('core/date')->gmtDate(),
            'subscriber_status' => Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED,
        );
        $connection->update(
            $resourceModel->getTableName('newsletter/subscriber'),
            $bind,
            array('subscriber_email IN (?)' => $emails)
        );
        return $this;
    }

    /**
     * Set json formatted response body
     *
     * @param mixed $response
     */
    protected function _json($response)
    {
        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * Return failure response
     *
     * @param int $httpCode
     */
    protected function _fault($httpCode)
    {
        $this->getResponse()->setHttpResponseCode($httpCode);
        $this->getResponse()->sendHeadersAndExit();
    }

    /**
     * Retrieve webhooks log adapter
     *
     * @return Snowdog_Freshmail_Model_Log_Webhooks_Adapter
     */
    protected function _getLogger()
    {
        return Mage::getSingleton('snowfreshmail/log_webhooks_adapter');
    }
}
