<?php

namespace Mangoit\CategoryUpdate\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\CategoryLinkRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\ResourceConnection;


class UpdateCategoryAjax extends Action
{
    protected $productFactory;
    protected $categoryCollectionFactory;
    protected $resultJsonFactory;
    protected $categoryLinkManagement;
    protected $categoryLinkRepository;
    protected $messageManager; // Add MessageManager
   /**
     * @var ResourceConnection
     */
    protected $resource;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ProductFactory $productFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        JsonFactory $resultJsonFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        CategoryLinkRepository $categoryLinkRepository,
        ResourceConnection $resource,
        ManagerInterface $messageManager // Inject MessageManager
    ) {
        $this->productFactory = $productFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->resource = $resource;
        $this->messageManager = $messageManager; // Initialize MessageManager
        parent::__construct($context);
    }

    public function execute()
    {
        // Get request params
        $resultJson = $this->resultJsonFactory->create();
        $params = $this->getRequest()->getParams();
        
        if (!isset($params['products'])) {
            $this->messageManager->addErrorMessage(__('Missing product or category data.'));
            return $resultJson->setData([
                'success' => false,
                'message' => __('Missing product or category data.')
            ]);
        }

        $productIdArray = json_decode($params['products']);

        try {
            foreach ($productIdArray as $productId => $categoryIds) {
                $product = $this->productFactory->create()->load($productId);
                if (!$product->getId()) {
                    continue;
                }
                $this->removeProductsFromCategory($product, $categoryIds);
                $this->addProductToCategory($product, $categoryIds);
            }
            // Add success message
            $this->messageManager->addSuccessMessage(__('Categories have been successfully assigned to the products.'));

            return $resultJson->setData([
                'success' => true,
                'message' => __('Categories have been successfully assigned to the products.')
            ]);
        } catch (LocalizedException $e) {
            // Add error message
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Add generic error message
            $this->messageManager->addErrorMessage(__($e));
            return $resultJson->setData([
                'success' => false,
                'message' => __($e)
            ]);
        }
    }

    /**
     * Get product object by product ID
     *
     * @param int $productId
     * @return \Magento\Catalog\Model\Product|null
     */
    protected function getProductById($productId)
    {
        if (!$productId) {
            return null; // Return null if no product ID is provided
        }

        // Load the product using the product factory
        return $this->productFactory->create()->load($productId);
    }

    /**
     * Add product to category
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $categoryIds
     */
    public function addProductToCategory($product, $categoryIds)
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            if (!count($categoryIds)) {
                return;
            }
            // Assign product to categories
            $this->categoryLinkManagement->assignProductToCategories(
                $product->getSku(),
                $categoryIds
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Handle Magento-specific exceptions
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        } catch (\Exception $e) {
            // Handle general exceptions
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
    }


    /**
     * Remove product from category
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $categoryIds
     */
    public function removeProductsFromCategory($product, $categoryIds)
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
        $assignedCategories = $product->getCategoryIds();
        $productId = $product->getId();
        $productSku = $product->getSku();
            
        foreach ($assignedCategories as  $category) {
            $this->deleteById($category,$productId);
        }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
    }

    /**
     * Delete product from category by ID
     *
     * @param int $categoryId
     * @param int $productId
     * @return bool
     */
    public function deleteById($categoryId, $productId)
    {
        $connection = null; // Initialize the connection variable
        try {
            // Get the database connection
            $connection = $this->resource->getConnection();
            // Prepare and execute the delete query
            $tableName = $connection->getTableName('catalog_category_product');
            $where = [
                'category_id = ?' => (int)$categoryId,
                'product_id = ?'  => (int)$productId
            ];
            $connection->delete($tableName, $where);
            return true;
        } catch (\Exception $e) {
            return false;
        } finally {

            if ($connection) {
                $connection->closeConnection();
            }
        }
    }
}