<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool\Core;

use Appflix\DewaShop\Core\Content\Shop\ShopEntity;
use MoorlFoundation\Core\Service\DataService;
use MoorlFoundation\Core\System\DataInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Takeaway\Http\Requests\GetRestaurantRequest;
use Takeaway\Models\Product;

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
    private array $allergensMapping = [
        'new_a' => '{MD5:ALLERGENS_A}',
        'new_a1' => '{MD5:ALLERGENS_A1}',
        'new_a2' => '{MD5:ALLERGENS_A2}',
        'new_a3' => '{MD5:ALLERGENS_A3}',
        'new_a4' => '{MD5:ALLERGENS_A4}',
        'new_a5' => '{MD5:ALLERGENS_A5}',
        'new_b' => '{MD5:ALLERGENS_B}',
        'new_c' => '{MD5:ALLERGENS_C}',
        'new_d' => '{MD5:ALLERGENS_D}',
        'new_e' => '{MD5:ALLERGENS_E}',
        'new_f' => '{MD5:ALLERGENS_F}',
        'new_g' => '{MD5:ALLERGENS_G}',
        'new_h' => '{MD5:ALLERGENS_H}',
        'new_h1' => '{MD5:ALLERGENS_H1}',
        'new_h2' => '{MD5:ALLERGENS_H2}',
        'new_h3' => '{MD5:ALLERGENS_H3}',
        'new_h4' => '{MD5:ALLERGENS_H4}',
        'new_h5' => '{MD5:ALLERGENS_H5}',
        'new_l' => '{MD5:ALLERGENS_L}',
        'new_m' => '{MD5:ALLERGENS_M}',
        'new_n' => '{MD5:ALLERGENS_N}',
        'new_o' => '{MD5:ALLERGENS_O}',
        'new_p' => '{MD5:ALLERGENS_P}',
        'new_r' => '{MD5:ALLERGENS_R}',
    ];

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
            //'mediaId' => $this->dataService->getMediaId($migrationData['logo'], 'cms_page', $this->dataObject),
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

        $migrationCategoryChildren = [];
        foreach ($migrationData['categories'] as $migrationCategory) {
            $migrationProducts = [];

            /** @var Product $migrationProduct */
            foreach ($migrationCategory->__get('products') as $migrationProduct) {
                //dd($migrationProduct);

                $productNumber++;

                /*$cover = $this->dataService->getMediaId($migrationProduct->image, 'product', $this->dataObject);
                if ($cover) {
                    $cover = [
                        "cover" => [
                            "id" => md5("cover" . $migrationProduct->id),
                            "mediaId" => $cover
                        ]
                    ];
                } else {
                    $cover = [];
                }*/

                $cover = [];
                $dewaOptions = [];
                $configuratorSettings = [];
                $children = [];
                if ($migrationProduct->sideDishes) {
                    if (is_array($migrationProduct->sideDishes)) {
                        foreach ($migrationProduct->sideDishes as $sideDish) {
                            if ($sideDish->type == "1") {
                                foreach ($sideDish->choices as $choice) {
                                    $children[] = [
                                        'id' => md5($migrationProduct->id.$choice->id),
                                        "productNumber" => str_pad((string)$productNumber, 4, '0', STR_PAD_LEFT) . "." . $choice->id,
                                        "taxId" => "{TAX_ID_REDUCED}",
                                        "price" => sprintf("{PRICE:%s|R}", $choice->deliveryPrice),
                                        "options" => [
                                            [
                                                "id" => md5($choice->id),
                                                "name" => $choice->name,
                                                "group" => [
                                                    "id" => md5($sideDish->name),
                                                    "name" => $sideDish->name,
                                                ]
                                            ]
                                        ]
                                    ];

                                    dd($children);

                                    $configuratorSettings[] = [
                                        'optionId' => md5($choice->id)
                                    ];
                                }
                            } else {
                                $choices = [];
                                foreach ($sideDish->choices as $choice) {
                                    $properties = [];
                                    if (isset($choice->allergens['id'])) {
                                        if (is_array($choice->allergens['id'])) {
                                            foreach ($choice->allergens['id'] as $allergen) {
                                                if (isset($this->allergensMapping[$allergen])) {
                                                    $properties[] = [
                                                        'id' => $this->allergensMapping[$allergen]
                                                    ];
                                                }
                                            }
                                        } else {
                                            if (isset($this->allergensMapping[$choice->allergens['id']])) {
                                                $properties[] = [
                                                    'id' => $this->allergensMapping[$choice->allergens['id']]
                                                ];
                                            }
                                        }
                                    }

                                    $choices[] = [
                                        "id" => md5($choice->id),
                                        "name" => $choice->name,
                                        "price" => $choice->deliveryPrice,
                                        "properties" => $properties
                                    ];
                                }

                                $dewaOptions[] = [
                                    'id' => md5($migrationProduct->id . $sideDish->name),
                                    'name' => $sideDish->name,
                                    "option" => [
                                        "id" => md5($sideDish->name),
                                        "name" => $sideDish->name,
                                        "type" => 'checkbox',
                                        "items" => $choices
                                    ],
                                ];
                            }
                        }
                    }
                }

                $properties = [];
                if (isset($migrationProduct->allergens['id'])) {
                    if (is_array($migrationProduct->allergens['id'])) {
                        foreach ($migrationProduct->allergens['id'] as $allergen) {
                            if (isset($this->allergensMapping[$allergen])) {
                                $properties[] = [
                                    'id' => $this->allergensMapping[$allergen]
                                ];
                            }
                        }
                    } else {
                        if (isset($this->allergensMapping[$migrationProduct->allergens['id']])) {
                            $properties[] = [
                                'id' => $this->allergensMapping[$migrationProduct->allergens['id']]
                            ];
                        }
                    }
                }

                $migrationProducts[] = array_merge([
                    'id' => md5($migrationProduct->id),
                    'name' => $migrationProduct->name,
                    'description' => $migrationProduct->description,
                    "productNumber" => str_pad((string)$productNumber, 4, '0', STR_PAD_LEFT),
                    "stock" => 0,
                    "taxId" => "{TAX_ID_REDUCED}",
                    "price" => sprintf("{PRICE:%s|R}", $migrationProduct->deliveryPrice),
                    "visibilities" => [
                        [
                            "id" => md5($migrationProduct->id . "1"),
                            "salesChannelId" => "{SALES_CHANNEL_ID}",
                            "visibility" => 30
                        ]
                    ],
                    "properties" => $properties,
                    "children" => $children,
                    "configuratorSettings" => $configuratorSettings,
                    "dewaOptions" => $dewaOptions,
                ], $cover);
            }

            $migrationCategoryChildren[] = [
                "active" => true,
                'id' => md5($migrationCategory->id),
                'name' => $migrationCategory->name,
                'description' => $migrationCategory->description,
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
                'description' => $migrationCategory->description,
                'mediaId' => $this->dataService->getMediaId($migrationData['header'], 'category', $this->dataObject),
                "children" => $migrationCategoryChildren
            ]
        ];

        $migrationCategories = json_decode(
            $this->dataService->processReplace(json_encode($migrationCategories), $this->dataObject),
            true
        );

        echo json_encode($migrationCategories);exit;

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
