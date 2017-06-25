<?php

class Popov_AdmitadExport_Model_Handler_Admitad extends Mirasvit_CatalogExport_Model_Handler_Yml
{
    //protected $_xml;
	//protected $_attr = array();
	//protected $_category_name = array();

    public function getName()
    {
        return Mage::helper('catalogexport')->__('Extended Yandex.Market (YML)');
    }

    public function getIdentifier()
    {
        return __CLASS__;
    }

    /*protected function _execute()
    {
        $content = $this->generateXml();
		$content2 = $this->generateStockXml();
		$this->_saveFileStock($content2);		
        return $this->_saveFile($content);		
    }

    protected function getCurrenciesElement()
    {
        $element = $this->_xml->createElement('currencies');

        $collection = $this->getCurrencyCollection();
        foreach ($collection as $currency) {
            $currencyElement = $this->_xml->createElement('currency');
            $currencyElement->setAttribute('id', $currency->getCode());
            $currencyElement->setAttribute('rate', $currency->getCurrencyRate());
            $element->appendChild($currencyElement);
        }

        return $element;
    }

    protected function getCategoriesElement()
    {
        $element = $this->_xml->createElement('categories');
        $collection = $this->getCategoryCollection();
        foreach ($collection as $category) {
            $el = $this->_xml->createElement('category', $this->_esc($category->getName()));
            $el->setAttribute('id', $category->getId());
            if ($category->getParentId()
                && $category->getParentId() != Mage::app()->getDefaultStoreView()->getRootCategoryId()) {
                $el->setAttribute('parentId', $category->getParentId());
            }
            $element->appendChild($el);
        }

        return $element;
    }

    protected function getOffersElement()
    {
        $element    = $this->_xml->createElement('offers');
        $collection = $this->getProductCollection();
		
        foreach ($collection as $product) { try{
			$product = $this->changeProductName($product);
            if (count($product->getCategoryIds()) > 0) {
                if($product->getTypeId() == 'configurable'){
					$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
					$col = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
					foreach($col as $simple_product){
						$item = $this->getOfferElement($product, $simple_product);
						if ($item) {
							$element->appendChild($item);
						}
					} 				
				} else {
					$item = $this->getOfferElement($product);
					if ($item) {
						$element->appendChild($item);
					}
				}
                
            } } catch(Exception $e) {Mage::log($e->getMessage(), null, 'YML-error.log');}
        }
		
        return $element;
    }
	
	protected function getOffersStockElement()
    {
        $element    = $this->_xml->createElement('offers');
        $collection = $this->getProductCollection();
		
        foreach ($collection as $product) {
		
            if (count($product->getCategoryIds()) > 0) {
                if($product->getTypeId() == 'configurable'){
					$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
					$col = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
					foreach($col as $simple_product){
						$item = $this->getOfferStockElement($product, $simple_product);
						if ($item) {
							$element->appendChild($item);
						}
					} 					
				} else {
					$item = $this->getOfferStockElement($product);
					if ($item) {
						$element->appendChild($item);
					}
				}
                
            }
        }

        return $element;
    }
	
	protected function getOfferStockElement($product, $configurable = false)
    {
        $element = $this->_xml->createElement('offer');
        if ($configurable) {
            $element->setAttribute('id', $configurable->getSku());
            $element->setAttribute('group_id', $product->getSku());
            if ($configurable->getStatus() == 1) {
                $element->setAttribute('stock', 1);
            } else {
                $element->setAttribute('stock', 0);
            }
        } else {
            $element->setAttribute('id', $product->getSku());
            $element->setAttribute('stock', 1);
        }
        $base = Mage::app()->getStore()->getBaseCurrency()->getCurrencyCode();
        $default = Mage::app()->getStore()->getDefaultCurrency()->getCurrencyCode();
        $price = Mage::helper('directory')->currencyConvert($product->getPrice(), $base, $default);
        $element->setAttribute('priceValue', $price);
        $element->setAttribute('priceCurrency', $default);
		
        return $element;
    }

    public function trim_value(&$value)
    {
        $value = trim($value);
    }*/
			
