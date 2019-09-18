<?php

/**
 * Get Grouped product items to init bread modal based on user selection
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class GroupedItems extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Bread\BreadCheckout\Helper\Catalog
     */
    public $catalogHelper;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    public $resultFactory;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    private $stockItemRepository;

    /**
     * GroupedItems constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Bread\BreadCheckout\Helper\Catalog $catalogHelper
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Bread\BreadCheckout\Helper\Catalog $catalogHelper,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
    ) {
        $this->productRepository = $productRepository;
        $this->catalogHelper = $catalogHelper;
        $this->resultFactory = $context->getResultFactory();
        $this->imageBuilder = $imageBuilder;
        $this->stockItemRepository = $stockItemRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $product = $this->productRepository->getById($params['product']);
        $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);

        $superGroup = $params['super_group'];
        $items = [];

        /**
         * @var \Magento\Catalog\Model\Product $associatedProduct
         */
        foreach ($associatedProducts as $associatedProduct) {
            $qty = (int)$superGroup[$associatedProduct->getId()];
            $stockQty = $this->stockItemRepository->get($associatedProduct->getId());

            if (empty($qty)) {
                continue;
            }

            if ($qty > $stockQty->getQty()) {
                $items = [];
                break;
            }

            $productData = [
                'name'      => $associatedProduct->getName(),
                'price'     => round($associatedProduct->getFinalPrice() * 100),
                'sku'       => $associatedProduct->getSku(),
                'detailUrl' => $product->getProductUrl(),
                'quantity'  => $qty,
                'imageUrl'  => $this->imageBuilder->setProduct($product)
                    ->setImageId('product_small_image')
                    ->create()
                    ->getImageUrl()
            ];

            $items[] = $productData;
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData(['items' => empty($items) ? null : $items]);
    }
}
