<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Services;

class SendMessageService
{

    public function sendOrder($formattedMessage, $orderNumber, $transmissionLogRepository)
    {
        $ch = curl_init();
        $url = "https://start.exactonline.be/api/v1/291548/salesorder/SalesOrders";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedMessage['headers']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formattedMessage['contentOrder']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode = 201 || $statusCode = 200)
        {
            $status = 'OK';
        }
        else
        {
	    $to = 'umar@emakers.be, stefan@emakers.be, support@emakers.be';	
	    mail($to, "Error with ". $orderNumber ."" , "Order not sent");
            $status = 'NOK';
        }


	$transmissionLogRepository->create(
                            [
                                [
                                    'orderNumber'       => $orderNumber,
                                    'status'            => $status,
                                    'targetUrl'         => $url,
                                    'request'           => $formattedMessage['contentOrder'],
                                    'response'          => $result,
                                    'requestType'       => 'Orders'
                                ],
                            ],
                            \Shopware\Core\Framework\Context::createDefaultContext()
        );
    }


    public function sendEntry($formattedMessage, $entryNumber, $transmissionLogRepository)
    {
        $ch = curl_init();
        $url = "https://start.exactonline.be/api/v1/291548/salesentry/SalesEntries";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedMessage['headersEntryLines']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formattedMessage['contentEntryLines']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode = 201 || $statusCode = 200)
        {
            $status = 'OK';
        }
        else
        {
	    $to = 'umar@emakers.be, stefan@emakers.be, support@emakers.be';
	    mail($to, "Error with ". $entryNumber. "" , "Invoice not sent");
            $status = 'NOK';
        }

        $transmissionLogRepository->create(
                            [
                                [
                                    'orderNumber'       => intval($entryNumber),
                                    'status'            => $status,
                                    'targetUrl'         => $url,
				    'request'           => $formattedMessage['contentEntryLines'],
                                    'response'          => $result,
                                    'requestType'       => 'Sending Invoice'
                                ],
                            ],
                            \Shopware\Core\Framework\Context::createDefaultContext()
        );
	
    }
}
