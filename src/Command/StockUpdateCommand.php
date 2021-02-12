<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Command;

use Emakers\TransmissionPlugin\Services\ExactDataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Emakers\TransmissionPlugin\Entity\TransmissionEntityCollection;
use Emakers\TransmissionPlugin\Entity\TransmissionEntity;

class StockUpdateCommand extends Command
{
    	protected static $defaultName = 'transmission:stock-update';

	/**
	 * @ContainerInterface $container
	*/
	private $container;

	/**
     	 * @var EntityRepositoryInterface
     	*/
    	private $transmissionRepository;


    	public function __construct(ContainerInterface $container, EntityRepositoryInterface $transmissionRepository)
	{
		$this->container		= $container;
		$this->transmissionRepository  	= $transmissionRepository;

		parent::__construct();
    	}


	protected function configure()
	{
		$this	->setDescription('Update the stock')
		     	->setHelp('Update the stock of the latest product in the db. Runs twice a minute');
	}

    	protected function execute(InputInterface $input, OutputInterface $output)
    	{
		$criteria = new Criteria();
		$criteria->addFilter(new EqualsFilter('status', 99));
		$criteria->setLimit(1);
		$criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));

		$transObject	= $this->transmissionRepository->search($criteria,  \Shopware\Core\Framework\Context::createDefaultContext());

		if (!$transObject->first())
		{
			$output->writeln('There is no product to update');
			die('No product to update');
		}

		$itemGuid 	= $transObject->first()->getProductNumber();
		$transId	= $transObject->first()->getId();

		$accessToken     = (new ExactDataService)->accessToken();

		$rscaItem	= $this->rscaItem($itemGuid, $transId, $accessToken);

		if ($rscaItem['itemNumber'] != 'FAN000423')
		{
			if ($rscaItem['isRscaItem'])
			{
				$storageId 	  = $this->getStorageId($itemGuid, $accessToken);
				$inStock 	  = $this->getStock($storageId, $accessToken);
				//$planningOutStock = $this->getPlanningOutStock($itemGuid, $accessToken);
				$stock		  = $inStock;  
				$this->updateItemStock($transId, $rscaItem['itemNumber'], $stock);

				$output->writeln('Stock updated successfully !');
			}
		}
		else
		{
			$this->transmissionRepository->delete(
                                [
                                        ['id' => $transId],
                                ],
                                \Shopware\Core\Framework\Context::createDefaultContext()
                        );
			$output->writeln('Product not to sync for the moment');
		}


    	}

	private function rscaItem($itemGuid, $transId, $accessToken)
	{
		$urlGetItem      = "https://start.exactonline.be/api/v1/291548/logistics/Items?\$filter=ID eq guid'". $itemGuid ."'";
                $headersGetItem  = array('Authorization: Bearer ' .$accessToken. '');

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlGetItem);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersGetItem);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $data = curl_exec($ch);

                $dom = new \DOMDocument();
                @$dom->loadHTML($data);

                $customerObject = $dom->getElementsByTagName('class_01');
                $customer       = $customerObject[0]->nodeValue;
		$isRscaItem = TRUE;

                if ($customer != 'RSCA')
                {
			$this->transmissionRepository->delete(
				[
					['id' => $transId],
				],
				\Shopware\Core\Framework\Context::createDefaultContext()
			);
			$isRscaItem = FALSE;
                        die('This is not a product for RSCA Shopware');
                }

		$itemNumberObject          = $dom->getElementsByTagName('code');
                $itemNumber                = $itemNumberObject[0]->nodeValue;

		return [
				'isRscaItem' 	=> $isRscaItem,
				'itemNumber' 	=> $itemNumber
			];

	}

	private function getStorageId($itemGuid, $accessToken)
        {
                $urlGetStorageId      = "https://start.exactonline.be/api/v1/291548/inventory/ItemWarehouses?\$filter=Item eq guid'". $itemGuid ."' and WarehouseCode eq '1'&\$select=ID";
                $headersGetStorageId  = array('Authorization: Bearer ' .$accessToken. '');

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlGetStorageId);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersGetStorageId);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $data = curl_exec($ch);

                $dom = new \DOMDocument();
                @$dom->loadHTML($data);

		$storageObject = $dom->getElementsByTagName('id');
               	$storageId     = $storageObject[2]->nodeValue;

                return $storageId;

        }


	 private function getStock($storageId, $accessToken)
        {
                $urlGetStock      = "https://start.exactonline.be/api/v1/291548/inventory/ItemWarehouseStorageLocations?\$filter= ID eq guid'". $storageId ."'&\$select=Stock";
                $headersGetStock  = array('Authorization: Bearer ' .$accessToken. '');

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlGetStock);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersGetStock);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $data = curl_exec($ch);

                $dom = new \DOMDocument();
                @$dom->loadHTML($data);

                $stockObject    = $dom->getElementsByTagName('stock');
                $stock 		= $stockObject[0]->nodeValue;

                return $stock;
        }
	
	private function getPlanningOutStock($itemGuid, $accessToken)
	{
		$url = "https://start.exactonline.be/api/v1/291548/read/logistics/StockPosition?itemId=guid'" . $itemGuid . "'";
		$header = array('Authorization: Bearer ' .$accessToken. '');
	
		$ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $data = curl_exec($ch);

                $dom = new \DOMDocument();
                @$dom->loadHTML($data);

                $planningOutObject    = $dom->getElementsByTagName('planningout');
                $planningOut          = $planningOutObject[0]->nodeValue;

                return $planningOut;
	}

        private function updateItemStock($transId, $itemNumber, $stock)
        {
                /* @var EntityRepositoryInterface $productRepository */
                $productRepository = $this->container->get('product.repository');
		$checkSHCR = substr($itemNumber, 0, 12);

		if ($checkSHCR == '2021.HT.1010' || $checkSHCR == '2021.HT.1080')
		{
			$itemNumberSC = 'SHCR.' . $itemNumber;

                	/** @var EntityCollection $productId */
			$checkProduct = $productRepository->search(
                                (new Criteria())->addFilter(new EqualsFilter('productNumber', $itemNumberSC)),
                                \Shopware\Core\Framework\Context::createDefaultContext()
                                )->first();

                        if ($checkProduct)
                        {
                                $productId = $checkProduct->getId();
                        	$productRepository->update(
                        	[
                                	[ 'id' => $productId, 'stock' => intval($stock) ],
                        	],
                        	\Shopware\Core\Framework\Context::createDefaultContext()
                        	);

                        	$this->transmissionRepository->update(
                        	[
                                	[
					'id'		=> $transId,
                                        'productNumber' => $itemNumberSC,
                                        'status'      	=> '100',
					'updatedAt'	=> new \Datetime('UTC'),
                                	],
                        	], \Shopware\Core\Framework\Context::createDefaultContext()

                        	);
                	}
		}

		/** @var EntityCollection $productId */
		 $checkProduct = $productRepository->search(
                                (new Criteria())->addFilter(new EqualsFilter('productNumber', $itemNumber)),
                                \Shopware\Core\Framework\Context::createDefaultContext()
                                )->first();

                        if ($checkProduct)
                        {
				$productId = $checkProduct->getId();
                                $productRepository->update(
                                [
                                        [ 'id' => $productId, 'stock' => intval($stock) ],
                                ],
                                \Shopware\Core\Framework\Context::createDefaultContext()
                                );

                                $this->transmissionRepository->update(
                                [
                                        [
                                        'id'            => $transId,
                                        'productNumber' => $itemNumber,
                                        'status'        => '100',
                                        'updatedAt'     => new \Datetime('UTC'),
                                        ],
                                ], \Shopware\Core\Framework\Context::createDefaultContext()

                                );

                        }
			else
			{
				$this->transmissionRepository->update(
					[
							[
							'id'            => $transId,
							'productNumber' => $itemNumber,
							'status'        => '90',
							'updatedAt'     => new \Datetime('UTC'),
							],
					], \Shopware\Core\Framework\Context::createDefaultContext()

					);

				die('This product is not yet existing in Shopware');

			}
        }
}


