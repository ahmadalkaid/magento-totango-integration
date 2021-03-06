<?php
/**
 * ShopGo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Shopgo
 * @package     Shopgo_Totango
 * @copyright   Copyright (c) 2015 ShopGo. (http://www.shopgo.me)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Observer model
 *
 * @category    Shopgo
 * @package     Shopgo_Totango
 * @author      Ammar <ammar@shopgo.me>
 *              Emad  <emad@shopgo.me>
 *              Ahmad <ahmadalkaid@shopgo.me>
 *              Aya   <aya@shopgo.me>
 */
class Shopgo_Totango_Model_Observer
{
    /**
     * Track orders based on their statuses
     *
     * @param Varien_Event_Observer $observer
     * @return null
     */
    public function trackOrderStatus(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        // Current order state
        $orderState  = $observer->getOrder()->getState();
        // List of order states and their data
        $orderStates = array(
            Mage_Sales_Model_Order::STATE_COMPLETE => array(
                'tracker-name'   => 'complete_orders',
                'attribute-name' => 'Complete Orders'
            ),
            Mage_Sales_Model_Order::STATE_CANCELED => array(
                'tracker-name'   => 'canceled_orders',
                'attribute-name' => 'Canceled Orders'
            )
        );

        $helper->log(sprintf('Track %s order', $orderState));

        if (!$helper->isEnabled()) {
            return;
        }

        $orderStatesNames = array_keys($orderStates);

        if (!isset($orderStatesNames[$orderState])) {
            $helper->log(array(
                'message' => sprintf(
                    '%s orders are not trackable',
                    ucfirst($orderState)
                ),
                'level' => 5
            ));

            return;
        }

        foreach ($orderStates as $state => $data) {
            if ($helper->isTrackerEnabled($data['tracker-name'])) {
                $orders = Mage::getModel('sales/order')
                          ->getCollection()
                          ->addAttributeToFilter('status', array(
                              'eq' => $state
                          ))->getSize();

                $helper->track(array(
                    'account-attribute' => array(
                        $data['attribute-name'] => $orders
                    )
                ));
            }
        }
    }

    /**
    * Track newly added catalog products
    *
    * @param Varien_Event_Observer $observer
    * @return null
    */
    public function trackNewProduct(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        $helper->log('Track new catalog product, Start...');

        if (!$helper->isEnabled()) {
            return;
        }

        if ($helper->isTrackerEnabled('product')) {
            $product   = $observer->getProduct();
            $isProduct = Mage::getModel('catalog/product')
                         ->getCollection()
                         ->addFieldToFilter('entity_id', $product->getId())
                         ->setPageSize(1)
                         ->getItems();

            if (!$isProduct) {
                $productsCount = Mage::getModel('catalog/product')
                                 ->getCollection()->getSize();

                $helper->track(array(
                    'user-activity' => array(
                        'action' => 'NewProduct',
                        'module' => 'Catalog'
                    ),
                    'account-attribute' => array(
                        // New product is not counted. Thus, increment by 1
                        'Number of Catalog Products' => $productsCount + 1
                    )
                ));
            } else {
                $helper->log(array(
                    'message' => 'Not a new catalog product!',
                    'level'   => 5
                ));
            }
        }
    }

    /**
    * Track newly added catalog categories
    *
    * @param Varien_Event_Observer $observer
    * @return null
    */
    public function trackNewCategory(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        $helper->log('Track new catalog category, Start...');

        if (!$helper->isEnabled()) {
            return;
        }

        if ($helper->isTrackerEnabled('category')) {
            $categoryId = $observer->getEvent()->getCategory()->getId();
            $categories = Mage::getModel('catalog/category')
                          ->getCollection()
                          ->getAllIds();

            if (!isset($categories[$categoryId])) {
                $categoriesCount = Mage::getModel('catalog/category')
                                   ->getCollection()->getSize();

                $helper->track(array(
                    'user-activity' => array(
                        'action' => 'NewCategory',
                        'module' => 'Catalog'
                    ),
                    'account-attribute' => array(
                        // Exclude root category. Thus, decrement by 1
                        'Number of Catalog Categories' => $categoriesCount - 1
                    )
                ));
            } else {
                $helper->log(array(
                    'message' => 'Not a new catalog category!',
                    'level'   => 5
                ));
            }
        }
    }

    /**
    * Track newly added catalog attributes
    *
    * @param Varien_Event_Observer $observer
    * @return null
    */
    public function trackNewAttribute(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        $helper->log('Track new catalog attribute, Start...');

        if (!$helper->isEnabled()) {
            return;
        }

        if ($helper->isTrackerEnabled('attribute')) {
            $attributeId = $observer->getEvent()->getAttribute()->getId();
            $isAttribute = Mage::getModel('eav/entity_attribute')
                           ->load($attributeId)
                           ->getAttributeCode();

            if (!$isAttribute) {
                $attributesCount = Mage::getResourceModel('catalog/product_attribute_collection')
                                   ->addVisibleFilter()->getSize();

                $helper->track(array(
                    'user-activity' => array(
                        'action' => 'NewAttribute',
                        'module' => 'Catalog'
                    ),
                    'account-attribute' => array(
                        // New attribute is not counted. Thus, increment by 1
                        'Number of Catalog Attributes' => $attributesCount + 1
                    )
                ));
            } else {
                $helper->log(array(
                    'message' => 'Not a new catalog attribute!',
                    'level'   => 5
                ));
            }
        }
    }

    /**
     * Track admin user successful logins
     *
     * @param Varien_Event_Observer $observer
     * @return null
     */
    public function trackAdminSuccessfulLogin(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        $helper->log('Track successful admin user login');

        if (!$helper->isEnabled()) {
            return;
        }

        if ($helper->isTrackerEnabled('admin_login')) {
            $adminUser     = $observer->getUser();
            $adminUsername = $adminUser->getUsername();

            $excludedAdminUsers = $helper->getExcludedAdminUsers();

            if (!isset($excludedAdminUsers[$adminUsername])) {
                // New login is not counted. So, this is a work around for
                // $adminUser->getLogDate()
                $logDate = Mage::getModel('core/date')->date('Y-m-d H:i:s');

                $helper->track(array(
                    'user-activity' => array(
                        'action' => 'AdminLogin',
                        'module' => 'Admin'
                    ),
                    'account-attribute' => array(
                        'Admin User Name' => $adminUser->getUsername(),
                        'Admin Last Login Time' => $logDate,
                        // New login is not counted. Thus, increment by 1
                        'Admin Login Number'    => $adminUser->getLognum() + 1
                    )
                ));
            } else {
                $helper->log(array(
                    'message' => sprintf(
                        'Admin user "%s" is excluded from tracking',
                        $adminUsername
                    ),
                    'level' => 5
                ));
            }
        }
    }
}
