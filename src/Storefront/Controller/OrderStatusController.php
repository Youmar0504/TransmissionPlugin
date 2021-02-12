<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Storefront\Controller;

use Emakers\TransmissionPlugin\Services\ExactDataService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Emakers\TransmissionPlugin\Entity\TransmissionEntity;
use Emakers\TransmissionPlugin\Entity\TransmissionEntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\Checkout\Order\Api\OrderActionController;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;

/**
 * @RouteScope(scopes={"storefront"})
 */
class OrderStatusController extends StorefrontController
{

    private  $orderRepository;
    private  $stateMachineRegistry;
    private  $stateMachineRepository;
    private  $context;

    public function __construct( EntityRepositoryInterface $orderRepository, StateMachineRegistry $stateMachineRegistry, EntityRepositoryInterface $stateMachineRepository)
    {
	$this->orderRepository      	= $orderRepository;
        $this->stateMachineRegistry 	= $stateMachineRegistry;
	$this->stateMachineRepository   = $stateMachineRepository;
	$this->context 			= Context::createDefaultContext();
    }

    /**
     * @Route("/orderStatus", name="frontend.orderstatus", options={"seo"="false"}, defaults={"csrf_protected"=false, "XmlHttpRequest"=true}, methods={"GET", "POST"})
    */
    public function orderStatus(RequestInterface $request): Response
    {
        $body            = $request->getBody()->getContents();
        $decodedWebhook  = json_decode($body, true);
        $orderGuid       = $decodedWebhook['Content']['Key'];
        $currentDivision = $decodedWebhook['Content']['Division'];
        $orderNumber     = $this->getOrderNumber($orderGuid, $currentDivision);

	$criteria = new Criteria();
	$criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        $criteria->addAssociation('deliveries');

        $order = $this->orderRepository->search(
            $criteria, $this->context
        )->first();
	
	if ($order instanceof OrderEntity)
	{
		$this->setShipped($order->getDeliveries()->first());
        	$this->completeOrder($order);
		mail('umar@emakers.be', 'Order '.$orderNumber.' was delivered', $orderNumber . ' status was set to Complete');

		die('Status Updated !');
	}

	die('Finished Status Update');
    }

    private function getOrderNumber($orderGuid, $currentDivision)
    {
        $accessToken = (new ExactDataService)->accessToken();

        $url         = "https://start.exactonline.be/api/v1/". $currentDivision ."/salesorder/SalesOrders?\$filter=OrderID eq guid'". $orderGuid ."'";
        $headers     = array('Authorization: Bearer ' .$accessToken. '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = curl_exec($ch);

        $dom = new \DOMDocument();
        @$dom->loadHTML($data);

        $statusObject   = $dom->getElementsByTagName('deliverystatus');
        $deliveryStatus = $statusObject[0]->nodeValue;
        if ($deliveryStatus == 21)
        {
            $orderObject = $dom->getElementsByTagName('ordernumber');
            return $orderObject[0]->nodeValue;
        }
    }

    private function setShipped(OrderDeliveryEntity $orderDelivery)
    {
	try {
        	$this->stateMachineRegistry->transition(
            		new Transition(
                		OrderDeliveryDefinition::ENTITY_NAME,
                		$orderDelivery->getId(),
      		      	 	StateMachineTransitionActions::ACTION_SHIP,
                		'stateId'
            		),
            		$this->context
        	);
	} catch (IllegalTransitionException $e) {
            // Do nothing if the transition is not possible
        }
    }

    private function completeOrder(OrderEntity $orderEntity): void
    {
	// First set the order to "processing", a direct state switch to "complete" is not possible
        try {
            $this->stateMachineRegistry->transition(
                new Transition(
                    OrderDefinition::ENTITY_NAME,
                    $orderEntity->getId(),
                    StateMachineTransitionActions::ACTION_PROCESS,
                    'stateId'
                ),
                $this->context
            );
        } catch (IllegalTransitionException $e) {
            // Do nothing if the transition is not possible
        }
        // Then complete the order
        try {
            $this->stateMachineRegistry->transition(
                new Transition(
                    OrderDefinition::ENTITY_NAME,
                    $orderEntity->getId(),
                    StateMachineTransitionActions::ACTION_COMPLETE,
                    'stateId'
                ),
                $this->context
            );
        } catch (IllegalTransitionException $e) {
            // Do nothing if the transition is not possible
        }
    }
}
