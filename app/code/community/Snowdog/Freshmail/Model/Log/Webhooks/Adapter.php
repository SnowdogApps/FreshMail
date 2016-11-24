<?php

class Snowdog_Freshmail_Model_Log_Webhooks_Adapter extends Snowdog_Freshmail_Model_Log_Adapter
{
    public function __construct()
    {
        parent::__construct('snowfreshmail_webhooks.log');
    }

    /**
     * @inheritdoc
     */
    public function log($data = null)
    {
        if (Mage::getSingleton('snowfreshmail/config')->isWebhooksDebugEnabled()) {
            return parent::log($data);
        }
        return $this;
    }
}
