<?php
$categories = get_categories();
$brands     = get_brands();
$products   = get_featured_products();

view('layout/top', ['title' => null]);
view('home/hero');
view('home/trust');
view('home/finder', compact('brands', 'categories'));
view('home/categories', compact('categories'));
view('home/bestsellers', compact('products', 'categories'));
view('home/promo');
view('home/brands', compact('brands'));
view('layout/bottom');
