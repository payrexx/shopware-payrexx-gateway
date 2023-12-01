<?php

namespace PayrexxPaymentGateway\Components\Services;

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Order\Detail;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Voucher\Voucher;

class OrderService
{

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var Shopware_Components_Modules $basketModule */
    protected $basketModule;

    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql|null */
    protected $db;

    /**
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;

        $this->basketModule = Shopware()->Modules()->Basket();
        $this->db = Shopware()->Container()->get('db');
    }

    /**
     * @param $orderNumber
     * @return Order
     */
    public function addTransactionIdToOrder($orderNumber, $transactionID)
    {
        /** @var Order $order */
        $order = $this->getShopwareOrderByNumber($orderNumber);

        $order->setTransactionId($transactionID);
        $this->modelManager->persist($order);
        $this->modelManager->flush();

        return $order;
    }

    /**
     * @param $orderNumber
     * @return Order
     */
    public function getShopwareOrderByNumber($orderNumber)
    {
        /** @var \Shopware\Models\Order\Repository $orderRepo */
        $orderRepo = $this->modelManager->getRepository(Order::class);

        /** @var Order $order */
        $order = $orderRepo->findOneBy([
            'number' => $orderNumber
        ]);

        return $order;
    }

    /**
     * @param $orderNumber
     * @return Order
     */
    public function getShopwareOrderByGatewayID($gatewayId)
    {
        /** @var \Shopware\Models\Order\Repository $orderRepo */
        $orderRepo = $this->modelManager->getRepository(Order::class);

        /** @var Order $order */
        $order = $orderRepo->findOneBy([
            'transactionId' => $gatewayId
        ]);

        return $order;
    }

    /**
     * @param Order $orderNumber
     */
    public function restoreCartFromOrder(Order $order)
    {
        if (!empty($order)) {
            // get order details
            $orderDetails = $order->getDetails();

            if (!empty($orderDetails)) {
                // clear basket
                $this->basketModule->sDeleteBasket();

                // iterate over products and add them to the basket
                foreach ($orderDetails as $orderDetail) {
                    $result = null;

                    if ($orderDetail->getMode() == 2) {
                        // get voucher from database
                        $voucher = $this->getVoucherById($orderDetail->getArticleId());

                        if (!empty($voucher)) {
                            // remove voucher from original order
                            $this->removeOrderDetail($orderDetail->getId());

                            // add voucher to basket
                            $this->basketModule->sAddVoucher($voucher->getVoucherCode());

                            // restore order price
                            $order->setInvoiceAmount($order->getInvoiceAmount() - $orderDetail->getPrice());
                        }
                    } else {
                        // add product to basket
                        $id = $this->basketModule->sAddArticle(
                            $orderDetail->getArticleNumber(),
                            $orderDetail->getQuantity()
                        );

                        if (is_int($id)) {
                            // set attributes
                            $this->addAttributes($id, $orderDetail);
                        }
                    }

                    // reset ordered quantity
//                    $this->resetOrderDetailQuantity($orderDetail);
                }

                // recalculate order
                $order->calculateInvoiceAmount();

                /** @var Status $statusCanceled */
                $paymentStatusCanceled = Shopware()->Container()->get('models')->getRepository(
                    Status::class
                )->find(
                    Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED
                );
                $orderStatusCanceled = Shopware()->Container()->get('models')->getRepository(
                    Status::class
                )->find(
                    Status::ORDER_STATE_CANCELLED_REJECTED
                );

                // set payment status

                $order->setPaymentStatus($paymentStatusCanceled);
                $order->setOrderStatus($orderStatusCanceled);

                // save order
                $this->modelManager->persist($order);
                $this->modelManager->flush();
            }
        }

        // refresh the basket
        $this->basketModule->sRefreshBasket();
    }

    /**
     * Get a voucher by it's id
     *
     * @param int $voucherId
     *
     * @return Voucher $voucher
     *
     * @throws Exception
     */
    private function getVoucherById($voucherId)
    {
        $voucher = null;

        try {
            /** @var \Shopware\Models\Voucher\Repository $voucherRepo */
            $voucherRepo = $this->modelManager->getRepository(
                Voucher::class
            );

            /** @var Voucher $voucher */
            $voucher = $voucherRepo->findOneBy([
                'id' => $voucherId
            ]);
        } catch (Exception $ex) {
            $this->logger->error(
                'Error when loading voucher by ID: ' . $voucherId,
                array(
                    'error' => $ex->getMessage(),
                )
            );
        }

        return $voucher;
    }

