<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Emakers\TransmissionPlugin\Entity\TransmissionEntity;
use Emakers\TransmissionPlugin\Entity\TransmissionEntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class StockPositionsController extends StorefrontController
{

    	/**
     	* @Route("/stockPositions", name="frontend.stockpositions", options={"seo"="false"}, defaults={"csrf_protected"=false, "XmlHttpRequest"=true}, methods={"GET", "POST"})
     	*/
     	public function stockPositions(RequestInterface $request): Response
     	{
		$body 		 = $request->getBody()->getContents();
		$decodedWebhook  = json_decode($body, true);
		$itemGuid	 = $decodedWebhook['Content']['Key'];
        	$currentDivision = $decodedWebhook['Content']['Division'];

		/* @var EntityRepositoryInterface $transmissionRepository */
                   $transmissionRepository = $this->container->get('transmission.repository');

                   $transmissionRepository->create(
                   [
                         [
				'productNumber' => $itemGuid,
                                'status'      	=> '99',
                                'origine'     	=> 'Exact',
                                'destination' 	=> 'Shopware',
                                'requestType' 	=> 'Update Stock',
				'createdAt' 	=> new \DateTime('UTC')
                         ],
                   ], \Shopware\Core\Framework\Context::createDefaultContext()

                        );

		die('Line added');
	}

	
}
