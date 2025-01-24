<?php
/**
 * Copyright © MageWorx. All rights reserved.
 */
declare(strict_types = 1);

namespace MageWorx\GiftCardsEmail\Observer;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\GiftCards\Api\Data\GiftCardsOrderInterface;
use MageWorx\GiftCards\Api\GiftCardsOrderRepositoryInterface;

/**
 * Adds gift card code to the order confirmation email template variables.
 */
class AddGiftCardCodeToEmail implements ObserverInterface
{
    private GiftCardsOrderRepositoryInterface $giftCardsOrderRepository;
    private SearchCriteriaBuilder             $searchCriteriaBuilder;

    public function __construct(
        GiftCardsOrderRepositoryInterface $giftCardsOrderRepository,
        SearchCriteriaBuilder             $searchCriteriaBuilder
    ) {
        $this->giftCardsOrderRepository = $giftCardsOrderRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $templateVars = $observer->getData('templateVars');
        $order        = $observer->getData('order');

        $giftCardDescription = $order->getData('mageworx_giftcards_description');
        $giftCardPlainCode   = $this->getGiftCardCode($order->getId());

        if ($giftCardDescription) {
            $templateVars['mw_gift_card_description'] = $giftCardDescription;
        }

        if ($giftCardPlainCode) {
            $templateVars['mw_gift_card_code'] = $giftCardPlainCode;
        }

        $observer->setData('templateVars', $templateVars);
    }

    /**
     * @param int $orderId
     * @return string
     */
    private function getGiftCardCode(int $orderId): string
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('order_id', $orderId)
                ->create();

            $list = $this->giftCardsOrderRepository->getList($searchCriteria);
            if ($list->getTotalCount() > 0) {
                /** @var GiftCardsOrderInterface[] $items */
                $items = $list->getItems();
                /** @var GiftCardsOrderInterface $giftCardOrder */
                $giftCardOrder = reset($items);
                return $giftCardOrder ? $giftCardOrder->getCardCode() : '';
            }
        } catch (\Exception $e) {
            return '';
        }

        return '';
    }
}
