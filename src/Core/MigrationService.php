<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool\Core;

use Appflix\DewaShop\Core\Content\Shop\ShopEntity;
use MoorlFoundation\Core\Service\DataService;
use MoorlFoundation\Core\System\DataInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Takeaway\Http\Requests\GetRestaurantRequest;

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
    private DataService $dataService;
    private DataInterface $dataObject;

    public function __construct(
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        SystemConfigService $systemConfigService,
        DataService $dataService,
        ?string $projectDir = null
    )
    {
        $this->definitionInstanceRegistry = $definitionInstanceRegistry;
        $this->systemConfigService = $systemConfigService;
        $this->dataService = $dataService;
        $this->logFile = $projectDir . '/var/log/dewa-migration-tool.log';

        $this->client = new Client([
            'timeout' => 10,
            'allow_redirects' => false,
        ]);

        $this->dataObject = new MigrationDataObject();
        $this->context = Context::createDefaultContext();
    }

    public function remove(): void
    {
        $this->dataService->cleanUpShopwareTables($this->dataObject);
    }

    public function install(string $shopId, string $restaurantId): void
    {
        $criteria = new Criteria([$shopId]);
        $criteria->setLimit(1);

        $shopRepository = $this->definitionInstanceRegistry->getRepository('dewa_shop');

        /** @var ShopEntity $shop */
        $shop = $shopRepository->search($criteria, $this->context)->get($shopId);

        if (!$shop) {
            throw new \Exception("No Shop selected or missing configuration.");
        }

        $restaurant = new GetRestaurantRequest(
            $restaurantId,
            $shop->getZipcode(),
            $shop->getLocationLat(),
            $shop->getLocationLon()
        );

        if (empty($restaurant)) {
            throw new \Exception("No Restaurant found, please be sure you have configured a valid shop and a valid restaurant ID from takeaway.");
        }

        $this->dataService->initTaxes();
        $this->dataService->initGlobalReplacers($this->dataObject);

        $migrationData = $restaurant->getData();
        $productNumber = 0;

        $migrationShops = [[
            'id' => $shop->getId(),
            'name' => $migrationData['name'],
            'mediaId' => $this->dataService->getMediaId($migrationData['logo'], 'cms_page', $this->dataObject),
            'street' => $migrationData['street'],
            'city' => $migrationData['city'],
            'zipCode' => $migrationData['postalCode'],
            'locationLat' => $migrationData['latitude'],
            'locationLon' => $migrationData['longitude']
        ]];

        $this->dataService->enrichData($migrationShops, 'dewa_shop', $this->dataObject);

        /** @var EntityRepositoryInterface $repository */
        $repository = $this->definitionInstanceRegistry->getRepository('dewa_shop');
        $repository->upsert($migrationShops, $this->context);

        //dump($migrationData);exit;

        $migrationCategoryChildren = [];
        foreach ($migrationData['categories'] as $migrationCategory) {
            $migrationProducts = [];

            foreach ($migrationCategory->__get('products') as $migrationProduct) {
                $productNumber++;

                $cover = $this->dataService->getMediaId($migrationProduct->image, 'product', $this->dataObject);
                if ($cover) {
                    $cover = [
                        "cover" => [
                            "id" => md5("cover" . $migrationProduct->id),
                            "mediaId" => $cover
                        ]
                    ];
                } else {
                    $cover = [];
                }

                $migrationProducts[] = array_merge([
                    'id' => md5($migrationProduct->id),
                    'name' => $migrationProduct->name,
                    'description' => $migrationProduct->description,
                    "productNumber" => str_pad((string)$productNumber, 4, '0', STR_PAD_LEFT),
                    "stock" => 100,
                    "taxId" => "{TAX_ID_REDUCED}",
                    "price" => (float) $migrationProduct->deliveryPrice,
                    "listPrice" => (float) $migrationProduct->deliveryPrice,
                    "visibilities" => [
                        [
                            "salesChannelId" => "{SALES_CHANNEL_ID}",
                            "visibility" => 30
                        ]
                    ],
                ], $cover);
            }

            $migrationCategoryChildren[] = [
                "active" => true,
                'id' => md5($migrationCategory->id),
                'name' => $migrationCategory->name,
                'mediaId' => $this->dataService->getMediaId($migrationCategory->image, 'category', $this->dataObject),
                'products' => $migrationProducts
            ];
        }

        $migrationCategories = [
            [
                "parentId" => "{NAVIGATION_CATEGORY_ID}",
                "cmsPageId" => "{CMS_PAGE_ID}",
                "active" => true,
                "name" => $migrationData['name'],
                'mediaId' => $this->dataService->getMediaId($migrationData['header'], 'category', $this->dataObject),
                "children" => $migrationCategoryChildren
            ]
        ];

        $migrationCategories = json_decode(strtr(json_encode($migrationCategories), $this->dataObject->getGlobalReplacers()), true);

        $this->dataService->enrichData($migrationCategories, 'category', $this->dataObject);

        /** @var EntityRepositoryInterface $repository */
        $repository = $this->definitionInstanceRegistry->getRepository('category');
        $repository->upsert($migrationCategories, $this->context);
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