    protected function getOfferElement($product, $configurable = false)
    {
        $element = $this->_xml->createElement('offer');
		if($configurable){
			$element->setAttribute('id', $configurable->getSku());
			$element->setAttribute('group_id', $product->getSku());
		} else {
			$element->setAttribute('id', $product->getSku());			
        }
		$element->setAttribute('available', $product->getStockStatus() > 0 ? 'true' : 'false');

        $ok = true;
		
        $ok = $this->appendOfferUrlElement($element, $product) ? $ok : false;
        $ok = $this->appendOfferPriceElement($element, $product) ? $ok : false;
        $ok = $this->appendOfferCurrencyElement($element, $product) ? $ok : false;
        $ok = $this->appendOfferCategoryElement($element, $product) ? $ok : false;
        $ok = $this->appendOfferPictureElement($element, $product) ? $ok : false;
        $ok = $this->appendOfferNameElement($element, $product) ? $ok : false;
        $ok = $this->appendOfferVendorElement($element, $product) ? $ok : false;
        $ok = $this->appendOfferDescriptionElement($element, $product) ? $ok : false;
		//$ok = $this->appendOfferCountryElement($element, $product) ? $ok : false;

        $stringAttributes = Mage::getStoreConfig('catalogexport/settings/attributes');

        $prepareNeedAttribute = explode(',', $stringAttributes);

        foreach($prepareNeedAttribute as $attr){
            if ($attr = trim($attr)) {
                $this->getParamElement($element, $attr, $product, $configurable);
            }
		}
        if ($ok) {
            return $element;
        }
    }
	
    public function changeProductName($_product)
    {
        $names['category'] = $this->getLastCategoryName($_product);
        $names['manufacturer'] = $this->getProductBrand($_product);
        $names['product'] = $_product->getName();
        $newName = implode(' ', array_filter($names));
        $_product->setData('name', $newName, false);
		
		return $_product;
    }
	
	public function getProductBrand($_product)
    {
        // @TODO Get "brand" attribute name from configuration
        if ($_product->getData('manufacturer')) {
            return $_product->getAttributeText('manufacturer');
        }
    }
	
	public function getLastCategoryName($_product)
    {
        if ($cat_ids = array_reverse($_product->getCategoryIds())) {
            $index = 1;
            foreach($cat_ids as $cat_id){
                $parent_cat = Mage::getModel('catalog/category')->load($cat_id);
                $all_child_categories = Mage::getModel('catalog/category')->getResource()->getAllChildren($parent_cat);
                if(count($all_child_categories) == 1 || $index == count($cat_ids)){
                    if(!isset($this->_category_name[$cat_id])){
                        $this->_category_name[$cat_id] = $parent_cat->getName();
                    }

                    if(!Mage::registry('current_category')){
                        $_product->setCurrentCategory($parent_cat);
                        //$this->setCurrentCategory($parent_cat);
                        //Mage::register('current_category', $parent_cat);
                    }
                    return $this->_category_name[$cat_id];
                }
                $index++;
            }
        }

        return '';
    }
	
	protected function getParamElement($to, $attr, $product, $configurable)
    {
		if(($attr == 'razmer') && $configurable && $configurable->getData($attr)){
			$value = $this->_esc($configurable->getAttributeText($attr));
		} else if($attr == 'pol'){
			$value = 'для женщин';
		} else if($attr == 'stock'){
			if($configurable){
				if($configurable->getStatus() == 1){
					$value = 1;
				} else {
					$value = '0';
				}
			} else {
				$value = 1;
			}
		} elseif ($product->getData($attr)) {
			$value = $this->_esc($product->getAttributeText($attr));
		}

		if(!empty($value) && $attr != 'stock'){
			if(is_array($value)){
				$element = $this->_xml->createElement('param', implode('/', $value));
			} else {
				$element = $this->_xml->createElement('param', $value);
				if($attr == 'color'){
					$element2 = $this->_xml->createElement('param', 'Цвет для фильтра');
				}
			}

			$ok = true;
			if($label = $this->getAttributeLabel($attr)){
				if($attr == 'razmer'){
					$element->setAttribute('name', 'Размер');
				} else {
					$element->setAttribute('name', $label);
					if($attr == 'color'){
						$element2->setAttribute('name', $label);
					}
				}
			} else if($attr == 'pol'){
				$element->setAttribute('name', 'Пол');
			} else if($attr == 'stock'){
				$element->setAttribute('name', 'stock');
			} else {
				$ok = false;
			}

			if ($ok) {
				$to->appendChild($element);
			}
		}

		if($attr == 'stock'){
			$element = $this->_xml->createElement('param', $value);
			$element->setAttribute('name', 'stock');
			$to->appendChild($element);
		}
    }
	
