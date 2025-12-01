<?php

namespace App\Models;

use CodeIgniter\Model;

class OpenRouterModelModel extends Model
{
    protected $table            = 'openrouter_models';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        // 基本情報（オリジナル）
        'model_id',
        'canonical_slug',
        'hugging_face_id',
        'name',
        'provider',
        'created',
        'description',
        'context_length',
        // アーキテクチャ（オリジナル）
        'arch_modality',
        'arch_input_modalities',
        'arch_output_modalities',
        'arch_tokenizer',
        'arch_instruct_type',
        // 価格（オリジナル）
        'pricing_prompt',
        'pricing_completion',
        'pricing_request',
        'pricing_image',
        'pricing_web_search',
        'pricing_internal_reasoning',
        // トッププロバイダ（オリジナル）
        'top_provider_context_length',
        'top_provider_max_completion_tokens',
        'top_provider_is_moderated',
        // サポートパラメータ・デフォルトパラメータ（JSON）
        'supported_parameters',
        'default_parameters',
        // 日本語解説・分類（追加）
        'category_ja',
        'modality_ja',
        'input_modalities_ja',
        'output_modalities_ja',
        'description_ja',
        'supported_parameters_ja',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * モデルIDで検索
     */
    public function findByModelId(string $modelId): ?array
    {
        return $this->where('model_id', $modelId)->first();
    }

    /**
     * 全モデルIDを取得
     */
    public function getAllModelIds(): array
    {
        $result = $this->select('model_id')->findAll();
        return array_column($result, 'model_id');
    }

    /**
     * モデルIDで削除
     */
    public function deleteByModelId(string $modelId): bool
    {
        return $this->where('model_id', $modelId)->delete();
    }

    /**
     * プロバイダでフィルタ
     */
    public function findByProvider(string $provider): array
    {
        return $this->where('provider', $provider)->findAll();
    }

    /**
     * カテゴリでフィルタ
     */
    public function findByCategory(string $categoryJa): array
    {
        return $this->where('category_ja', $categoryJa)->findAll();
    }

    /**
     * モダリティでフィルタ
     */
    public function findByModality(string $modality): array
    {
        return $this->where('arch_modality', $modality)->findAll();
    }
}
