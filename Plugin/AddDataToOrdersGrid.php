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
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * AddDeliveryDateDataToOrdersGrid constructor.
     *
     * @param \Magento\Framework\App\State $appState
     * @param \Psr\Log\LoggerInterface $customLogger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $customLogger,
        array $data = []
    ) {
        $this->appState = $appState;
        $this->logger   = $customLogger;
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
            if ($this->appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER) {
                $sqlDump = $collection->getSelectSql(true);
                $a = 0;
            }

            try {
                $orderAddressTableName = $collection->getResource()->getTable('sales_order_address');
                $directoryCountryRegionTableName = $collection->getResource()->getTable('directory_country_region');
                $collection->getSelect()->joinLeft(
                    ['soa' => $orderAddressTableName],
                    'soa.parent_id = main_table.entity_id AND soa.address_type = \'shipping\'',
                    null
                );
                $collection->getSelect()->joinLeft(
                    ['dcrt' => $directoryCountryRegionTableName],
                    'soa.region_id = dcrt.region_id',
                    ['code']
                );
            } catch (\Zend_Db_Select_Exception $selectException) {
                // Do nothing in that case
                $this->logger->log(100, $selectException);
            }
        }

        if ($this->appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER) {
            $sqlDump = $collection->getSelectSql(true);
            $a = 0;
        }

        return $collection;
    }
}