	/*protected function getAttributeLabel($attr)
	{
		if(!isset($this->_attr[$attr])){
			$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $attr);
			$attribute = $attribute_details->getData();	
			if(!$attribute_details->getId()){
				return false;
			}
			$this->_attr[$attr] = $attribute;
		}
		
		return $this->_attr[$attr]['frontend_label'];
		
	}*/
	
	protected function appendOfferCountryElement($to, $product)
    {
		if ($product->getData('shoes_country')) {
			$element = $this->_xml->createElement('country_of_origin', $this->_esc($product->getAttributeText('shoes_country')));
			$to->appendChild($element);
		}

        return true;
    }

    /*protected function appendOfferUrlElement($to, $product)
    {
        $suffix = '';
        if ($this->getIsTraceGa()) {
            $source = preg_replace("/[^A-Za-z0-9]/", '', $this->getData('name'));
            $sku    = preg_replace("/[^A-Za-z0-9]/", '', $product->getSku());
            $name   = preg_replace("/[^A-Za-z0-9]/", '', $product->getName());

            $suffix = '?utm_source='.$source.'&utm_medium=cpc&utm_term='.$sku.'&utm_campaign='.$name;
        }
        if ($product->getVisibility() == 1) {
            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
            if (count($parentIds) > 0) {
                $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                if ($parent->getStatus() != 1) {
                    return false;
                }
                if ($this->getIsTraceGa()) {
                    $suffix .= '&sku='.$product->getSku();
                } else {
                    $suffix .= '?sku='.$product->getSku();
                }
                $get = $this->_esc($suffix);

                $element = $this->_xml->createElement('url', $parent->getProductUrl().$get);
                $to->appendChild($element);
            } else {
                return false;
            }
        } else {
            $element = $this->_xml->createElement('url', $product->getProductUrl().$this->_esc($suffix));
            $to->appendChild($element);
        }

        return true;
    }*/

    /*protected function appendOfferPriceElement($to, $product)
    {
        $base    = Mage::app()->getStore()->getBaseCurrency()->getCurrencyCode();
        $default = Mage::app()->getStore()->getDefaultCurrency()->getCurrencyCode();
        $price   = Mage::helper('directory')->currencyConvert($product->getPrice(), $base, $default);
        $element = $this->_xml->createElement('price', $price);
        $to->appendChild($element);

        return true;
    }

    protected function appendOfferCurrencyElement($to, $product)
    {
        $default = Mage::app()->getStore()->getDefaultCurrency()->getCurrencyCode();
        $element = $this->_xml->createElement('currencyId', $default);
        $to->appendChild($element);

        return true;
    }

    protected function appendOfferCategoryElement($to, $product)
    {
        $categories = $product->getCategoryIds();
        if (count($categories) > 0) {
            $element = $this->_xml->createElement('categoryId', $categories[0]);
            $to->appendChild($element);
        }

        return true;
    }

    protected function appendOfferPictureElement($to, $product)
    {
        $thumbnail = '';
		$image = '';
		$small_image = '';
		if ($thumbnail = $product->getThumbnail()) {
            $url     = Mage::helper('mstcore/image')->init($product, 'thumbnail', 'catalog/product')->resize(200, 200);
            $element = $this->_xml->createElement('picture', $url);

            $to->appendChild($element);
        }
		
		if (($image = $product->getImage()) && $image != $thumbnail) {
            $url     = Mage::helper('mstcore/image')->init($product, 'image', 'catalog/product')->resize(200, 200);
            $element = $this->_xml->createElement('picture', $url);

            $to->appendChild($element);
        }
		
		if (($small_image = $product->getSmallImage()) && ($small_image != $thumbnail) && ($small_image != $image)) {
            $url     = Mage::helper('mstcore/image')->init($product, 'small_image', 'catalog/product')->resize(200, 200);
            $element = $this->_xml->createElement('picture', $url);

            $to->appendChild($element);
        }
		
		$attributes = $product->getTypeInstance(true)->getSetAttributes($product);
		$media_gallery = $attributes['media_gallery'];
		$backend = $media_gallery->getBackend();
		$backend->afterLoad($product);
        foreach ($product->getMediaGalleryImages() as $image) {
            $url= Mage::helper('catalog/image')->init($product, 'image', $image->getFile())->resize(265);
            $element = $this->_xml->createElement('picture', $url);

            $to->appendChild($element);
        } 

        return true;
    }*/

