<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\ApiKey;

class GenerateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-api-key {name : The name of the API key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new API key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Validasi apakah name diisi
        if (!$this->argument('name')) {
            $this->error('Name is required!');
            return 1;
        }

        $name = $this->argument('name');
        $key = Str::random(32);

        try {
            $apiKey = ApiKey::create([
                'name' => $name,
                'key' => $key,
                'is_active' => true
            ]);

            $this->info("API Key generated successfully:");
            $this->line("Name: {$apiKey->name}");
            $this->line("Key: {$apiKey->key}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to generate API Key: ' . $e->getMessage());
            return 1;
        }
    }
}