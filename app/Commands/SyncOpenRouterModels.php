<?php

namespace App\Commands;

use App\Models\OpenRouterModelModel;
use App\Services\OpenRouterService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncOpenRouterModels extends BaseCommand
{
    /**
     * The Command's Group
     */
    protected $group = 'OpenRouter';

    /**
     * The Command's Name
     */
    protected $name = 'openrouter:sync';

    /**
     * The Command's Description
     */
    protected $description = 'OpenRouterからAIモデル一覧を取得し、翻訳してDBに同期する';

    /**
     * The Command's Usage
     */
    protected $usage = 'openrouter:sync [options]';

    /**
     * The Command's Arguments
     */
    protected $arguments = [];

    /**
     * The Command's Options
     */
    protected $options = [
        '--force' => 'すべてのモデルの説明を再生成する',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $forceRegenerate = CLI::getOption('force') !== null;

        CLI::write('OpenRouterモデル同期を開始します...', 'green');

        $service = new OpenRouterService();
        $model = new OpenRouterModelModel();

        // 1. OpenRouterからモデル一覧を取得
        CLI::write('OpenRouterからモデル一覧を取得中...', 'yellow');
        
        try {
            $apiModels = $service->fetchModels();
        } catch (\Exception $e) {
            CLI::error('モデル取得に失敗しました: ' . $e->getMessage());
            return EXIT_ERROR;
        }

        CLI::write('取得モデル数: ' . count($apiModels), 'green');

        // APIから取得したモデルIDリスト
        $apiModelIds = array_column($apiModels, 'id');

        // 2. DB内の既存モデルIDを取得
        $existingModelIds = $model->getAllModelIds();

        CLI::write('DB既存モデル数: ' . count($existingModelIds), 'cyan');

        // 3. 削除対象を特定（DBにあるがAPIにない）
        $deletedModelIds = array_diff($existingModelIds, $apiModelIds);
        
        if (!empty($deletedModelIds)) {
            CLI::write('削除対象モデル数: ' . count($deletedModelIds), 'red');
            
            foreach ($deletedModelIds as $deletedId) {
                $model->deleteByModelId($deletedId);
                CLI::write("  [削除] {$deletedId}", 'red');
            }
        }

        // 4. モデルを処理（新規追加または更新）
        $newCount = 0;
        $skipCount = 0;
        $updateCount = 0;

        foreach ($apiModels as $apiModel) {
            $modelId = $apiModel['id'] ?? '';
            
            if (empty($modelId)) {
                continue;
            }

            $existing = $model->findByModelId($modelId);

            if ($existing && !$forceRegenerate) {
                // 既存モデルはスキップ（説明文を再利用）
                $skipCount++;
                CLI::write("  [スキップ] {$modelId}", 'light_gray');
                continue;
            }

            if ($existing && $forceRegenerate) {
                // 強制再生成の場合は更新
                CLI::write("  [再生成中] {$modelId}", 'yellow');
                
                try {
                    $formattedData = $service->formatModelData($apiModel);
                    $model->update($existing['id'], $formattedData);
                    $updateCount++;
                    CLI::write("  [更新完了] {$modelId}", 'green');
                } catch (\Exception $e) {
                    CLI::error("  [エラー] {$modelId}: " . $e->getMessage());
                }
                continue;
            }

            // 新規モデル
            CLI::write("  [新規翻訳中] {$modelId}", 'yellow');
            
            try {
                $formattedData = $service->formatModelData($apiModel);
                $model->insert($formattedData);
                $newCount++;
                CLI::write("  [追加完了] {$modelId}", 'green');
            } catch (\Exception $e) {
                CLI::error("  [エラー] {$modelId}: " . $e->getMessage());
            }
            
            // API制限を考慮して少し待機
            usleep(500000); // 0.5秒
        }

        CLI::newLine();
        CLI::write('同期完了！', 'green');
        CLI::write("  - 新規追加: {$newCount} 件", 'green');
        CLI::write("  - スキップ: {$skipCount} 件", 'cyan');
        CLI::write("  - 更新: {$updateCount} 件", 'yellow');
        CLI::write("  - 削除: " . count($deletedModelIds) . " 件", 'red');

        return EXIT_SUCCESS;
    }
}