    /**
     * Remove detail from order
     *
     * @param int $orderDetailId
     *
     * @return int $result
     *
     * @throws Exception
     */
    private function removeOrderDetail($orderDetailId)
    {
        $result = null;

        try {
            // init db
            $db = Shopware()->Container()->get('db');

            // prepare database statement
            $q = $db->prepare('
                DELETE FROM 
                s_order_details 
                WHERE id=?
            ');

            // execute sql query
            $result = $q->execute([
                $orderDetailId,
            ]);
        } catch (Exception $ex) {
            $this->logger->error(
                'Error when removing order detail: ' . $orderDetailId,
                array(
                    'error' => $ex->getMessage(),
                )
            );
        }

        return $result;
    }

    /**
     * Adds order details attributes to restored order basket attributes
     *
     * @param int $id
     * @param Detail $orderDetail
     * @throws Zend_Db_Adapter_Exception
     */
    private function addAttributes($id, Detail $orderDetail)
    {
        // load all order basket attributes
        $orderBasketAttributes = $this->getOrderBasketAttributes($id);

        if ($orderBasketAttributes === null) {
            return;
        }

        // load all order details attributes
        $orderDetailAttributes = $this->getOrderDetailsAttributes($orderDetail->getId());

        if (!$orderDetailAttributes) {
            return;
        }

        // create update array
        $update = [];

        foreach ($orderBasketAttributes as $key => $attribute) {
            // remove id columns
            if (in_array($key, ['id', 'basketID', 'basket_item_id'])) {
                continue;
            }

            // check if attribute exists in order details
            if (!array_key_exists($key, $orderDetailAttributes)) {
                continue;
            }

            // add attribute
            $update[$key] = $orderDetailAttributes[$key];
        }

        // perform update
        $this->db->update('s_order_basket_attributes', $update, 'id = ' . $id);
    }

    /**
     * @param int $id
     * @return array|null
     */
    private function getOrderBasketAttributes($id)
    {
        $attributesResult = $this->db->fetchAll(
            'SELECT * FROM s_order_basket_attributes WHERE basketID = ?;',
            [$id]
        );

        if (!$attributesResult) {
            return null;
        }

        if (!array_key_exists(0, $attributesResult)) {
            return null;
        }

        return $attributesResult[0];
    }

    /**
     * @param int $id
     * @return array|null
     */
    private function getOrderDetailsAttributes($id)
    {
        $orderDetailAttributesResult = $this->db->fetchAll(
            'SELECT * FROM s_order_details_attributes WHERE id = ?;',
            [$id]
        );

        if (!$orderDetailAttributesResult) {
            return null;
        }

        if (!array_key_exists(0, $orderDetailAttributesResult)) {
            return null;
        }

        return $orderDetailAttributesResult[0];
    }

    /**
     * Reset the order quantity for a canceled order
     *
     * @param Detail $orderDetail
     *
     * @return Detail $orderDetail
     *
     * @throws Exception
     */
    private function resetOrderDetailQuantity(Detail $orderDetail)
    {
        // reset quantity
        $orderDetail->setQuantity(0);

        try {
            $this->modelManager->persist($orderDetail);
            $this->modelManager->flush($orderDetail);
        } catch (Exception $e) {
            //
        }

        // build order detail repository
        $articleDetailRepository = Shopware()->Container()->get('models')->getRepository(
            \Shopware\Models\Article\Detail::class
        );

        try {
            /** @var \Shopware\Models\Article\Detail $article */
            $article = $articleDetailRepository->findOneBy([
                'number' => $orderDetail->getArticleNumber()
            ]);
        } catch (Exception $ex) {
            //
        }

        // restore stock
        if ($article !== null) {
            $article->setInStock($article->getInStock());

            try {
                $this->modelManager->persist($article);
                $this->modelManager->flush($article);
            } catch (Exception $e) {
                //
            }
        }

        return $orderDetail;
    }
}