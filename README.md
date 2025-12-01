# AI Model Search API

OpenRouterで利用可能なAIモデルの情報を日本語解説付きで提供するAPIです。

## 環境構築

### 必要要件

- PHP 8.1+
- CodeIgniter 4.6+
- MariaDB / MySQL

### セットアップ

1. `.env` ファイルを作成

```bash
cp env .env
```

2. `.env` を編集

```env
CI_ENVIRONMENT = development

# データベース設定
database.default.hostname = localhost
database.default.database = ai_model_search
database.default.username = your_username
database.default.password = your_password
database.default.DBDriver = MySQLi

# OpenRouter設定
OPENROUTER_API_KEY = sk-or-v1-xxxxxxxx
DESCRIPTION_AI_MODEL = x-ai/grok-4.1-fast:free
```

3. マイグレーション実行

```bash
php spark migrate
```

4. モデルデータ同期

```bash
php spark openrouter:sync
```

---

## API エンドポイント

### ベースURL

```
http://localhost/api/openrouter
```

---

### モデル一覧取得

全てのAIモデルを取得します。

```
GET /api/openrouter/models
```

#### レスポンス例

```json
{
  "success": true,
  "count": 331,
  "usd_jpy_rate": 149.85,
  "data": [
    {
      "model_id": "openai/gpt-4o",
      "canonical_slug": "openai/gpt-4o-20240513",
      "hugging_face_id": null,
      "name": "OpenAI: GPT-4o",
      "provider": "openai",
      "created": 1715644800,
      "description": "GPT-4o is OpenAI's flagship model...",
      "context_length": 128000,
      "arch_modality": "text+image->text",
      "arch_input_modalities": "text, image",
      "arch_output_modalities": "text",
      "arch_tokenizer": "o200k_base",
      "arch_instruct_type": null,
      "pricing_prompt": "0.0000025",
      "pricing_completion": "0.00001",
      "pricing_request": "0",
      "pricing_image": "0.003613",
      "pricing_web_search": "0",
      "pricing_internal_reasoning": "0",
      "top_provider_context_length": 128000,
      "top_provider_max_completion_tokens": 16384,
      "top_provider_is_moderated": 1,
      "supported_parameters": "[\"temperature\",\"top_p\",\"max_tokens\",...]",
      "default_parameters": "{\"temperature\":1,\"top_p\":1}",
      "category_ja": "画像生成・マルチモーダル",
      "modality_ja": "テキスト＋画像入力 → テキスト出力",
      "input_modalities_ja": "テキスト, 画像",
      "output_modalities_ja": "テキスト",
      "description_ja": "GPT-4oはOpenAIのフラッグシップモデルで、テキストと画像を入力としてテキストを出力するマルチモーダル対応。128Kトークンのコンテキスト長を持ち、高速推論と複雑なタスク処理に対応する。",
      "supported_parameters_ja": "温度（ランダム性）, Top-P（核サンプリング）, 最大トークン数, ...",
      "created_at": "2024-12-01 12:00:00",
      "updated_at": "2024-12-01 12:00:00",
      "pricing_prompt_ja": "0.000374625",
      "pricing_completion_ja": "0.001498500",
      "pricing_request_ja": "0",
      "pricing_image_ja": "0.541387",
      "pricing_web_search_ja": "0",
      "pricing_internal_reasoning_ja": "0"
    }
  ]
}
```

---

### 特定モデル取得

モデルIDを指定して1件取得します。

```
GET /api/openrouter/models/{modelId}
```

#### パラメータ

| 名前 | 型 | 説明 |
|------|-----|------|
| `modelId` | string | モデルID（例: `openai/gpt-4o`） |

#### リクエスト例

```
GET /api/openrouter/models/openai%2Fgpt-4o
```

#### レスポンス例

```json
{
  "success": true,
  "usd_jpy_rate": 149.85,
  "data": {
    "model_id": "openai/gpt-4o",
    "name": "OpenAI: GPT-4o",
    ...
  }
}
```

---

### モデル検索

キーワードでモデルを検索します。

```
GET /api/openrouter/models/search?q={query}
```

#### クエリパラメータ

| 名前 | 型 | 必須 | 説明 |
|------|-----|------|------|
| `q` | string | ✓ | 検索キーワード |

#### 検索対象フィールド

- `name` - モデル名
- `model_id` - モデルID
- `description` - 英語説明
- `description_ja` - 日本語説明
- `provider` - プロバイダ
- `category_ja` - カテゴリ（日本語）

#### リクエスト例

```
GET /api/openrouter/models/search?q=コード生成
```

#### レスポンス例

```json
{
  "success": true,
  "count": 15,
  "usd_jpy_rate": 149.85,
  "query": "コード生成",
  "data": [...]
}
```

---

### プロバイダでフィルタ

指定プロバイダのモデル一覧を取得します。

```
GET /api/openrouter/models/provider/{provider}
```

#### パラメータ

| 名前 | 型 | 説明 |
|------|-----|------|
| `provider` | string | プロバイダ名（例: `openai`, `anthropic`, `google`） |

#### リクエスト例

```
GET /api/openrouter/models/provider/anthropic
```

---

### カテゴリでフィルタ

指定カテゴリのモデル一覧を取得します。

```
GET /api/openrouter/models/category/{category}
```

#### パラメータ

| 名前 | 型 | 説明 |
|------|-----|------|
| `category` | string | カテゴリ名（日本語、URLエンコード必須） |

