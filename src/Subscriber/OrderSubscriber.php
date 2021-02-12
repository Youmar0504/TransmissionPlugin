<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Emakers\TransmissionPlugin\Services\ShopwareConnectService;


class OrderSubscriber implements EventSubscriberInterface
{

    /**
     * @ContainerInterface $container
     */
    private $container;

    /**
     * @var EntityRepositoryInterface
     */
    private $stateMachineStateRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EntityRepositoryInterface
     */
     private $shippingMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $transmissionLogRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $transmissionRepository;


    public function __construct(ContainerInterface $container, EntityRepositoryInterface $stateMachineStateRepository, EntityRepositoryInterface $orderRepository, EntityRepositoryInterface $shippingMethodRepository, EntityRepositoryInterface $transmissionRepository, EntityRepositoryInterface $transmissionLogRepository) {
                $this->container                   = $container;
                $this->stateMachineStateRepository = $stateMachineStateRepository;
                $this->orderRepository             = $orderRepository;
		$this->shippingMethodRepository	   = $shippingMethodRepository;
                $this->transmissionRepository      = $transmissionRepository;
                $this->transmissionLogRepository   = $transmissionLogRepository;
    }

        public static function getSubscribedEvents(): array
        {
                return [
                        OrderEvents::ORDER_TRANSACTION_WRITTEN_EVENT    => 'onOrderCheckout',
			StateMachineTransitionEvent::class              => 'onOrderPaid',
			'state_enter.order_transaction.state_changed'	=> 'onOrderUpdate',
                ];
        }


        public function onOrderPaid(StateMachineTransitionEvent $event) {

            $orderTransactionId = $event->getEntityId();
            $context = $event->getContext();

            $technicalName = $event->getToPlace()->getTechnicalName();
	    if ($technicalName == 'paid' && $technicalName != 'failed')
            {
            
	    $criteria = new Criteria();
            $criteria->addFilter(
                new EqualsFilter('transactions.id', $orderTransactionId)
            );
    
            $order = $this->orderRepository->search($criteria, $context)->first();
            $orderId = $order->getOrderCustomer()->getOrderId();
    
            $criteria1= new Criteria([$orderId]);
            $criteria1->addAssociation('lineItems');
    
            $orderObject = $this->orderRepository->search( $criteria1, \Shopware\Core\Framework\Context::createDefaultContext() );
            $orderNumber = intval($orderObject->first()->getOrderNumber());

	    $criteria->addAssociation('deliveries');

            $deliveryObject   = $this->orderRepository->search( $criteria, \Shopware\Core\Framework\Context::createDefaultContext() );
            $shippingMethodId = $deliveryObject->first()->getDeliveries()->first()->getShippingMethodId();

       	   $shippingMethodObject = $this->shippingMethodRepository->search(new Criteria([ $shippingMethodId ]), \Shopware\Core\Framework\Context::createDefaultContext() );
       	     $shippingMethod       = $shippingMethodObject->first()->getName();

		$sent        = $this->transmissionRepository->search(
                                        (new Criteria())->addFilter(new EqualsFilter('orderNumber', $orderNumber)),
                                        \Shopware\Core\Framework\Context::createDefaultContext()
                                );
                if (!array_key_exists('status', $sent))
                {
                        $messageProcess = (new ShopwareConnectService)->MessageProcess($orderId, $orderNumber, $orderObject, $this->transmissionLogRepository, $this->container, 'Exact', $shippingMethod);
                        $this->transmissionRepository->create(
                        [
                                [
                                'orderNumber' => $orderNumber,
                                'status'      => '100',
                                'origine'     => 'Shopware',
                                'destination' => 'Exact',
                                'requestType' => 'Create Order'
                                ],
                        ],
                        \Shopware\Core\Framework\Context::createDefaultContext()
                        );

                }
                else
                {
                        //order already sent
                        $this->transmissionRepository->create(
                        [
                                [
                                'orderNumber' => $orderNumber,
                                'status'      => '999',
                                'origine'     => 'Shopware',
                                'destination' => 'Exact',
                                'requestType' => 'Resent Order'
                                ],
                        ],
                        \Shopware\Core\Framework\Context::createDefaultContext()
                        );

                }
            }
	    /*
            elseif ($technicalName == 'refunded' || $technicalName == 'refunded_partially')
            {
                        $messageProcess = (new ShopwareConnectService)->MessageProcess($orderId, $orderNumber, $orderObject, $this->transmissionLogRepository, $this->container, 'Exact', $shippingMethod);
                        $this->transmissionRepository->create(
                        [
                                [
                                'orderNumber' => $orderNumber,
                                'status'      => '100',
                                'origine'     => 'Shopware',
                                'destination' => 'Exact',
                                'requestType' => 'Order '. $technicalName .''
                                ],
                        ],
                        \Shopware\Core\Framework\Context::createDefaultContext()
                        );
            }
	    */

        }

        public function onOrderCheckout($event)
        {
            $payloads = $event->getPayloads();
	    $open = '2ea3dd17fad94022a50ee32d04c87cef';

            if ($payloads[0]['stateId'] == $open)
            {

                $orderId  = $payloads[0]['orderId'];

                $criteria = new Criteria([$orderId]);
                $criteria->addAssociation('lineItems');

                $orderObject = $this->orderRepository->search( $criteria, \Shopware\Core\Framework\Context::createDefaultContext() );
		$orderNumber = intval($orderObject->first()->getOrderNumber());

		$paymentMethodId = $payloads[0]['paymentMethodId'];

		$criteria->addAssociation('deliveries');
		
		$deliveryObject   = $this->orderRepository->search( $criteria, \Shopware\Core\Framework\Context::createDefaultContext() ); 
		$shippingMethodId = $deliveryObject->first()->getDeliveries()->first()->getShippingMethodId();

		
		$shippingMethodObject = $this->shippingMethodRepository->search(new Criteria([ $shippingMethodId ]), \Shopware\Core\Framework\Context::createDefaultContext() );
		$shippingMethod       = $shippingMethodObject->first()->getName();

		$invoice = 'd248cd984d3d4c0cbf81278941e70ac2';
                if ($paymentMethodId == $invoice)
                {
                $messageProcess = (new ShopwareConnectService)->MessageProcess($orderId, $orderNumber, $orderObject, $this->transmissionLogRepository, $this->container, 'Exact', $shippingMethod);
                $this->transmissionRepository->create(
                        [
                                [
                                'orderNumber' => $orderNumber,
                                'status'      => '100',
                                'origine'     => 'Shopware',
                                'destination' => 'Exact',
                                'requestType' => 'Create Order'
                                ],
                        ],
                        \Shopware\Core\Framework\Context::createDefaultContext()
                        );
                }
            }
        }
}

