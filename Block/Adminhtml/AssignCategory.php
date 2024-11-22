<?php
namespace Mangoit\CategoryUpdate\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\ProductFactory;

class AssignCategory extends Template
{
    protected $productIds;
    protected $productFactory;

    public function __construct(
        Template\Context $context,
        ProductFactory $productFactory,
        array $data = []
    ) {
        $this->productFactory = $productFactory;
        parent::__construct($context, $data);
    }

    public function setProductIds($productIds)
    {
        $this->productIds = $productIds;
        return $this;
    }

    public function getProductIds()
    {
        return $this->productIds;
    }

    /**
     * Get an array of product objects based on product IDs
     *
     * @return \Magento\Catalog\Model\Product[]
     */
    public function getProductsByIds()
    {
        $products = [];
        if (!empty($this->productIds)) {
            foreach ($this->productIds as $productId) {
                $product = $this->productFactory->create()->load($productId);
                if ($product->getId()) {
                    $products[] = $product; // Add the product object to the array
                }
            }
        }
        return $products;
    }
}
