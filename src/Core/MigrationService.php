<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MigrationService
{
    protected ClientInterface $client;
    protected string $host;
    private DefinitionInstanceRegistry $definitionInstanceRegistry;
    private SystemConfigService $systemConfigService;
    private string $logFile;
    private string $salesChannelId;
    private bool $logEnabled;
    private Context $context;

    public function __construct(
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        SystemConfigService $systemConfigService,
        ?string $projectDir = null
    )
    {
        $this->definitionInstanceRegistry = $definitionInstanceRegistry;
        $this->systemConfigService = $systemConfigService;
        $this->logFile = $projectDir . '/var/log/dewa-migration-tool.log';

        $this->client = new Client([
            'timeout' => 10,
            'allow_redirects' => false,
        ]);

        $this->context = Context::createDefaultContext();
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    private function apiRequest(
        string $method,
        string $endpoint = '/Order',
        ?array $data = null,
        array $query = []
    )
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        $httpBody = json_encode($data);

        $query = \guzzlehttp\psr7\build_query($query);

        $title = sprintf("%s - %s", $method, $this->host . $endpoint . ($query ? "?{$query}" : ''));
        $this->apiLog($title, [
            'headers' => $headers,
            'data' => $data
        ]);

        $request = new Request(
            $method,
            $this->host . $endpoint . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );

        $response = $this->client->send($request);

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode > 299) {
            throw new \Exception(
                sprintf('[%d] Error connecting to the API (%s)', $statusCode, $request->getUri()),
                $statusCode
            );
        }

        $contents = $response->getBody()->getContents();

        try {
            $this->apiLog('Response', json_decode($contents, true));

            return json_decode($contents, true);
        } catch (\Exception $exception) {
            throw new \Exception(
                sprintf('[%d] Error decoding JSON: %s', $statusCode, $contents),
                $statusCode
            );
        }
    }

    private function apiLog(string $title, ?array $payload = null): void
    {
        if (!$this->logEnabled) {
            return;
        }

        file_put_contents(
            $this->logFile, sprintf(
            "######### %s\n%s\n%s\n\n",
            date(DATE_ATOM),
            $title,
            json_encode($payload, JSON_PRETTY_PRINT)
        ),
            FILE_APPEND
        );
    }
}
