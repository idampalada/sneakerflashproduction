<?php

namespace App\Console\Commands;

use App\Services\GineeClient;
use Illuminate\Console\Command;

class FixGineeStockCommand extends Command
{
    protected $signature = 'ginee:fix-stock {sku} {quantity}';
    protected $description = 'Fix/update stock for specific SKU in Ginee';

    public function handle()
    {
        $sku = $this->argument('sku');
        $quantity = (int) $this->argument('quantity');
        
        $this->warn("🔧 FIXING STOCK for SKU: {$sku} to quantity: {$quantity}");
        $this->newLine();

        if (!$this->confirm("Are you sure you want to update {$sku} stock to {$quantity}?")) {
            $this->info('❌ Operation cancelled');
            return 0;
        }

        try {
            $ginee = new GineeClient();
            
            $stockUpdate = [
                ['masterSku' => $sku, 'quantity' => $quantity]
            ];
            
            $this->info("📤 Updating stock...");
            $result = $ginee->updateStock($stockUpdate);
            
            if (($result['code'] ?? null) === 'SUCCESS') {
                $this->info("✅ SUCCESS! Stock updated");
                
                $stockList = $result['data']['stockList'] ?? [];
                foreach ($stockList as $item) {
                    if (($item['masterSku'] ?? null) === $sku) {
                        $this->table(['Field', 'Value'], [
                            ['SKU', $item['masterSku'] ?? 'N/A'],
                            ['Product Name', $item['masterProductName'] ?? 'N/A'],
                            ['NEW Warehouse Stock', $item['warehouseStock'] ?? 'N/A'],
                            ['NEW Available Stock', $item['availableStock'] ?? 'N/A'],
                            ['Locked Stock', $item['lockedStock'] ?? 'N/A'],
                            ['Updated At', $item['updateDatetime'] ?? 'N/A'],
                        ]);
                        break;
                    }
                }
            } else {
                $this->error("❌ Failed to update stock");
                $this->line("Error: " . ($result['message'] ?? 'Unknown error'));
                $this->line("Code: " . ($result['code'] ?? 'Unknown'));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}