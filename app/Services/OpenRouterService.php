<?php

namespace App\Services;

use CodeIgniter\HTTP\CURLRequest;

class OpenRouterService
{
    protected string $apiKey;
    protected string $modelsUrl = 'https://openrouter.ai/api/v1/models';
    protected string $chatUrl = 'https://openrouter.ai/api/v1/chat/completions';
    protected string $descriptionModelId;
    protected CURLRequest $client;

    /**
     * モダリティ日本語マップ
     */
    protected array $modalityMapJa = [
        'text'  => 'テキスト',
        'image' => '画像',
        'audio' => '音声',
        'video' => '動画',
        'file'  => 'ファイル',
    ];

    /**
     * サポートパラメータ日本語マップ
     */
    protected array $parameterMapJa = [
        'frequency_penalty'   => '頻度ペナルティ',
        'include_reasoning'   => '推論プロセス含有',
        'logit_bias'          => 'ロジットバイアス',
        'max_tokens'          => '最大トークン数',
        'min_p'               => '最小確率（min_p）',
        'presence_penalty'    => '存在ペナルティ',
        'reasoning'           => '推論モード',
        'repetition_penalty'  => '繰り返しペナルティ',
        'response_format'     => 'レスポンス形式',
        'seed'                => 'シード値',
        'stop'                => '停止シーケンス',
        'structured_outputs'  => '構造化出力',
        'temperature'         => '温度（ランダム性）',
        'tool_choice'         => 'ツール選択',
        'tools'               => 'ツール定義',
        'top_k'               => 'Top-K サンプリング',
        'top_p'               => 'Top-P（核サンプリング）',
        'stream'              => 'ストリーミング',
        'n'                   => '生成数',
        'logprobs'            => 'ログ確率',
        'top_logprobs'        => 'Top ログ確率数',
        'echo'                => 'エコー',
        'best_of'             => 'ベストオブ',
        'suffix'              => 'サフィックス',
        'user'                => 'ユーザー識別子',
    ];

    public function __construct()
    {
        $this->apiKey = getenv('OPENROUTER_API_KEY') ?: '';
        $this->descriptionModelId = getenv('DESCRIPTION_AI_MODEL') ?: 'x-ai/grok-4.1-fast:free';
        $this->client = service('curlrequest');
    }