    /*protected function appendOfferNameElement($to, $product)
    {
        $element = $this->_xml->createElement('name', $this->_esc($product->getData('name')));
        $to->appendChild($element);

        return true;
    }*/

    protected function appendOfferDescriptionElement($to, $product)
    {
		if ($product->getData('description')) {
			$element = $this->_xml->createElement('description', $this->_esc($product->getData('description')));
			$to->appendChild($element);
		}

        return true;
    }

    protected function appendOfferVendorElement($to, $product)
    {
        if ($product->getData('manufacturer')) {
            $element = $this->_xml->createElement('vendor', $this->_esc($product->getAttributeText('manufacturer')));
            $to->appendChild($element);
        }

        return true;
    }

    /*protected function generateStockXml()
    {
        $dom         = new DOMImplementation();
        $doctype     = $dom->createDocumentType('yml_catalog SYSTEM "shops.dtd"');
        $this->_xml  = $dom->createDocument('', '', $doctype);

        $this->_xml->preserveWhiteSpace = false;
        $this->_xml->formatOutput = true;
        $this->_xml->encoding = 'utf-8';

        $catalog = $this->_xml->createElement('yml_catalog');
        $shop    = $this->_xml->createElement('shop');
        $name    = $this->_xml->createElement('name', $this->_esc($this->getStoreName()));
        $company = $this->_xml->createElement('company', $this->_esc($this->getCompanyName()));
        $url     = $this->_xml->createElement('url', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));


        $shop->appendChild($name);
        $shop->appendChild($company);
        $shop->appendChild($url);
        //$shop->appendChild($this->getCurrenciesElement());
        //$shop->appendChild($this->getCategoriesElement());
        $shop->appendChild($this->getOffersStockElement());

        $catalog->setAttribute('date', date('Y-m-d h:i'));
        $catalog->appendChild($shop);

        $this->_xml->appendChild($catalog);

        return $this->_xml->saveXML();
    }

    protected function generateXml()
    {
        $dom         = new DOMImplementation();
        $doctype     = $dom->createDocumentType('yml_catalog SYSTEM "shops.dtd"');
        $this->_xml  = $dom->createDocument('', '', $doctype);

        $this->_xml->preserveWhiteSpace = false;
        $this->_xml->formatOutput = true;
        $this->_xml->encoding = 'utf-8';

        $catalog = $this->_xml->createElement('yml_catalog');
        $shop    = $this->_xml->createElement('shop');
        $name    = $this->_xml->createElement('name', $this->_esc($this->getStoreName()));
        $company = $this->_xml->createElement('company', $this->_esc($this->getCompanyName()));
        $url     = $this->_xml->createElement('url', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));


        $shop->appendChild($name);
        $shop->appendChild($company);
        $shop->appendChild($url);
        $shop->appendChild($this->getCurrenciesElement());
        $shop->appendChild($this->getCategoriesElement());
        $shop->appendChild($this->getOffersElement());

        $catalog->setAttribute('date', date('Y-m-d h:i'));
        $catalog->appendChild($shop);

        $this->_xml->appendChild($catalog);

        return $this->_xml->saveXML();
    }*/
}