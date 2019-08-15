<?php
/**
 * Returns saved shipping option for checkout
 *
 * @author Bread   copyright 2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ShippingOption extends \Bread\BreadCheckout\Controller\Checkout
{

    public function execute()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(
            $this->helper->getShippingOptions()
        );
    }
}
