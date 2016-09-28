<?php

class Snowdog_Freshmail_Model_Log_Api_Adapter extends Snowdog_Freshmail_Model_Log_Adapter
{
    public function log($data = null)
    {
        if (Mage::getSingleton('snowfreshmail/config')->isApiLogEnabled()) {
            return parent::log($data);
        }
        return $this;
    }
}
