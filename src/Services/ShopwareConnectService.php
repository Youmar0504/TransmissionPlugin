<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Services;

use Doctrine\DBAL\Connection;
use Emakers\TransmissionPlugin\Services\SendMessageService;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Article\Detail as ArticleDetail;
use Shopware\Models\Order\Order;
use Shopware\Models\Article\Article;
use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;
use Emakers\TransmissionPlugin\Subscriber\OrderSubscriber;
use Emakers\TransmissionPlugin\Services\FormatMessageService;
use Emakers\TransmissionPlugin\Services\ExactDataService;
use Emakers\TransmissionPlugin\Services\GettingInfoService;


class ShopwareConnectService
{

    public function MessageProcess($orderId, $orderNumber, $orderObject, $transmissionLogRepository, $container, $destination, $shippingMethod)
    {
        $orderInfo = (new GettingInfoService)->getInfo($orderId, $orderNumber, $orderObject, $container, 'Order');

        if ($destination == 'Makro') {
          $formattedMessage   = (new FormatMessageService)->formatOrderMessageMakro($orderInfo);
        }
        elseif ($destination == 'Distrimedia') {
          $formattedMessage   = (new FormatMessageService)->formatOrderMessageEDI($orderInfo);
        }
        elseif ($destination == 'Exact') {
            $formattedMessage = (new FormatMessageService)->formatOrderMessageExact($orderInfo, $orderNumber, $shippingMethod, $transmissionLogRepository, $container);
            $accessToken      = (new ExactDataService)->accessToken();

        }

        $sendMessage = (new SendMessageService)->sendOrder($formattedMessage, $orderNumber, $transmissionLogRepository);

    }

    //Send the invoice to Exact at its generation after the payment of a new order
    public function InvoiceProcess($orderId, $entryNumber, $orderObject, $container, $transmissionLogRepository, $destination)
    {
        $orderInfo        = (new GettingInfoService)->getInfo($orderId, $entryNumber, $orderObject, $container, 'Invoice');
        $formattedMessage = (new FormatMessageService)->formatInvoiceMessageExact($orderInfo, $entryNumber, $orderObject, $transmissionLogRepository, $container);

        $sendEntry        = (new SendMessageService)->sendEntry($formattedMessage, $entryNumber, $transmissionLogRepository);
    }
}

