<?php
namespace Mangoit\CategoryUpdate\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassUpdateCategory extends Action
{
    protected $productCollectionFactory;
    protected $categoryRepository;
    protected $resultPageFactory;

        /**
     * Massactions filter
     *
     * @var Filter
     */
    protected $filter;


    public function __construct(
        Context $context,
        ProductCollectionFactory $productCollectionFactory,
        CategoryRepository $categoryRepository,
        Filter $filter,
        PageFactory $resultPageFactory // Add PageFactory
    ) {
        parent::__construct($context);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->filter = $filter;
        $this->resultPageFactory = $resultPageFactory; // Initialize PageFactory
    }

    public function execute()
    {

        $requestSelected = $this->getRequest()->getParams();
        if (isset($requestSelected['selected'])) {
            $selectedArray = $requestSelected['selected'];
            
        } elseif (!is_array($requestSelected['excluded'])) {
            if (isset($requestSelected['filters'])) {
                $selectedArray = $this->getProductIdsByFilter($requestSelected['filters']);
            } else {
                $selectedArray = $this->getAllProductIds();
            }
            
        } else {
            $excludedArray = $requestSelected['excluded'];
            // $selectedArray = $this->getAllProductIds();
            if (isset($requestSelected['filters'])) {
                $selectedArray = $this->getProductIdsByFilter($requestSelected['filters']);
            } else {
                $selectedArray = $this->getAllProductIds();
            }
            $selectedArray = array_diff($selectedArray, $excludedArray);    
            
        }
        $productIds = $selectedArray;
        // Create a result page
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Category Assign'));

        // Pass product IDs to the block
        if ($productIds) {
            $resultPage->getLayout()
                       ->getBlock('custom-category-assign')
                       ->setProductIds($productIds); // Pass product IDs as an array
        }
        return $resultPage;
    }

    // Function to get all product IDs
    public function getAllProductIds()
    {
        $productCollection = $this->productCollectionFactory->create();
        $productIds = $productCollection->getAllIds(); // Get all product IDs
        return $productIds;
    }

    // Function to get product IDs by applying filters dynamically
    public function getProductIdsByFilter($filters)
    {    
        $productCollection = $this->filter->getCollection($this->productCollectionFactory->create());
        $productIds = $productCollection->getAllIds();
        return $productIds;
    }

}