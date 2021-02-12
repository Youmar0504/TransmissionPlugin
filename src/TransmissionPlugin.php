<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Components\Model\ModelManager;

class TransmissionPlugin extends Plugin {

    public function install(InstallContext $context): void
    {
    }


    public function uninstall(UninstallContext $context): void 
    {
    }


    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
    }
    
    
    private function getTransmissionModelMetaData() {
        return [$this->container->get('models')->getClassMetaData(Models\Transmission::class)];
    }

    private function getTransmissionLogModelMetaData() {
        return [$this->container->get('models')->getClassMetaData(Models\Log::class)];
    }

    private function getTransmissionInformation()   
    {
        $sql = "
            SELECT
                t.id,
                t.orderNumber,
                t.productNumber,
		t.customerId,
                t.status,
                t.origine,
                t.destination,
                t.requestType,
                t.creationDate,
                t.lastModifiedDate
            FROM s_transmission t
            ORDER BY t.lastModifiedDate DESC
        ";

        $this->container->get('dbal_connection')->fetchAll($sql);
    }
    
    private function getLogInformation()
    {
        $sql = "
            SELECT
                l.orderNumber,
                l.targetUrl,
                l.requestType,
                l.request,
                l.response,
                l.status,
                l.creationDate
            FROM s_transmission_logs l
        ";

        $this->container->get('dbal_connection')->fetchAll($sql);
    }

    private function webhookSubscription($accessToken, $currentDivision, $topic)
    {
        $now            = ('Y:m:d H:i:s');
        $urlWebhook     = "https://start.exactonline.be/api/v1/". $currentDivision ."/webhooks/WebhookSubscriptions";
        $headersWebhook = array('Authorization: Bearer '. $accessToken .'',
                                'Content-Type: application/json');
        $bodyWebhook    = "{
                            'CallbackURL': 'https://sanjoya.eu/frontend/". $topic ."',
                            'Topic': '".$topic."'
                            }";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlWebhook);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersWebhook);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyWebhook);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

	$mysqli = mysqli_connect("localhost", "rsca", "Z7G9ULF4vJiv6QRRX7jooKWN", "rsca");
        $insertLogs = "INSERT INTO s_transmission_logs (requestType, targetUrl, request, response, status, creationdate) values ('".$topic."', '".$urlWebhook."', '" .$topic. "' ,'".$data."', '".$statusCode."', '".$now."')";
        $result = $mysqli->query($insertLogs);
    }
   
    
}
