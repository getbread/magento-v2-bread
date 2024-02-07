<?php

namespace Bread\BreadCheckout\Helper;

/**
 * Class Category
 *
 * @package Bread\BreadCheckout\Helper
 */
class Category extends Data
{

    /**
     * Is label only on category page?
     *
     * @param string $store
     *
     * @return bool
     */
    public function isLabelOnlyOnCategories($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->scopeConfig->getValue($this->getConfigValue('xml_config_cat_label_only'), $store);
    }

    /**
     * Get Custom Button Design for Category Page
     *
     * @param string $store
     *
     * @return mixed
     */
    public function getCATButtonDesign($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($this->getConfigValue('xml_config_cat_button_design'), $store);
    }

    /**
     * Check If Open In Window on Category Page
     *
     * @param string $store
     *
     * @return boolean
     */
    public function getShowInWindowCAT($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->scopeConfig->getValue($this->getConfigValue('xml_config_cat_window'), $store);
    }

    /**
     * Check If Default Button Size Is Used On Category Page
     *
     * @param string $store
     *
     * @return bool
     */
    public function useDefaultButtonSizeCategory($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->scopeConfig->getValue($this->getConfigValue('xml_config_default_bs_cat'), $store);
    }

    /**
     * Get bread categories
     *
     * @param string $store
     *
     * @return array
     */
    public function getBreadCategories($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        $selectedCategories  =$this->scopeConfig->getValue($this->getConfigValue('xml_config_select_categories'), $store);
        if(!is_null($selectedCategories)) {
            return explode(",", $this->scopeConfig->getValue($this->getConfigValue('xml_config_select_categories'), $store));
        }
        return array();
    }

    /**
     * Use As Low As Pricing View?
     *
     * @param string $store
     *
     * @return bool
     */
    public function isAsLowAsCAT($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->scopeConfig->getValue($this->getConfigValue('xml_config_cat_as_low_as'), $store);
    }

    /**
     * Is button enabled on category pages
     *
     * @param string $store
     *
     * @return bool
     */
    public function isEnabledOnCAT($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {

        return (bool)$this->scopeConfig->getValue($this->getConfigValue('xml_config_active_on_cat'), $store);
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return bool
     */
    public function isEnabledForCategory($category)
    {
        if (!$this->isActive() || !$this->isEnabledOnCAT() || empty($category)) {
            return false;
        }
        $breadCategories = $this->getBreadCategories();
        if(count($breadCategories) < 1) {
            return true;
        }

        return in_array($category->getId(), $this->getBreadCategories());
    }
}