#### 利用可能なカテゴリ

| カテゴリ | 説明 |
|----------|------|
| 汎用テキストモデル | 一般的なテキスト生成 |
| 画像生成・マルチモーダル | 画像を扱うモデル |
| コード生成・ソフトウェア開発支援 | コーディング特化 |
| 推論強化モデル | 深い推論能力を持つ |
| 高速・軽量チャット／補助モデル | 低遅延・低コスト |
| 高精度汎用フラッグシップモデル | 高性能フラッグシップ |
| リサーチ・検索エージェント | Web検索・調査特化 |
| 音声・音声認識／合成 | 音声処理 |
| 安全判定・コンテンツフィルタリング | モデレーション |

#### リクエスト例

```
GET /api/openrouter/models/category/%E6%8E%A8%E8%AB%96%E5%BC%B7%E5%8C%96%E3%83%A2%E3%83%87%E3%83%AB
```

---

### プロバイダ一覧取得

登録されているプロバイダとモデル数を取得します。

```
GET /api/openrouter/providers
```

#### レスポンス例

```json
{
  "success": true,
  "count": 45,
  "data": [
    { "provider": "openai", "count": 25 },
    { "provider": "anthropic", "count": 12 },
    { "provider": "google", "count": 18 },
    ...
  ]
}
```

---

### カテゴリ一覧取得

登録されているカテゴリとモデル数を取得します。

```
GET /api/openrouter/categories
```

#### レスポンス例

```json
{
  "success": true,
  "count": 9,
  "data": [
    { "category_ja": "汎用テキストモデル", "count": 120 },
    { "category_ja": "画像生成・マルチモーダル", "count": 85 },
    { "category_ja": "コード生成・ソフトウェア開発支援", "count": 32 },
    ...
  ]
}
```

---

## データフィールド説明

### 基本情報

| フィールド | 型 | 説明 |
|------------|-----|------|
| `model_id` | string | モデルの一意識別子 |
| `canonical_slug` | string | 正規スラッグ |
| `hugging_face_id` | string | HuggingFace ID |
| `name` | string | モデル表示名 |
| `provider` | string | プロバイダ名 |
| `created` | integer | 作成日時（UNIXタイムスタンプ） |
| `description` | string | 英語説明（オリジナル） |
| `context_length` | integer | コンテキスト長（トークン数） |

### アーキテクチャ

| フィールド | 型 | 説明 |
|------------|-----|------|
| `arch_modality` | string | モダリティ（例: `text->text`） |
| `arch_input_modalities` | string | 入力モダリティ |
| `arch_output_modalities` | string | 出力モダリティ |
| `arch_tokenizer` | string | トークナイザー |
| `arch_instruct_type` | string | インストラクトタイプ |

### 価格（USD）

| フィールド | 型 | 説明 |
|------------|-----|------|
| `pricing_prompt` | string | 入力トークン単価（USD） |
| `pricing_completion` | string | 出力トークン単価（USD） |
| `pricing_request` | string | リクエスト単価（USD） |
| `pricing_image` | string | 画像単価（USD） |
| `pricing_web_search` | string | Web検索単価（USD） |
| `pricing_internal_reasoning` | string | 内部推論単価（USD） |

### 価格（日本円・リアルタイム換算）

| フィールド | 型 | 説明 |
|------------|-----|------|
| `pricing_prompt_ja` | string | 入力トークン単価（JPY） |
| `pricing_completion_ja` | string | 出力トークン単価（JPY） |
| `pricing_request_ja` | string | リクエスト単価（JPY） |
| `pricing_image_ja` | string | 画像単価（JPY） |
| `pricing_web_search_ja` | string | Web検索単価（JPY） |
| `pricing_internal_reasoning_ja` | string | 内部推論単価（JPY） |

### 日本語解説（自動生成）

| フィールド | 型 | 説明 |
|------------|-----|------|
| `category_ja` | string | モデルカテゴリ（日本語） |
| `modality_ja` | string | モダリティ（日本語） |
| `input_modalities_ja` | string | 入力モダリティ（日本語） |
| `output_modalities_ja` | string | 出力モダリティ（日本語） |
| `description_ja` | string | AI生成の日本語解説 |
| `supported_parameters_ja` | string | サポートパラメータ（日本語） |

---

## CLIコマンド

### モデル同期

OpenRouterからモデル情報を取得し、DBに同期します。

```bash
# 通常実行（新規モデルのみ翻訳）
php spark openrouter:sync

# 全モデルの説明を再生成
php spark openrouter:sync --force
```

### Cron設定例

```bash
# 毎日AM3:00に同期
0 3 * * * cd /path/to/project && php spark openrouter:sync >> /var/log/openrouter-sync.log 2>&1
```

---

## エラーレスポンス

### 404 Not Found

```json
{
  "status": 404,
  "error": 404,
  "messages": {
    "error": "Model not found"
  }
}
```

### 400 Bad Request

```json
{
  "status": 400,
  "error": 400,
  "messages": {
    "error": "Search query is required"
  }
}
```

---

## 為替レート

日本円価格は、API実行時に以下のエンドポイントからリアルタイムで取得した為替レートを使用しています。

```
https://api.excelapi.org/currency/rate?pair=usd-jpy
```

レスポンスの `usd_jpy_rate` フィールドで使用した為替レートを確認できます。為替レート取得に失敗した場合は `null` となり、`*_ja` フィールドは付加されません。

---

## ライセンス

MIT License
