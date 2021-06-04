<?php

namespace Pagarme\Pagarme\Model\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Exception as MagentoException;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\Manager;
use Pagarme\Pagarme\Api\HubCommandInterface;
use Pagarme\Pagarme\Concrete\Magento2CoreSetup;
use Pagarme\Core\Hub\Services\HubIntegrationService;

class HubCommand implements HubCommandInterface
{
    /**
     * @var Request
     */
    protected $request;


    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var Manager
     */
    protected $cacheManager;

    public function __construct(
        Request $request,
        WriterInterface $configWriter,
        Manager $cacheManager
    ) {
        $this->request = $request;
        $this->configWriter = $configWriter;
        $this->cacheManager = $cacheManager;

        Magento2CoreSetup::bootstrap();
    }

    public function execute()
    {
        $params = json_decode(
            json_encode($this->request->getBodyParams())
        );

        $hubIntegrationService = new HubIntegrationService();
        try {
            $hubIntegrationService->executeCommandFromPost($params);
        } catch (\Throwable $e) {
            throw new MagentoException(
                __($e->getMessage()),
                0,
                400
            );
        }

        $command = strtolower($params->command) . 'Command';

        if (method_exists($this, $command)) {
            $commandMessage = $this->$command();
        } else {
            $commandMessage = "Command $params->command executed successfully";
        }

        return new $commandMessage;
    }

    public function uninstallCommand()
    {

        $this->configWriter->save(
            "pagarme_pagarme/hub/install_id",
            null
        );

        $this->configWriter->save(
            "pagarme_pagarme/hub/access_token",
            null
        );

        $this->configWriter->save(
            "pagarme_pagarme/global/secret_key",
            null
        );

        $this->configWriter->save(
            "pagarme_pagarme/global/public_key",
            null
        );

        $this->cacheManager->clean(['config']);

        return "Hub uninstalled successfully";
    }
}
