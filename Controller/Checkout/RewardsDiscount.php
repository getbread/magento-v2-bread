<?php

namespace Bread\BreadCheckout\Controller\Checkout;


use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Rewards\Helper\Purchase;

class RewardsDiscount extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Purchase
     */
    private $rewardsPurchase;

    public function __construct(
        Context $context,
        Purchase $rewardsPurchase
    )
    {
        parent::__construct($context);
        $this->rewardsPurchase = $rewardsPurchase;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $purchase = $this->rewardsPurchase->getPurchase();
        $resultJson->setData(['discount' => (int)$purchase->getSpendAmount()]);

        return $resultJson;
    }
}