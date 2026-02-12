<?php
/**
 * Bread BreadCheckout - Hyva Checkout Magewire Payment Component
 *
 * This component handles the Bread payment flow within Hyva Checkout.
 * This file requires Hyva Checkout with Magewire to be installed.
 *
 * @see https://docs.hyva.io/hyva-checkout/magewire-components.html
 */
declare(strict_types=1);

namespace Bread\BreadCheckout\Magewire\Checkout\Payment\Method;

// Define stub if Magewire is not installed to prevent DI compilation errors
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
if (!class_exists(\Magewirephp\Magewire\Component::class)) {
    class BreadCheckout
    {
        // stub class 
    }
    return;
}

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magewirephp\Magewire\Component;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Bread\BreadCheckout\Helper\Data as BreadHelper;
use Bread\BreadCheckout\Helper\Quote as QuoteHelper;
use Bread\BreadCheckout\Helper\Customer as CustomerHelper;

class BreadCheckout extends Component implements EvaluationInterface
{
    public ?string $breadTransactionId = null;
    public bool $isApproved = false;

    protected $listeners = [
        'bread_transaction_complete' => 'handleTransactionComplete',
        'refresh' => '$refresh'
    ];

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $cartRepository;

    /**
     * @var BreadHelper
     */
    protected BreadHelper $breadHelper;

    /**
     * @var QuoteHelper
     */
    protected QuoteHelper $quoteHelper;

