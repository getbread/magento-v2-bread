<?php
/**
 * Functions for use specifically for validating Bread payment with Magento checkout
 *
 * @author  Bread       copyright   2016
 * @author  Dale        @Mediotype
 * @author  Miranda     @Mediotype
 */
namespace Bread\BreadCheckout\Helper;

class Checkout extends Quote
{
    const BREAD_AMOUNT = "bread_transaction_amount";

    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    protected $paymentApiClient;

    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Request\Http\Proxy $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Bread\BreadCheckout\Helper\Catalog $helperCatalog,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient
    ) {
        $this->paymentApiClient = $paymentApiClient;

        parent::__construct(
            $helperContext,
            $context,
            $request,
            $encryptor,
            $urlInterfaceFactory,
            $checkoutSession,
            $helperCatalog,
            $orderCreateModel,
            $priceCurrency
        );
    }

    /**
     * Save payment amount authorized by Bread to checkout session
     *
     * @param int $amount
     * @return void
     */
    public function setBreadTransactionAmount($amount)
    {
        $this->checkoutSession->setData($this::BREAD_AMOUNT, $amount);
    }

    /**
     * Retrieve payment amount previously authorized by Bread
     *
     * @return int
     */
    public function getBreadTransactionAmount()
    {
        $amount = $this->checkoutSession->getData($this::BREAD_AMOUNT);
        return ($amount == null) ? 0 : $amount;
    }

    /**
     * Verify that Magento's quote amount matches the amount
     * authorized by Bread
     *
     * @param string $transactionId
     * @return bool
     */
    public function validateTransactionAmount($transactionId)
    {
        $breadAmount = $this->getBreadTransactionAmount();
        $quoteTotal = (int)($this->priceCurrency->round($this->getSessionQuote()->getGrandTotal()) * 100);

        if ($breadAmount === 0) {
            $info = $this->paymentApiClient->getInfo($transactionId);
            $this->setBreadTransactionAmount($info['adjustedTotal']);
        }

        return (bool) ($breadAmount == $quoteTotal);
    }
}
