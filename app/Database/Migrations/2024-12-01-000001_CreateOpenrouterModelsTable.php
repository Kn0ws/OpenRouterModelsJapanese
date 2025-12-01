<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpenrouterModelsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // === 基本情報（オリジナル） ===
            'model_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'canonical_slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'hugging_face_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'created' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'context_length' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            // === アーキテクチャ（オリジナル） ===
            'arch_modality' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'arch_input_modalities' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'arch_output_modalities' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'arch_tokenizer' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'arch_instruct_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],

            // === 価格（オリジナル） ===
            'pricing_prompt' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'pricing_completion' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'pricing_request' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'pricing_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'pricing_web_search' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'pricing_internal_reasoning' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],

            // === トッププロバイダ（オリジナル） ===
            'top_provider_context_length' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'top_provider_max_completion_tokens' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'top_provider_is_moderated' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],

            // === サポートパラメータ（オリジナル・JSON保存） ===
            'supported_parameters' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            // === デフォルトパラメータ（オリジナル・JSON保存） ===
            'default_parameters' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            // === 日本語解説・分類（追加） ===
            'category_ja' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'modality_ja' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'input_modalities_ja' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'output_modalities_ja' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'description_ja' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'supported_parameters_ja' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            // === タイムスタンプ ===
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('model_id');
        $this->forge->addKey('provider');
        $this->forge->addKey('category_ja');
        $this->forge->addKey('arch_modality');
        $this->forge->createTable('openrouter_models');
    }

    public function down()
    {
        $this->forge->dropTable('openrouter_models');
    }
}
