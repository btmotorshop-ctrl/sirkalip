<?php
$t = cart_totals();
view('layout/top', ['title' => L('Sepetim', 'My Cart')]);
view('cart', compact('t'));
view('layout/bottom');
