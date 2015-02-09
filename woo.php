<?php
defined('ABSPATH') or die('You have been weighed, you have been measured, and you have been found wanting.');

final class meteoraWoo {
	private static $jsTmpl = '<script>var _meq = _meq || []; _meq.push(\'%s\', %s);</script>';

	private static function getCats() {
		$terms = get_the_terms(get_the_ID(), 'product_cat');
		$cats = array();
		foreach ($terms as $term) {
			$cats[] = $term->name;
		}
		return join('/', $cats);
	}

	private static function getBrand() {
		$brand = wp_get_post_terms(get_the_ID(), 'product_brand', true);
		return is_string($brand) ? $brand : '';
	}

	private static function getImageUrl() {
		global $product;
		$img = $product->get_image();
		preg_match('/src="(.*?)"/', $product->get_image(), $matches);
		return count($matches) == 2 ? $matches[1] : '';
	}

	public static function getProductJS($asStr = false) {
		global $product;
		$sku = $product->get_sku();
		$data = array(
			'product' => array(
				'id' => $product->post->ID,
				'sku' => empty($sku) ? $product->id : $sku,
				'name' => $product->post->post_title,
				'category' => self::getCats(),
				'price' => $product->get_price(),
				'brand' => self::getBrand(),
				'image' => self::getImageUrl(),
			),
		);
		printf(self::$jsTmpl, 'meta', json_encode($data));
	}

	public static function getConvJS($order_id) {
		$order = new WC_Order($order_id);
		$prods = $order->get_items();
		$items = array();
		foreach($prods as $prod) {
			$items[] = array(
				'sku' => isset($prod['item_meta']['_sku']) ? $prod['item_meta']['_sku'][0] : $prod['product_id'],
				'price' => $prod['line_total'],
			);
		}
		$data = array(
			'order' => array(
				'id' => $order->id,
				'amount' => $order->get_total(),
				'currency' => $order->get_order_currency(),
				'items' => $items,
			),
		);
		printf(self::$jsTmpl, 'conversion', json_encode($data));
	}
}
