<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', static function () {
    return redirect()->to('/api-console.html');
});

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

/*
 * 404 Fallback - API以外の不明なルートはapi-console.htmlにリダイレクト
 */
$routes->set404Override(static function () {
    $uri = service('uri')->getPath();
    
    // /api で始まるパスは404をそのまま返す
    if (str_starts_with($uri, 'api/') || $uri === 'api') {
        return service('response')
            ->setStatusCode(404)
            ->setJSON([
                'status'  => 404,
                'error'   => 404,
                'messages' => ['error' => 'Not Found'],
            ]);
    }
    
    // それ以外はapi-console.htmlにリダイレクト
    return redirect()->to('/api-console.html');
});
