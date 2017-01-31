<?php

class Snowdog_Freshmail_Block_System_Config_Info
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'snowfreshmail/system/config/info.phtml';
    protected $_config;

    public function _construct()
    {
        parent::_construct();
        $this->_config = Mage::app()->getConfig()->getModuleConfig($this->getModuleName());
    }

    public function getModuleName()
    {
        return 'Snowdog_Freshmail';
    }

    public function getVersion()
    {
        return $this->_config->version;
    }

    public function getWebsite()
    {
        return $this->_config->website;
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
