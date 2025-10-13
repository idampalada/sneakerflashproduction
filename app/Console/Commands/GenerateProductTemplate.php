<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\ProductTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class GenerateProductTemplate extends Command
{
    protected $signature = 'products:generate-template';
    protected $description = 'Generate Excel template for product import';

    public function handle()
    {
        try {
            // Create templates directory if not exists
            if (!file_exists(public_path('templates'))) {
                mkdir(public_path('templates'), 0755, true);
                $this->info('Created templates directory');
            }

            // Generate and save template
            $templatePath = public_path('templates/product-import-template.xlsx');
            
            Excel::store(new ProductTemplateExport(), 'templates/product-import-template.xlsx', 'public');
            
            $this->info("Product import template generated successfully!");
            $this->info("Location: {$templatePath}");
            $this->info("You can now download it from the admin panel.");
            
            return 0;
        } catch (Exception $e) {
            $this->error("Failed to generate template: " . $e->getMessage());
            return 1;
        }
    }
}