    /**
     * @var CustomerHelper
     */
    protected CustomerHelper $customerHelper;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param BreadHelper $breadHelper
     * @param QuoteHelper $quoteHelper
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository,
        BreadHelper $breadHelper,
        QuoteHelper $quoteHelper,
        CustomerHelper $customerHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->breadHelper = $breadHelper;
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
    }

    /**
     * Get integration key for Bread SDK
     *
     * @return string
     */
    public function getIntegrationKey(): string
    {
        return $this->breadHelper->getIntegrationKey() ?? '';
    }

    /**
     * Get SDK name (BreadPayments or RBCPayPlan)
     *
     * @return string
     */
    public function getSdkName(): string
    {
        $configClient = $this->breadHelper->getConfigClient();
        return $configClient === 'RBC' ? 'RBCPayPlan' : 'BreadPayments';
    }

    /**
     * Get JS SDK location URL
     *
     * @return string
     */
    public function getJsLocation(): string
    {
        return $this->breadHelper->getJsLibLocation() ?? '';
    }

    /**
     * Get payment validation URL
     *
     * @return string
     */
    public function getPaymentUrl(): string
    {
        return $this->quoteHelper->getPaymentUrl();
    }

    /**
     * Get validate totals URL
     *
     * @return string
     */
    public function getValidateTotalsUrl(): string
    {
        return $this->quoteHelper->getValidateTotalsUrl();
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->checkoutSession->getQuote()->getQuoteCurrencyCode() ?? 'USD';
    }

    /**
     * Get checkout data for Bread modal
     *
     * @return array
     */
    public function getCheckoutData(): array
    {
        $quote = $this->checkoutSession->getQuote();
        
        $items = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $qty = (float) ($item->getQty() ?? 1);
            $price = (float) ($item->getPrice() ?? 0);
            $taxAmount = (float) ($item->getTaxAmount() ?? 0);
            
            $items[] = [
                'name' => $item->getName(),
                'quantity' => (int) $qty,
                'unitPrice' => [
                    'value' => (int) round($price * 100),
                    'currency' => $this->getCurrencyCode()
                ],
                'itemUrl' => $item->getProduct()->getProductUrl(),
                'shippingCost' => [
                    'value' => 0,
                    'currency' => $this->getCurrencyCode()
                ],
                'shippingDescription' => '',
                'unitTax' => [
                    'value' => $qty > 0 ? (int) round(($taxAmount / $qty) * 100) : 0,
                    'currency' => $this->getCurrencyCode()
                ]
            ];
        }

        $subtotal = (int) round((float) ($quote->getSubtotal() ?? 0) * 100);
        $shippingAmount = (int) round((float) ($quote->getShippingAddress()->getShippingAmount() ?? 0) * 100);
        $taxAmount = (int) round((float) ($quote->getShippingAddress()->getTaxAmount() ?? 0) * 100);
        $discountAmount = (int) round(abs((float) ($quote->getShippingAddress()->getDiscountAmount() ?? 0)) * 100);
        $grandTotal = (int) round((float) ($quote->getGrandTotal() ?? 0) * 100);

        return [
            'items' => $items,
            'subTotal' => ['value' => $subtotal, 'currency' => $this->getCurrencyCode()],
            'totalShipping' => ['value' => $shippingAmount, 'currency' => $this->getCurrencyCode()],
            'totalTax' => ['value' => $taxAmount, 'currency' => $this->getCurrencyCode()],
            'totalDiscounts' => ['value' => $discountAmount, 'currency' => $this->getCurrencyCode()],
            'totalPrice' => ['value' => $grandTotal, 'currency' => $this->getCurrencyCode()]
        ];
    }

    /**
     * Get billing contact from quote
     *
     * @return array
     */
    public function getBillingContact(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();

        return [
            'firstName' => $billingAddress->getFirstname() ?? '',
            'lastName' => $billingAddress->getLastname() ?? '',
            'email' => $billingAddress->getEmail() ?? $quote->getCustomerEmail() ?? '',
            'phone' => $billingAddress->getTelephone() ?? '',
            'address' => [
                'address1' => $billingAddress->getStreetLine(1) ?? '',
                'address2' => $billingAddress->getStreetLine(2) ?? '',
                'locality' => $billingAddress->getCity() ?? '',
                'region' => $billingAddress->getRegionCode() ?? '',
                'postalCode' => $billingAddress->getPostcode() ?? '',
                'country' => $billingAddress->getCountryId() ?? 'US'
            ]
        ];
    }

    /**
     * Get shipping contact from quote
     *
     * @return array
     */
    public function getShippingContact(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        return [
            'firstName' => $shippingAddress->getFirstname() ?? '',
            'lastName' => $shippingAddress->getLastname() ?? '',
            'email' => $shippingAddress->getEmail() ?? $quote->getCustomerEmail() ?? '',
            'phone' => $shippingAddress->getTelephone() ?? '',
            'address' => [
                'address1' => $shippingAddress->getStreetLine(1) ?? '',
                'address2' => $shippingAddress->getStreetLine(2) ?? '',
                'locality' => $shippingAddress->getCity() ?? '',
                'region' => $shippingAddress->getRegionCode() ?? '',
                'postalCode' => $shippingAddress->getPostcode() ?? '',
                'country' => $shippingAddress->getCountryId() ?? 'US'
            ]
        ];
    }

    /**
     * Handle transaction completion from frontend
     *
     * @param string $transactionId
     * @return void
     */
    public function handleTransactionComplete(string $transactionId): void
    {
        $this->breadTransactionId = $transactionId;
        $this->isApproved = true;

        // Store transaction ID in quote payment
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('bread_transaction_id', $transactionId);
        $payment->setAdditionalInformation('bread_api_version', 'bread_2');
        
        $this->cartRepository->save($quote);

        $this->dispatchBrowserEvent('bread:approved', ['transactionId' => $transactionId]);
    }

    /**
     * Set transaction ID (called from frontend after Bread approval)
     *
     * @param string $transactionId
     * @return void
     */
    public function setTransactionId(string $transactionId): void
    {
        $this->handleTransactionComplete($transactionId);
    }

    /**
     * Check if Bread is approved and ready for order placement
     *
     * @return bool
     */
    public function isReadyForOrder(): bool
    {
        return $this->isApproved && !empty($this->breadTransactionId);
    }

    /**
     * Get stored transaction ID
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        if ($this->breadTransactionId) {
            return $this->breadTransactionId;
        }

        // Check if stored in quote
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        
        return $payment->getAdditionalInformation('bread_transaction_id');
    }

    /**
     * Evaluate if component is ready for order placement
     * Required by EvaluationInterface for Hyva Checkout
     *
     * Note: We always return success here because the JS intercepts
     * the Place Order click and opens the Bread modal first.
     * The order will only actually be placed after Bread approval.
     *
     * @param EvaluationResultFactory $resultFactory
     * @return EvaluationResultInterface
     */
    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        // Always allow - JS will intercept and open Bread modal if needed
        return $resultFactory->createSuccess();
    }

    /**
     * Mount component - restore state from quote if available
     *
     * @return void
     */
    public function mount(): void
    {
        $transactionId = $this->getTransactionId();
        if ($transactionId) {
            $this->breadTransactionId = $transactionId;
            $this->isApproved = true;
        }
    }
}
