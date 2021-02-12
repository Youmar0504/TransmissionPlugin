<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Command;

use Emakers\TransmissionPlugin\Services\ExactRequirements;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Emakers\TransmissionPlugin\Entity\TransmissionLogEntityCollection;
use Emakers\TransmissionPlugin\Entity\TransmissionLogEntity;

class RefreshTokenCommand extends Command
{
        protected static $defaultName = 'transmission:refresh-token';

        /**
         * @var EntityRepositoryInterface
        */
        private $transmissionLogRepository;


        public function __construct(EntityRepositoryInterface $transmissionLogRepository)
        {
                $this->transmissionLogRepository   = $transmissionLogRepository;

                parent::__construct();
        }


        protected function configure()
        {
                $this   ->setDescription('Refresh the token')
                        ->setHelp('Refresh the accessToken for Exact connection every 9 minutes');
        }

	protected function execute(InputInterface $input, OutputInterface $output)
        {
		$this->refreshToken();

		$output->writeln('Token Refreshed Successfully!');

        }

	private function refreshToken()
	{
	$file                = '/var/www/rsca/custom/plugins/TransmissionPlugin/src/Resources/tokens/tokens.txt';
        $urlRefreshToken     = "https://start.exactonline.be/api/oauth2/token";
        $headersRefreshToken = array('Content-Type: application/x-www-form-urlencoded');
        $decodedTokens       = json_decode(file_get_contents($file), true);
        $refreshToken        = $decodedTokens['refresh_token'];

	if (!$refreshToken)
        {
                $msg = ('RSCA Token is broken, Refresh it manually !');
                mail('umar@emakers.be', 'RSCA Token is broken', $msg);
                die('Token is not valid');
        }

        $bodyRefreshToken    = "refresh_token=". $refreshToken ."&grant_type=refresh_token&client_id=7c5f5e96-b219-4c5e-8497-3f24708d0f6a&client_secret=SruCXcFVdg3H";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlRefreshToken);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersRefreshToken);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyRefreshToken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

	$checkToken = json_decode($data, true);

        if (!$checkToken['access_token'])
        {
                $msg = ('RSCA Token is broken, Refresh it manually !');
                mail('umar@emakers.be', 'RSCA Token not refreshed', $msg);
		$this->transmissionLogRepository->create(
              	[
                     [
                           'status'            => 'OK',
                           'targetUrl'         => $urlRefreshToken,
                           'request'           => $bodyRefreshToken,
                           'response'          => $data,
                           'requestType'       => 'Refresh Token'
                     ],
              	],
              	\Shopware\Core\Framework\Context::createDefaultContext()
       		);

                die('Token was not refreshed, it needs to be done manually');
        }

        file_put_contents($file, $data);

	$this->transmissionLogRepository->create(
              [
                     [
                           'status'            => 'OK',
                           'targetUrl'         => $urlRefreshToken,
                           'request'           => $bodyRefreshToken,
                           'response'          => $data,
                           'requestType'       => 'Refresh Token'
                     ],
              ],
              \Shopware\Core\Framework\Context::createDefaultContext()
        );
	

	}
}
