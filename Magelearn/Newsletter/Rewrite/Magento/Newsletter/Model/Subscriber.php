<?php
declare(strict_types=1);

namespace Magelearn\Newsletter\Rewrite\Magento\Newsletter\Model;

use \Magento\Framework\App\ObjectManager;

class Subscriber extends \Magento\Newsletter\Model\Subscriber
{
	/**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $_customerGroup;

    /**
     * @var \Magento\Store\Model\Website
     */
    protected $_website;

    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $_salesRule;
	
	/**
     * Sends out confirmation success email
     *
     * @return $this
     */
    public function sendConfirmationSuccessEmail()
    {
        if ($this->getImportMode()) {
            return $this;
        }

        if (!$this->_scopeConfig->getValue(
            self::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) || !$this->_scopeConfig->getValue(
            self::XML_PATH_SUCCESS_EMAIL_IDENTITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )
        ) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->_scopeConfig->getValue(
                self::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->_storeManager->getStore()->getId(),
            ]
        )->setTemplateVars(
            ['subscriber' => $this, 'coupon_code' => $this->generateCouponCode()]
        )->setFrom(
            $this->_scopeConfig->getValue(
                self::XML_PATH_SUCCESS_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        )->addTo(
            $this->getEmail(),
            $this->getName()
        );
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }
	/**
     * Retrieve the coupon code
     *
     * @return string
     */
    protected function generateCouponCode()
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $sku = array('XXXXXXXXXX','XXXXXXXXXX');// skus you want to exclude for this rule.
			$couponData[] = $objectManager->create('Magento\SalesRule\Model\Rule');
            $couponData['name'] = '$10 Gift Voucher Newsletter Subscription ('.$this->getEmail().')';
            $couponData['description'] = '10% Off';
            $couponData['is_active'] = '1';
            $couponData['simple_action'] = 'by_percent';
            $couponData['discount_amount'] = '10';
            $from_date = date('Y-m-d');
            $couponData['from_date'] = $from_date;
            $from_date_time = strtotime($from_date);
            $to_date_time = strtotime("+14 day", $from_date_time);
            $end_time = '23:59:59';
            $to_date = date('Y-m-d', $to_date_time)." ".$end_time;
            $couponData['to_date'] = $to_date;
            $couponData['uses_per_coupon'] = '1';
            $couponData['uses_per_customer'] = '1';
            $couponData['coupon_type'] = '2';
            $couponData['customer_group_ids'] = $this->getCustomerGroupIds();
            $couponData['website_ids'] = $this->getWebsiteIds();
            /** @var \Magento\SalesRule\Model\Rule $rule */
            $rule = $this->_getSalesRule();
            $couponCode = $rule->getCouponCodeGenerator()->setLength(4)->setAlphabet(
                'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
            )->generateCode().'SUBNEW';
            $couponData['coupon_code'] = $couponCode;
            $rule->loadPost($couponData);
			$actions = $objectManager->create('Magento\SalesRule\Model\Rule\Condition\Product\Found')
            						 ->setType('Magento\SalesRule\Model\Rule\Condition\Product')
            						 ->setData('attribute','sku')
                                     ->setData('operator','!()')
                    				 ->setValue($sku);
            $rule->getActions()->addCondition($actions);
            $rule->save();
            return $couponCode;
        } catch (\Exception $e) {
            return null;
        }
    }
	/**
     * Retrieve the customer group ids
     *
     * @return array
     */
    protected function getCustomerGroupIds()
    {
        $groupsIds = [];
        $collection = $this->_getCustomerGroup()->getCollection();
        foreach ($collection as $group) {
                $groupsIds[] = $group->getId();
        }
        return $groupsIds;
    }

    /**
     * Retrieve the website ids
     *
     * @return array
     */
    protected function getWebsiteIds()
    {
        $websiteIds = [];
        $collection = $this->_getWebsite()->getCollection();
        foreach ($collection as $website) {
            $websiteIds[] = $website->getId();
        }
        return $websiteIds;
    }

    /**
     * @return \Magento\Customer\Model\Group
     */
    protected function _getCustomerGroup()
    {
        if ($this->_customerGroup === null) {
            $this->_customerGroup = ObjectManager::getInstance()->get(\Magento\Customer\Model\Group::class);
        }
        return $this->_customerGroup;
    }

    /**
     * @return \Magento\Store\Model\Website
     */
    protected function _getWebsite()
    {
        if ($this->_website === null) {
            $this->_website = ObjectManager::getInstance()->get(\Magento\Store\Model\Website::class);
        }
        return $this->_website;
    }

    /**
     * @return \Magento\SalesRule\Model\Rule
     */
    protected function _getSalesRule()
    {
        if ($this->_salesRule === null) {
            $this->_salesRule = ObjectManager::getInstance()->get(\Magento\SalesRule\Model\Rule::class);
        }
        return $this->_salesRule;
    }
}

