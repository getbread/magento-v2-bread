<?php
/**
 * Helps Integration With Catalog
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Helper;

class Catalog extends Data
{
    /** @var \Magento\Catalog\Helper\Image */
    protected $catalogImageHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Catalog\Helper\Image $catalogImageHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->catalogImageHelper = $catalogImageHelper;
        parent::__construct($helperContext, $context, $request, $encryptor, $urlInterfaceFactory, $storeManager);
    }

    /**
     * Get html param string for default button size based on configuration
     *
     * @return string
     */
    public function getDefaultButtonSizeHtml()
    {
        if ($this->useDefaultButtonSize()) {
            return 'data-bread-default-size="true"';
        }

        return '';
    }

    /**
     * Get Formatted Product Data Array
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $baseProduct
     * @param int $qty
     * @param null $lineItemPrice
     * @return array
     */
    public function getProductDataArray(\Magento\Catalog\Model\Product $product,
                                        \Magento\Catalog\Model\Product $baseProduct = null,
                                        $qty = 1,
                                        $lineItemPrice = null)
    {
        $theProduct     = ($baseProduct == null) ? $product : $baseProduct;
        $skuString      = $this->getSkuString($product, $theProduct);
        $price          = ($lineItemPrice !== null) ? $lineItemPrice * 100 : ( ( $baseProduct == null ) ? $product->getFinalPrice() : $baseProduct->getFinalPrice() ) * 100;

        $productData = array(
            'name'      => ( $baseProduct == null ) ? $product->getName() : $baseProduct->getName(),
            'price'     => $price,
            'sku'       => ( $baseProduct == null ) ? $skuString : ($baseProduct['sku'].'///'.$skuString),
            'detailUrl' => ( $baseProduct == null ) ? $product->getProductUrl() : $baseProduct->getProductUrl(),
            'quantity'  => $qty,
        );

        $imgSrc = $this->getImgSrc($product);
        if( $imgSrc != null ) {
            $productData['imageUrl'] = $imgSrc;
        }

        return $productData;
    }

    /**
     * Return Product SKU Or Formatted SKUs for Products With Options
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $theProduct
     * @return string
     */
    protected function getSkuString(\Magento\Catalog\Model\Product $product,
                                    \Magento\Catalog\Model\Product $theProduct)
    {
        $selectedOptions    = $theProduct->getTypeInstance(true)->getOrderOptions($theProduct);

        if(!array_key_exists('options', $selectedOptions ) ) {
            return (string) $product->getSku();
        }

        $skuString  = $product->getData('sku');
            foreach( $selectedOptions['options'] as $key => $value ) {
                if ($value['option_type'] == 'multiple') {
                    $selectedOptionValues = explode(',', $value['option_value']);
                } else {
                    $selectedOptionValues = array($value['option_value']);
                }
                foreach ($selectedOptionValues as $selectedOptionValue) {
                    $found = false;
                    foreach ($theProduct->getOptions() as $option) {
                        if ($found) {
                            break;
                        }
                        if (count($option->getValues()) > 0) {
                            if ($option->getTitle() == $value['label']) {
                                foreach ($option->getValues() as $optionValue) {
                                    if ($selectedOptionValue == $optionValue->getOptionTypeId()) {
                                        $skuString = $skuString . '***' . $optionValue->getSku();
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                        } else if ($value['label'] == $option['title']) {
                            $skuString = $skuString . '***' . $option->getSku() . '===' . $value['option_value'];
                            break;
                        }
                    }
                }
            }

        return $skuString;
    }

    /**
     * Get Img Src Value
     *
     * @param   \Magento\Catalog\Model\Product $product
     * @return null|string
     */
    protected function getImgSrc(\Magento\Catalog\Model\Product $product)
    {
        if( $this->isInAdmin() == true ) {
            return null;
        }

        try {
            return (string) $this->catalogImageHelper->init($product, 'thumbnail')->getUrl();
        } catch (Exception $e) {
            return null;
        }
    }
}