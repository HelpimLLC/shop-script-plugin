<?php
//cron /usr/bin/php ~/path/to/cli.php shop helpimGenerateYml
//ICML address http://URL/wa-data/public/shop/helpim/product.xml
class shopHelpimGenerateYmlCli extends shopHelpimAbstractCli
{
    public $settings;
    public $dd;
    public $eCategories;
    public $eOffers;

    public $category;
    private $serviceCategoryId;

    public function execute()
    {
        $app_settings_model = new waAppSettingsModel();
        $this->settings["shopname"] = $app_settings_model->get('shop','name');;
        $this->settings["siteurl"] = $app_settings_model->get('webasyst','url');

        $companyName = htmlspecialchars($app_settings_model->get('webasyst', 'name', 'Webasyst'), ENT_QUOTES, 'utf-8');
        $shop = "";
        if (!isset($this->settings["shopname"]) || empty($this->settings["shopname"])) {
            $shop = $companyName;
        } else {
            $shop = htmlspecialchars($this->settings["shopname"]);
        }
        $company = "";
        if (!isset($this->settings["companyname"]) || empty($this->settings["companyname"])) {
            $company = $companyName;
        } else {
            $company = htmlspecialchars($this->settings["companyname"]);
        }
        $string = '<?xml version="1.0" encoding="UTF-8"?>
            <yml_catalog date="' . date('Y-m-d H:i:s') . '">
                <shop>
                    <name>' . $shop . '</name>
                    <company>' . $company .'</company>
                    <categories/>
                    <offers/>
                </shop>
            </yml_catalog>
        ';
        $xml = new SimpleXMLElement($string, LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);

        $this->dd = new DOMDocument();
        $this->dd->preserveWhiteSpace = false;
        $this->dd->formatOutput = false;
        $this->dd->loadXML($xml->asXML());

        $this->eCategories = $this->dd->getElementsByTagName('categories')->item(0);
        $this->eOffers = $this->dd->getElementsByTagName('offers')->item(0);

        $this->addCategories();
        $this->addOffers();

        $this->dd->saveXML();
        $dir = waConfig::get('wa_path_data') . '/public/shop/helpim/';
        if (!file_exists($dir)) {
            mkdir($dir, 0755);
        }
        $this->dd->save($dir . 'product.xml');

        $this->addLog('catalog_export', 0, 'Файл экспорта каталога сгенерирован');
    }

    public function addCategories()
    {
        $e = $this->eCategories->appendChild($this->dd->createElement('category', "Без категории"));
        $e->setAttribute('id', 0);

        $category_model = new shopCategoryModel();
        foreach ($category_model->getAll() as $key => $value) {
            if (empty($value['name']) || !isset($value['name'])) {
                continue;
            }

            $e = $this->eCategories->appendChild($this->dd->createElement('category', htmlspecialchars($value['name'])));
            $e->setAttribute('id', $value['id']);

            if ($value['parent_id'] != 0) {
                $e->setAttribute('parentId', $value['parent_id']);
            }
            $this->category[$value["id"]] = $value["full_url"];
        }
        /* Add service category */
        $this->serviceCategoryId = $value['id'] + 1;
        $e = $this->eCategories->appendChild($this->dd->createElement('category', "Доп. услуги"));
        $e->setAttribute('id', $this->serviceCategoryId);
    }

