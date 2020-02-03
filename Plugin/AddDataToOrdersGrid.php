<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ExtendedOrdersGrid\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

/**
 * Class AddDataToOrdersGrid
 */
class AddDataToOrdersGrid
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * AddDataToOrdersGrid constructor.
     *
     * @param \Psr\Log\LoggerInterface $customLogger
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $customLogger,
        array $data = []
    ) {
        $this->logger = $customLogger;
    }

    /**
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject
     * @param OrderGridCollection $collection
     * @param $requestName
     * @return mixed
     */
    public function afterGetReport($subject, $collection, $requestName)
    {
        if ($requestName !== 'sales_order_grid_data_source') {
            return $collection;
        }

        if ($collection->getMainTable() === $collection->getResource()->getTable('sales_order_grid')) {
            try {
                $orderAddressTableName           = $collection->getResource()->getTable('sales_order_address');
                $directoryCountryRegionTableName = $collection->getResource()->getTable('directory_country_region');
                $collection->getSelect()->joinLeft(
                    ['soat' => $orderAddressTableName],
                    'soat.parent_id = main_table.entity_id AND soat.address_type = \'shipping\'',
                    ['telephone']
                );
                $collection->getSelect()->joinLeft(
                    ['dcrt' => $directoryCountryRegionTableName],
                    'soat.region_id = dcrt.region_id',
                    ['code']
                );

                // Add product's name column
                $this->addProductsNameColumn($collection);
            } catch (\Zend_Db_Select_Exception $selectException) {
                // Do nothing in that case
                $this->logger->log(100, $selectException);
            }
        }

        return $collection;
    }

    /**
     * Adds products name column to the orders grid collection
     *
     * @param OrderGridCollection $collection
     * @return OrderGridCollection
     */
    private function addProductsNameColumn(OrderGridCollection $collection): OrderGridCollection
    {
        // Get original table name
        $orderItemsTableName = $collection->getResource()->getTable('sales_order_item');
        // Create new select instance
        $itemsTableSelectGrouped = $collection->getConnection()->select();
        // Add table with columns which must be selected (skip useless columns)
        $itemsTableSelectGrouped->from(
            $orderItemsTableName,
            [
                'name'     => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT name SEPARATOR \',\')'),
                'order_id' => 'order_id'
            ]
        );
        // Group our select to make one column for one order
        $itemsTableSelectGrouped->group('order_id');
        // Add our sub-select to main collection with only one column: name
        $collection->getSelect()
                   ->joinLeft(
                       ['soi' => $itemsTableSelectGrouped],
                       'soi.order_id = main_table.entity_id',
                       ['name']
                   );

        return $collection;
    }
}
