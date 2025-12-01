<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\OpenRouterModelModel;
use CodeIgniter\API\ResponseTrait;

class OpenRouterController extends BaseController
{
    use ResponseTrait;

    protected OpenRouterModelModel $modelModel;
    protected ?float $usdJpyRate = null;

    public function __construct()
    {
        $this->modelModel = new OpenRouterModelModel();
    }

    /**
     * USD/JPY為替レートを取得
     */
    protected function getUsdJpyRate(): ?float
    {
        if ($this->usdJpyRate !== null) {
            return $this->usdJpyRate;
        }

        try {
            $client = service('curlrequest');
            $response = $client->request('GET', 'https://api.excelapi.org/currency/rate?pair=usd-jpy', [
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() === 200) {
                $rate = (float) trim($response->getBody());
                if ($rate > 0) {
                    $this->usdJpyRate = $rate;
                    return $rate;
                }
            }
        } catch (\Exception $e) {
            log_message('warning', '為替レート取得に失敗: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * モデルデータに日本円価格を追加
     */
    protected function addJpyPricing(array $model): array
    {
        $rate = $this->getUsdJpyRate();

        if ($rate === null) {
            return $model;
        }

        $pricingFields = [
            'pricing_prompt',
            'pricing_completion',
            'pricing_request',
            'pricing_image',
            'pricing_web_search',
            'pricing_internal_reasoning',
        ];

        foreach ($pricingFields as $field) {
            $jaField = $field . '_ja';
            if (isset($model[$field]) && $model[$field] !== null && $model[$field] !== '') {
                $usdValue = (float) $model[$field];
                $jpyValue = $usdValue * $rate;
                // 指数表記を避けて文字列として返す（小数点以下12桁まで、末尾ゼロ除去）
                $model[$jaField] = rtrim(rtrim(number_format($jpyValue, 12, '.', ''), '0'), '.');
            } else {
                $model[$jaField] = null;
            }
        }

        return $model;
    }

    /**
     * モデル配列を整形（ID除外 + 日本円価格追加）
     */
    protected function formatModels(array $models): array
    {
        return array_map(function ($model) {
            unset($model['id']);
            return $this->addJpyPricing($model);
        }, $models);
    }

    /**
     * OpenRouterモデル一覧を取得
     * GET /api/openrouter/models
     */
    public function index()
    {
        $models = $this->modelModel->orderBy('name', 'ASC')->findAll();
        $formattedModels = $this->formatModels($models);

        return $this->respond([
            'success'      => true,
            'count'        => count($formattedModels),
            'usd_jpy_rate' => $this->usdJpyRate,
            'data'         => $formattedModels,
        ]);
    }

    /**
     * 特定のモデルを取得
     * GET /api/openrouter/models/{modelId}
     */
    public function show(?string $modelId = null)
    {
        // URIセグメントから直接取得（スラッシュを含むパス対応）
        $uri = service('uri');
        $segments = $uri->getSegments();
        
        // /api/openrouter/models/{modelId} なので、4番目以降を結合
        if (count($segments) >= 4) {
            $modelId = implode('/', array_slice($segments, 3));
        }
        
        if (empty($modelId)) {
            return $this->failNotFound('Model ID is required');
        }

        // URLエンコードされたモデルIDをデコード
        $modelId = urldecode($modelId);

        $model = $this->modelModel->findByModelId($modelId);

        if (!$model) {
            return $this->failNotFound('Model not found');
        }

        unset($model['id']);
        $model = $this->addJpyPricing($model);

        return $this->respond([
            'success'      => true,
            'usd_jpy_rate' => $this->usdJpyRate,
            'data'         => $model,
        ]);
    }

    /**
     * プロバイダ一覧を取得
     * GET /api/openrouter/providers
     */
    public function providers()
    {
        $providers = $this->modelModel
            ->select('provider, COUNT(*) as count')
            ->groupBy('provider')
            ->orderBy('count', 'DESC')
            ->findAll();

        return $this->respond([
            'success' => true,
            'count'   => count($providers),
            'data'    => $providers,
        ]);
    }

    /**
     * カテゴリ一覧を取得
     * GET /api/openrouter/categories
     */
    public function categories()
    {
        $categories = $this->modelModel
            ->select('category_ja, COUNT(*) as count')
            ->groupBy('category_ja')
            ->orderBy('count', 'DESC')
            ->findAll();

        return $this->respond([
            'success' => true,
            'count'   => count($categories),
            'data'    => $categories,
        ]);
    }

    /**
     * プロバイダでフィルタ
     * GET /api/openrouter/models/provider/{provider}
     */
    public function byProvider(string $provider)
    {
        $models = $this->modelModel->findByProvider($provider);
        $formattedModels = $this->formatModels($models);

        return $this->respond([
            'success'      => true,
            'count'        => count($formattedModels),
            'usd_jpy_rate' => $this->usdJpyRate,
            'data'         => $formattedModels,
        ]);
    }

    /**
     * カテゴリでフィルタ
     * GET /api/openrouter/models/category/{category}
     */
    public function byCategory(string $category)
    {
        // URLエンコードされたカテゴリをデコード
        $category = urldecode($category);

        $models = $this->modelModel->findByCategory($category);
        $formattedModels = $this->formatModels($models);

        return $this->respond([
            'success'      => true,
            'count'        => count($formattedModels),
            'usd_jpy_rate' => $this->usdJpyRate,
            'data'         => $formattedModels,
        ]);
    }

    /**
     * モデル検索
     * GET /api/openrouter/models/search?q={query}
     */
    public function search()
    {
        $query = $this->request->getGet('q');

        if (empty($query)) {
            return $this->failValidationErrors('Search query is required');
        }

        $models = $this->modelModel
            ->like('name', $query)
            ->orLike('model_id', $query)
            ->orLike('description', $query)
            ->orLike('description_ja', $query)
            ->orLike('provider', $query)
            ->orLike('category_ja', $query)
            ->findAll();

        $formattedModels = $this->formatModels($models);

        return $this->respond([
            'success'      => true,
            'count'        => count($formattedModels),
            'usd_jpy_rate' => $this->usdJpyRate,
            'query'        => $query,
            'data'         => $formattedModels,
        ]);
    }
}
