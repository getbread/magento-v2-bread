<?php
/**
 * Functions for use specifically for validating Bread payment with Magento checkout
 *
 * @author Bread       copyright   2016
 * @author Dale        @Mediotype
 * @author Miranda     @Mediotype
 */
namespace Bread\BreadCheckout\Helper;

class Checkout extends Quote
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    const BREAD_AMOUNT = "bread_transaction_amount";

    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bread\BreadCheckout\Helper\Catalog $helperCatalog,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Bread\BreadCheckout\Helper\Log $logger
    ) {
        $this->logger = $logger;
        parent::__construct(
            $helperContext,
            $context,
            $request,
            $encryptor,
            $urlInterfaceFactory,
            $checkoutSession,
            $helperCatalog,
            $orderCreateModel,
            $priceCurrency,
            $paymentApiClient,
            $productRepository
        );
    }

    /**
     * Save payment amount authorized by Bread to checkout session
     *
     * @param  int $amount
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
     * @param  $transactionId
     * @return bool
     * @throws \Exception
     */
    public function validateTransactionAmount($transactionId)
    {
        $breadAmount = $this->getBreadTransactionAmount();
        $quoteTotal = (int)($this->priceCurrency->round($this->getSessionQuote()->getGrandTotal() * 100));

        if ($breadAmount === 0) {
            $this->logger->info('bread amount is 0');
            $info = $this->paymentApiClient->getInfo($transactionId);
            $this->setBreadTransactionAmount($info['adjustedTotal']);
        }

        $areAmountsEqual = (bool) (abs((int)$breadAmount - $quoteTotal) <= 2);

        if (!$areAmountsEqual) {
            $quote = $this->getSessionQuote();

            $itemPrices = array_map(function($item) {
                return $item->getPrice() * 100;
            }, $quote->getItems());

            $this->logger->log([
                'LOCATION' => __CLASS__,
                'SESSION QUOTE GRAND TOTAL' => ($quote->getGrandTotal() * 100),
                'SESSION QUOTE AFTER ROUND METHOD' => ($quoteTotal),
                'SESSION QUOTE SUB TOTAL' => ($quote->getSubtotal() * 100),
                'SESSION QUOTE SUB TOTAL W/ DISCOUNT' => ($quote->getSubtotalWithDiscount() * 100),
                'SESSION QUOTE SHIPPING ADDRESS TAX AMOUNT' => ($quote->getShippingAddress()->getBaseTaxAmount() * 100),
                'SESSION QUOTE SHIPPING COST' => ($quote->getShippingAddress()->getShippingAmount() * 100),
                'SESSION QUOTE ITEM PRICES' => $itemPrices
            ]);

        }

        return $areAmountsEqual;
    }
}
