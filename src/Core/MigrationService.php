<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool\Core;

use Appflix\DewaShop\Core\Content\Shop\ShopEntity;
use MoorlFoundation\Core\Service\DataService;
use MoorlFoundation\Core\System\DataInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
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
    private array $propertyMapping = [
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
        'new_wh' => '{MD5:ALLERGENS_H}',
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
        'new_1' => '{MD5:ADDITIVES_1}',
        'new_2' => '{MD5:ADDITIVES_2}',
        'new_3' => '{MD5:ADDITIVES_3}',
        'new_4' => '{MD5:ADDITIVES_4}',
        'new_5' => '{MD5:ADDITIVES_5}',
        'new_6' => '{MD5:ADDITIVES_6}',
        'new_7' => '{MD5:ADDITIVES_7}',
        'new_8' => '{MD5:ADDITIVES_8}',
        'new_9' => '{MD5:ADDITIVES_9}',
        'new_10' => '{MD5:ADDITIVES_10}',
        'new_11' => '{MD5:ADDITIVES_11}',
        'new_12' => '{MD5:ADDITIVES_12}',
        'new_13' => '{MD5:ADDITIVES_13}',
        'new_14' => '{MD5:ADDITIVES_14}',
    ];

    public function __construct(
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        SystemConfigService        $systemConfigService,
        DataService                $dataService,
        ?string                    $projectDir = null
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

    public function getCover(Product $migrationProduct): array
    {
        $cover = $this->dataService->getMediaId($migrationProduct->image, 'product', $this->dataObject);
        if (!$cover) {
            return [];
        }
        return ["cover" => ["id" => md5("cover" . $migrationProduct->id), "mediaId" => $cover]];
    }

    public function getProperties($value): array
    {
        $properties = [];
        if (!isset($value['id'])) {
            return $properties;
        }
        if (is_array($value['id'])) {
            foreach ($value['id'] as $item) {
                if (isset($this->propertyMapping[$item])) {
                    $properties[] = ['id' => $this->propertyMapping[$item]];
                }
            }
        } else {
            if (isset($this->propertyMapping[$value['id']])) {
                $properties[] = ['id' => $this->propertyMapping[$value['id']]];
            }
        }
        return $properties;
    }

    public function getChildren(Product $migrationProduct, int $productNumber): ?array
    {
        if (!$migrationProduct->products) {
            return [];
        }
        $variantNumber = sprintf(
            "%s.%s",
            str_pad((string)$productNumber, 4, '0', STR_PAD_LEFT),
            $this->removeSpecialChars($this->getContentInBrackets($migrationProduct->name))
        );

        $pos = 1000;

        $children = [[
            'id' => md5($variantNumber),
            'productNumber' => $variantNumber,
            'price' => sprintf("{PRICE:%s|R}", $migrationProduct->deliveryPrice),
            'stock' => 0,
            'options' => [[
                'id' => md5($this->getContentInBrackets($migrationProduct->name)),
                'name' => $this->getContentInBrackets($migrationProduct->name),
                'position' => $pos,
                'groupId' => 'e1a0160326131fd7bc82569ed88b743d'
            ]],
            'createdAt' => $this->dataObject->getCreatedAt()
        ]];
        $configuratorSettings = [[
            'id' => md5($variantNumber),
            'optionId' => md5($this->getContentInBrackets($migrationProduct->name)),
            'createdAt' => $this->dataObject->getCreatedAt()
        ]];

        foreach ($migrationProduct->products as $product) {
            $variantNumber = sprintf(
                "%s.%s",
                str_pad((string)$productNumber, 4, '0', STR_PAD_LEFT),
                $this->removeSpecialChars($this->getContentInBrackets($product->name))
            );

            $pos++;

            $children[] = [
                'id' => md5($variantNumber),
                'productNumber' => $variantNumber,
                'price' => sprintf("{PRICE:%s|R}", $product->deliveryPrice),
                'stock' => 0,
                'options' => [[
                    'id' => md5($this->getContentInBrackets($product->name)),
                    'name' => $this->getContentInBrackets($product->name),
                    'position' => $pos,
                    'groupId' => 'e1a0160326131fd7bc82569ed88b743d'
                ]],
                'createdAt' => $this->dataObject->getCreatedAt()
            ];
            $configuratorSettings[] = [
                'id' => md5($variantNumber),
                'optionId' => md5($this->getContentInBrackets($product->name))
            ];
        }

        return [
            'children' => $children,
            'configuratorSettings' => $configuratorSettings,
        ];
    }

    public function getDewaOptions(Product $migrationProduct): ?array
    {
        $dewaOptions = [];
        if (!$migrationProduct->sideDishes) {
            return null;
        }

        $pos = 1000;

        foreach ($migrationProduct->sideDishes as $sideDish) {
            $pos--;
            $choices = [];
            foreach ($sideDish->choices as $choice) {
                $properties = array_merge(
                    $this->getProperties($choice->allergens),
                    $this->getProperties($choice->additives)
                );

                $choices[] = [
                    "id" => md5($sideDish->name . $choice->name),
                    "name" => $choice->name,
                    "price" => $choice->deliveryPrice,
                    "properties" => !empty($properties) ? $properties : null,
                    'createdAt' => $this->dataObject->getCreatedAt()
                ];
            }
            $dewaOptions[] = [
                "id" => md5($migrationProduct->id . $sideDish->name),
                "isCollapsible" => count($choices) > 4,
                "priority" => $pos,
                "name" => $sideDish->name,
                "option" => [
                    "id" => md5($sideDish->name),
                    "name" => $sideDish->name,
                    "type" => $sideDish->type == "1" ? 'radio' : 'checkbox',
                    "items" => $choices,
                    'createdAt' => $this->dataObject->getCreatedAt()
                ],
                'createdAt' => $this->dataObject->getCreatedAt()
            ];
        }
        return $dewaOptions;
    }

    public function getShop(string $shopId): ShopEntity
    {
        $criteria = new Criteria([$shopId]);
        $criteria->setLimit(1);
        $shopRepository = $this->definitionInstanceRegistry->getRepository('dewa_shop');
        /** @var ShopEntity $shop */
        $shop = $shopRepository->search($criteria, $this->context)->get($shopId);
        if (!$shop) {
            throw new \Exception("No Shop selected or missing configuration.");
        }
        return $shop;
    }

    public function install(string $shopId, string $restaurantId): void
    {
        $shop = $this->getShop($shopId);

        $restaurant = new GetRestaurantRequest(
            $restaurantId,
            $shop->getZipcode(),
            $shop->getLocationLat(),
            $shop->getLocationLon()
        );

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

        $repository = $this->definitionInstanceRegistry->getRepository('dewa_shop');
        $repository->upsert($migrationShops, $this->context);

        $migrationProducts = [];
        $migrationCategoryChildren = [];
        foreach ($migrationData['categories'] as $migrationCategory) {
            /** @var Product $migrationProduct */
            foreach ($migrationCategory->products as $migrationProduct) {
                $productNumber++;

                $properties = array_merge(
                    $this->getProperties($migrationProduct->allergens),
                    $this->getProperties($migrationProduct->additives)
                );

                $extra = [];
                if ($migrationProduct->extra) {
                    $dw_deposit = isset($migrationProduct->extra['dep']) ? (float) $migrationProduct->extra['dep'] : null;
                    $dw_caffeine = isset($migrationProduct->extra['caf']) ? (float) $migrationProduct->extra['caf'] : null;
                    $dw_alcohol_percentage = isset($migrationProduct->extra['abv']) ? (float) $migrationProduct->extra['abv'] : null;
                    $dw_min_age = $dw_alcohol_percentage ? ($dw_alcohol_percentage < 10 ? 16 : 18) : null;

                    $extra = [
                        "customFields" => [
                            "dw_deposit" => $dw_deposit,
                            "dw_caffeine" => $dw_caffeine,
                            "dw_alcohol_percentage" => $dw_alcohol_percentage,
                            "dw_min_age" => $dw_min_age,
                        ],
                        "unitId" => "064d48a99d26fada884d4fa28c018758",
                        "referenceUnit" => 1,
                        "purchaseUnit" => (float) $migrationProduct->extra['ltr'],
                    ];
                }

                $migrationProducts[] = array_merge(
                    [
                        'id' => md5($migrationProduct->id),
                        'name' => $this->entferneEckigeKlammern($migrationProduct->name),
                        'description' => $migrationProduct->description,
                        "productNumber" => str_pad((string)$productNumber, 4, '0', STR_PAD_LEFT),
                        "stock" => 0,
                        "taxId" => "{TAX_ID_REDUCED}",
                        "price" => sprintf("{PRICE:%s|R}", $migrationProduct->deliveryPrice),
                        "visibilities" => [
                            ["id" => md5($migrationProduct->id . "1"), "salesChannelId" => "{SALES_CHANNEL_ID}", "visibility" => 30]
                        ],
                        "properties" => !empty($properties) ? $properties : null,
                        "categories" => [
                            ["id" => md5($migrationCategory->id)]
                        ],
                        "dewaOptions" => $this->getDewaOptions($migrationProduct),
                        "createdAt" => $this->dataObject->getCreatedAt()
                    ],
                    $this->getCover($migrationProduct),
                    $this->getChildren($migrationProduct, $productNumber),
                    $extra
                );
            }

            $migrationCategoryChildren[] = [
                "active" => true,
                'id' => md5($migrationCategory->id),
                'name' => $migrationCategory->name,
                'description' => $migrationCategory->description,
                'mediaId' => $this->dataService->getMediaId($migrationCategory->image, 'category', $this->dataObject)
            ];
        }

        $mainCatId = md5("MAINCAT1234");

        $migrationCategories = [
            [
                "id" => $mainCatId,
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
            $this->dataService->processReplace(json_encode($migrationCategories), $this->dataObject, 'category'),
            true
        );
        $this->dataService->enrichData($migrationCategories, 'category', $this->dataObject);
        $repository = $this->definitionInstanceRegistry->getRepository('category');
        $repository->delete([['id' => $mainCatId]], $this->context);
        $repository->upsert($migrationCategories, $this->context);

        $migrationProducts = json_decode(
            $this->dataService->processReplace(json_encode($migrationProducts), $this->dataObject, 'product'),
            true
        );

        $repository = $this->definitionInstanceRegistry->getRepository('product');
        $repository->upsert($migrationProducts, $this->context);
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

    private function entferneEckigeKlammern($string)
    {
        $muster = '/\[(.*?)\]/';
        $ohneKlammern = preg_replace($muster, '', $string);
        return trim($ohneKlammern);
    }

    private function getContentInBrackets($string)
    {
        preg_match('/\[(.*?)\]/', $string, $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }

    private function removeSpecialChars($string)
    {
        $pattern = '/[^a-zA-Z0-9]/';
        $string = preg_replace($pattern, '', $string);
        return $string;
    }
}