    /**
     * OpenRouterからモデル一覧を取得
     */
    public function fetchModels(): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenRouter API Key is not configured');
        }

        $response = $this->client->request('GET', $this->modelsUrl, [
            'headers' => $this->getBaseHeaders(),
            'timeout' => 30,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to fetch models from OpenRouter');
        }

        $data = json_decode($response->getBody(), true);
        return $data['data'] ?? [];
    }

    /**
     * OpenRouter Chat APIを呼び出し
     */
    public function callChat(array $messages, int $maxTokens = 256): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenRouter API Key is not configured');
        }

        $headers = array_merge($this->getBaseHeaders(), [
            'Content-Type' => 'application/json',
        ]);

        $payload = [
            'model'      => $this->descriptionModelId,
            'messages'   => $messages,
            'max_tokens' => $maxTokens,
        ];

        $response = $this->client->request('POST', $this->chatUrl, [
            'headers' => $headers,
            'json'    => $payload,
            'timeout' => 120,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to call OpenRouter Chat API');
        }

        $data = json_decode($response->getBody(), true);
        $choices = $data['choices'] ?? [];

        if (empty($choices)) {
            throw new \RuntimeException('OpenRouter chat API: choices is empty');
        }

        $message = $choices[0]['message'] ?? [];
        $content = $message['content'] ?? '';

        if (is_array($content)) {
            $textParts = [];
            foreach ($content as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'text') {
                    $textParts[] = $part['text'] ?? '';
                }
            }
            return trim(implode('', $textParts));
        }

        return trim((string)$content);
    }

    /**
     * モデルのカテゴリを判定（日本語）
     */
    public function detectCategory(array $model): string
    {
        $name = strtolower($model['name'] ?? '');
        $desc = strtolower($model['description'] ?? '');
        $arch = $model['architecture'] ?? [];
        $modality = strtolower($arch['modality'] ?? '');
        $params = array_map('strtolower', $model['supported_parameters'] ?? []);

        if (preg_match('/safeguard|safety|moderation/', $name)) {
            return '安全判定・コンテンツフィルタリング';
        }
        if (preg_match('/code|codex|coder/', $name)) {
            return 'コード生成・ソフトウェア開発支援';
        }
        if (strpos($modality, 'image') !== false || preg_match('/image|vision|vl/', $name)) {
            return '画像生成・マルチモーダル';
        }
        if (strpos($modality, 'audio') !== false || strpos($name, 'speech') !== false) {
            return '音声・音声認識／合成';
        }
        if (preg_match('/research|sonar/', $name) || strpos($desc, 'search') !== false) {
            return 'リサーチ・検索エージェント';
        }
        if (preg_match('/think/', $name) || in_array('reasoning', $params) || in_array('include_reasoning', $params)) {
            return '推論強化モデル';
        }
        if (preg_match('/mini|nano|haiku|fast|flash|small|micro/', $name)) {
            return '高速・軽量チャット／補助モデル';
        }
        if (preg_match('/pro|opus|premier|ultra/', $name)) {
            return '高精度汎用フラッグシップモデル';
        }

        return '汎用テキストモデル';
    }

    /**
     * モダリティを日本語に変換
     */
    public function modalityToJa(string $modality): string
    {
        $mod = strtolower($modality);

        if ($mod === 'text->text') {
            return 'テキスト入力 → テキスト出力';
        }
        if ($mod === 'text+image->text') {
            return 'テキスト＋画像入力 → テキスト出力';
        }
        if ($mod === 'text+image->text+image') {
            return 'テキスト＋画像入力 → テキスト＋画像出力';
        }
        if (strpos($mod, 'image->image') !== false) {
            return '画像入力 → 画像出力';
        }
        if (strpos($mod, 'audio') !== false) {
            return '音声を含むマルチモーダル';
        }
        if (strpos($mod, 'video') !== false) {
            return '動画を含むマルチモーダル';
        }
        if (empty($mod)) {
            return '不明';
        }

        return $mod;
    }

    /**
     * モダリティリストを日本語に変換
     */
    public function listToJaModalities(?array $values): string
    {
        if (empty($values)) {
            return '';
        }

        $jaList = array_map(function ($v) {
            $lower = strtolower($v ?? '');
            return $this->modalityMapJa[$lower] ?? $v;
        }, $values);

        return implode(', ', $jaList);
    }

    /**
     * サポートパラメータを日本語に変換
     */
    public function parametersToJa(?array $parameters): string
    {
        if (empty($parameters)) {
            return '';
        }

        $jaList = array_map(function ($param) {
            $lower = strtolower($param);
            return $this->parameterMapJa[$lower] ?? $param;
        }, $parameters);

        return implode(', ', $jaList);
    }

    /**
     * モデルの日本語説明を生成
     */
    public function buildDescriptionJa(array $model): string
    {
        $name = $model['name'] ?? $model['id'] ?? 'このモデル';
        $originalDesc = trim($model['description'] ?? '');
        $category = $this->detectCategory($model);
        $arch = $model['architecture'] ?? [];
        $modalityJa = $this->modalityToJa($arch['modality'] ?? '');
        $ctx = $model['context_length'] ?? null;

        $systemPrompt = 'あなたはAIモデル一覧表を作るための日本語ライター。' .
            'エンジニア向けに、事実のみを、です・ます調を使わずに説明を書く。' .
            '用途と特徴が詳細に分かるよう、長くなっても良いので正確に書く。';

        $infoLines = [
            "Model name: {$name}",
            "Category (ja hint): {$category}",
            "Modality (ja hint): {$modalityJa}",
        ];

        if ($ctx) {
            $infoLines[] = "Context length: {$ctx} tokens";
        }

        if (!empty($originalDesc)) {
            $infoLines[] = 'Original description (English):';
            $infoLines[] = $originalDesc;
        } else {
            $infoLines[] = 'Original description (English): (none)';
        }

        $userPrompt = "次のモデル情報を読んで、このモデルがどのような用途・特徴を持つかを、" .
            "日本語の説明分として出力して。\n\n" .
            implode("\n", $infoLines) .
            "\n\n条件:\n" .
            "- 口調は説明文。です・ます調は使わない。\n" .
            "- 主観的な評価や推測は書かない。\n" .
            "- モデルの用途や特徴が明確にわかるようにする。\n";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        try {
            $ja = $this->callChat($messages);
            $ja = trim($ja);

            if (empty($ja)) {
                return $this->buildFallbackDescription($name, $category, $modalityJa);
            }

            return $ja;
        } catch (\Exception $e) {
            log_message('warning', "description_ja生成に失敗: {$name}: " . $e->getMessage());
            return $this->buildFallbackDescription($name, $category, $modalityJa);
        }
    }

    /**
     * フォールバック説明文を生成
     */
    protected function buildFallbackDescription(string $name, string $category, string $modalityJa): string
    {
        return "{$name} は「{$category}」に分類されるモデル。モダリティは {$modalityJa}。";
    }

    /**
     * モデルデータを整形（オリジナル情報を保持 + 日本語列を追加）
     */
    public function formatModelData(array $model, ?string $existingDescriptionJa = null): array
    {
        $modelId = $model['id'] ?? '';
        $arch = $model['architecture'] ?? [];
        $pricing = $model['pricing'] ?? [];
        $topProvider = $model['top_provider'] ?? [];
        $inputMods = $arch['input_modalities'] ?? [];
        $outputMods = $arch['output_modalities'] ?? [];
        $supportedParams = $model['supported_parameters'] ?? [];
        $defaultParams = $model['default_parameters'] ?? [];

        // 既存の説明があれば再利用、なければ新規生成
        if ($existingDescriptionJa !== null) {
            $descriptionJa = $existingDescriptionJa;
        } else {
            $descriptionJa = $this->buildDescriptionJa($model);
        }

        // 改行を除去
        $descriptionJa = str_replace(["\r\n", "\n", "\r"], ' ', $descriptionJa);
        $descriptionJa = trim($descriptionJa);

        return [
            // === 基本情報（オリジナル） ===
            'model_id'        => $modelId,
            'canonical_slug'  => $model['canonical_slug'] ?? null,
            'hugging_face_id' => $model['hugging_face_id'] ?? null,
            'name'            => $model['name'] ?? '',
            'provider'        => strpos($modelId, '/') !== false ? explode('/', $modelId, 2)[0] : '',
            'created'         => $model['created'] ?? null,
            'description'     => $model['description'] ?? null,
            'context_length'  => $model['context_length'] ?? null,

            // === アーキテクチャ（オリジナル） ===
            'arch_modality'          => $arch['modality'] ?? null,
            'arch_input_modalities'  => implode(', ', $inputMods),
            'arch_output_modalities' => implode(', ', $outputMods),
            'arch_tokenizer'         => $arch['tokenizer'] ?? null,
            'arch_instruct_type'     => $arch['instruct_type'] ?? null,

            // === 価格（オリジナル） ===
            'pricing_prompt'             => $pricing['prompt'] ?? null,
            'pricing_completion'         => $pricing['completion'] ?? null,
            'pricing_request'            => $pricing['request'] ?? null,
            'pricing_image'              => $pricing['image'] ?? null,
            'pricing_web_search'         => $pricing['web_search'] ?? null,
            'pricing_internal_reasoning' => $pricing['internal_reasoning'] ?? null,

            // === トッププロバイダ（オリジナル） ===
            'top_provider_context_length'        => $topProvider['context_length'] ?? null,
            'top_provider_max_completion_tokens' => $topProvider['max_completion_tokens'] ?? null,
            'top_provider_is_moderated'          => !empty($topProvider['is_moderated']) ? 1 : 0,

            // === サポートパラメータ・デフォルトパラメータ（JSON） ===
            'supported_parameters' => !empty($supportedParams) ? json_encode($supportedParams) : null,
            'default_parameters'   => !empty($defaultParams) ? json_encode($defaultParams) : null,

            // === 日本語解説・分類（追加） ===
            'category_ja'              => $this->detectCategory($model),
            'modality_ja'              => $this->modalityToJa($arch['modality'] ?? ''),
            'input_modalities_ja'      => $this->listToJaModalities($inputMods),
            'output_modalities_ja'     => $this->listToJaModalities($outputMods),
            'description_ja'           => $descriptionJa,
            'supported_parameters_ja'  => $this->parametersToJa($supportedParams),
        ];
    }

    /**
     * ベースヘッダーを取得
     */
    protected function getBaseHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Referer'  => getenv('app.baseURL') ?: 'https://example.com',
            'X-Title'       => 'ai-model-search-api',
        ];
    }
}