    public function addOffers()
    {
        /* Добавление сервисов */
        $serviceModel = new shopServiceModel();
        foreach ($serviceModel->getAll() as $service) {
            $e = $this->eOffers->appendChild($this->dd->createElement('offer'));
            $e->setAttribute('id', 'service_' . $service['id']);
            $e->appendChild($this->dd->createElement('price', $service['price']));
            $e->appendChild($this->dd->createElement('categoryId', $this->serviceCategoryId));
            $e->appendChild($this->dd->createElement('name', $service['name']));
            $e->appendChild($this->dd->createElement('productName', $service['name']));
            $desc = $this->dd->createElement('param', $service['description']);
            $desc->setAttribute('name', 'Описание');
            $desc->setAttribute('code', 'description');
            $e->appendChild($desc);
        }

        /* Добавление продуктов */
        $productModel = new shopProductModel();
        $product = array();
        foreach ($productModel->getAll() as $key => $value) {
            $product[$value["id"]] = $value;
        }

        $productParamsModel = new shopProductFeaturesModel();
        $productFields = array();

        $productValueModel = null;
        if (method_exists("shopFeatureModel", "getByProduct")) {
            $productValueModel = new shopFeatureModel();
        } else {
            $productValueModel = new shopHelpimFeatureModel();
        }

        $fields = array();
        foreach ($productParamsModel->getAll() as $key => $val) {
            if (!isset($fields) || empty($fields)) {
                $fields = $productValueModel->getValues($productValueModel->getByProduct($val["product_id"]));
            }
            if (!isset($fields[ $val["feature_id"] ])) {
                continue;
            }
            $tmpfields = $fields[ $val["feature_id"] ];
            $productFields[ $val["product_id"] ][ $tmpfields["code"] ] = $tmpfields["values"][ $val["feature_value_id"] ];
        }

        $skusModel = new shopProductSkusModel();
        foreach ($skusModel->getAll() as $key => $val) {
            $val["fields"] = (isset($productFields[ $val["product_id"] ])) ? $productFields[ $val["product_id"] ] : array();
            $e = $this->eOffers->appendChild($this->dd->createElement('offer'));

            $e->setAttribute('id', $val['id']);
            $e->setAttribute('productId', $val['product_id']);
            $e->setAttribute('quantity', (empty($val['count'])) ? 0 : $val['count']);
            $e->setAttribute('available', $val["available"] ? 'true' : 'false');

            $category = $product[ $val["product_id"] ]["category_id"];
            if (empty($category)) {
                $category = 0;
            }
            $e->appendChild($this->dd->createElement('categoryId', $category));

            $name = $product[ $val["product_id"] ]["name"].$val['name'];
            $productName = $product[ $val["product_id"] ]["name"];
            if (empty($name)) {
                $name = $productName;
            }

            $e->appendChild($this->dd->createElement('name'))->appendChild($this->dd->createTextNode(htmlspecialchars($name)));
            $e->appendChild($this->dd->createElement('productName'))->appendChild($this->dd->createTextNode(htmlspecialchars($productName)));

            $e->appendChild($this->dd->createElement('price', $val['primary_price']));
            if ($val["purchase_price"] > 0) {
                $e->appendChild($this->dd->createElement('purchasePrice', $val["purchase_price"]));
            }

            $e->appendChild($this->dd->createElement('xmlId'))->appendChild($this->dd->createTextNode(htmlspecialchars($val["sku"])));

            $image = array(
                "product_id" => $val["product_id"],
                "id"         => (!empty($val["image_id"]) && $val["image_id"] != 0) ? $val["image_id"] : $product[ $val["product_id"] ]["image_id"],
                "ext"        => $product[ $val["product_id"] ]["ext"]
            );

            //if (!empty($image["image_id"]) && !empty($image["product_id"])) {
                $image = $this->getUrl($image);
                $e->appendChild($this->dd->createElement('picture', $image));
            //}

            $url = $this->settings["siteurl"];
            if (isset($this->settings["routing"]["catalog"]) && !empty($this->settings["routing"]["catalog"])) {
                $url .= $this->settings["routing"]["catalog"] . "/";
            }
            if (isset($this->settings["routing"]["addcategory"]) && isset($this->category[ $category ])) {
                $url .= $this->category[ $category ] . "/";
            }
            $url .= $product[ $val["product_id"] ]["url"];
            $e->appendChild($this->dd->createElement('url', $url));

            $art = $val["sku"];
            if (isset($this->settings["offers"]["article"]) && !empty($this->settings["offers"]["article"])) {
                if (isset($val["fields"][ $this->settings["offers"]["article"] ])
                    && !empty($val["fields"][ $this->settings["offers"]["article"] ]))
                {
                    $art = $val["fields"][ $this->settings["offers"]["article"] ];
                }
            }

            if (!empty($art)) {
                $article = $this->dd->createElement('param');
                $article->setAttribute('name', 'article');
                $article->appendChild($this->dd->createTextNode($art));
                $e->appendChild($article);
            }

            if (isset($this->settings["offers"]["size"]) && !empty($this->settings["offers"]["size"])) {
                if (isset($val["fields"][ $this->settings["offers"]["size"] ])
                    && !empty($val["fields"][ $this->settings["offers"]["size"] ]))
                {
                    $size = $this->dd->createElement('param');
                    $size->setAttribute('name', 'size');
                    $size->appendChild($this->dd->createTextNode($val["fields"][ $this->settings["offers"]["size"] ]));
                    $e->appendChild($size);
                }
            }

            if (isset($this->settings["offers"]["color"]) && !empty($this->settings["offers"]["color"])) {
                if (isset($val["fields"][ $this->settings["offers"]["color"] ])
                    && !empty($val["fields"][ $this->settings["offers"]["color"] ]))
                {
                    $color = $this->dd->createElement('param');
                    $color->setAttribute('name', 'color');
                    $color->appendChild($this->dd->createTextNode($val["fields"][ $this->settings["offers"]["color"] ]));
                    $e->appendChild($color);
                }
            }

            if (isset($this->settings["offers"]["weight"]) && !empty($this->settings["offers"]["weight"])) {
                if (isset($val["fields"][ $this->settings["offers"]["weight"] ])
                    && !empty($val["fields"][ $this->settings["offers"]["weight"] ]))
                {
                    $weight = $this->dd->createElement('param');
                    $weight->setAttribute('name', 'weight');
                    $weight->appendChild($this->dd->createTextNode($val["fields"][ $this->settings["offers"]["weight"] ]));
                    $e->appendChild($weight);
                }
            }

            if (isset($this->settings["offers"]["vendor"]) && !empty($this->settings["offers"]["vendor"])) {
                if (isset($val["fields"][ $this->settings["offers"]["vendor"] ])
                    && !empty($val["fields"][ $this->settings["offers"]["vendor"] ]))
                {
                    $e->appendChild($this->dd->createElement('vendor', $val["fields"][ $this->settings["offers"]["vendor"] ]));
                }
            }
        }
    }

    public function getUrl($image)
    {
        $sizes = $this->getConfig()->getImageSizes('system');

        $str = str_pad($image['product_id'], 4, '0', STR_PAD_LEFT);
        $url = 'wa-data/public/shop/products/'.substr($str, -2).'/'.substr($str, -4, 2);
        $url .= "/" .$image['product_id']. "/images/" .$image['id']. "/";
        $url .= $image['id']. "." .$sizes['default']. "." . $image['ext'];

        return $this->settings["siteurl"] . $url;
    }
}
