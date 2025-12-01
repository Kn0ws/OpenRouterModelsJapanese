<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/*
 * OpenRouter API Routes
 */
$routes->group('api', ['namespace' => 'App\Controllers\API'], static function ($routes) {
    $routes->group('openrouter', static function ($routes) {
        // モデル一覧
        $routes->get('models', 'OpenRouterController::index');
        
        // 検索
        $routes->get('models/search', 'OpenRouterController::search');
        
        // プロバイダでフィルタ
        $routes->get('models/provider/(:segment)', 'OpenRouterController::byProvider/$1');
        
        // カテゴリでフィルタ
        $routes->get('models/category/(.+)', 'OpenRouterController::byCategory/$1');
        
        // 特定のモデル取得（スラッシュを含むモデルIDに対応）
        $routes->get('models/(.+)', 'OpenRouterController::show/$1');
        
        // プロバイダ一覧
        $routes->get('providers', 'OpenRouterController::providers');
        
        // カテゴリ一覧
        $routes->get('categories', 'OpenRouterController::categories');
    });
});
