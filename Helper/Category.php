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
        return (bool)$this->scopeConfig->getValue(self::XML_CONFIG_CAT_LABEL_ONLY, $store);
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
        return $this->scopeConfig->getValue(self::XML_CONFIG_CAT_BUTTON_DESIGN, $store);
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
        return (bool)$this->scopeConfig->getValue(self::XML_CONFIG_CAT_WINDOW, $store);
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
        return (bool)$this->scopeConfig->getValue(self::XML_CONFIG_DEFAULT_BS_CAT, $store);
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
        $selectedCategories  =$this->scopeConfig->getValue(self::XML_CONFIG_SELECT_CATEGORIES, $store);
        if(!is_null($selectedCategories)) {
            return explode(",", $this->scopeConfig->getValue(self::XML_CONFIG_SELECT_CATEGORIES, $store));
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
        return (bool)$this->scopeConfig->getValue(self::XML_CONFIG_CAT_AS_LOW_AS, $store);
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

        return (bool)$this->scopeConfig->getValue(self::XML_CONFIG_ACTIVE_ON_CAT, $store);
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
