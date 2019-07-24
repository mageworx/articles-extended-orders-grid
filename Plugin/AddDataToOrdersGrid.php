<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ExtendedOrdersGrid\Plugin;

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
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $collection
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
                    null
                );
                $collection->getSelect()->joinLeft(
                    ['dcrt' => $directoryCountryRegionTableName],
                    'soat.region_id = dcrt.region_id',
                    ['code']
                );
            } catch (\Zend_Db_Select_Exception $selectException) {
                // Do nothing in that case
                $this->logger->log(100, $selectException);
            }
        }

        return $collection;
    }
}
