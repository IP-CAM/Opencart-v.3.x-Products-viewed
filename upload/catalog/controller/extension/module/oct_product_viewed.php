<?php

class ControllerExtensionModuleOctProductViewed extends Controller {
    public function index($setting) {
        static $module = 0;

        $this->load->language('extension/module/oct_product_viewed');

        $this->load->model('catalog/product');

        $this->load->model('tool/image');

        $data['products'] = array();

        if (!$setting['limit']) {
            $setting['limit'] = 4;
        }

        $data['position'] = $setting['position'];

        if (isset($this->session->data['oct_product_viewed']) && !empty($this->session->data['oct_product_viewed']) && count($this->session->data['oct_product_viewed']) > 2) {

            $products = array_slice(array_unique($this->session->data['oct_product_viewed']), 0, $setting['limit']);

            krsort($products);

            foreach ($products as $product_id) {
                $product_info = $this->model_catalog_product->getProduct($product_id);

                if ($product_info) {

                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                    }

                    if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }

                    if ((float) $product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }

                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float) $product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }

                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }

                    $data['products'][] = array(
                        'product_id' => $product_info['product_id'],
                        'thumb' => $image,
                        'name' => $product_info['name'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
                        'quantity' => $product_info['quantity'],
                        'price' => $price,
                        'special' => $special,
                        'saving' => round((($product_info['price'] - $product_info['special']) / ($product_info['price'] + 0.01)) * 100, 0),
                        'tax' => $tax,
                        'rating' => $rating,
                        'href' => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }
        }

        $data['module'] = $module++;

        if ($data['products']) {
            return $this->load->view('octemplates/module/oct_product_viewed', $data);
        }
    }
}