<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EvaluateCustomerTiersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tiers:evaluate {--dry-run : Run without making changes} {--force : Force evaluation for all users}';

    /**
     * The console command description.
     */
    protected $description = 'Evaluate customer tiers based on 6-month spending and downgrade if necessary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $forceAll = $this->option('force');
        
        $this->info("🔄 Starting Customer Tier Evaluation");
        $this->info("📅 Evaluation Period: 6 months rolling window");
        
        if ($isDryRun) {
            $this->warn("🔍 DRY RUN MODE - No changes will be made");
        }
        
        if ($forceAll) {
            $this->info("⚡ FORCE MODE - Evaluating all users");
        }
        
        $this->newLine();
        
        // Get users that need tier evaluation
        $query = User::query();
        
        if (!$forceAll) {
            // Only users who need evaluation (haven't been evaluated in 30 days or never)
            $query->where(function ($q) {
                $q->whereNull('last_tier_evaluation')
                  ->orWhere('last_tier_evaluation', '<', now()->subDays(30));
            });
        }
        
        $users = $query->get();
        $total = $users->count();
        
        if ($total === 0) {
            $this->info("✅ No users need tier evaluation at this time");
            return;
        }
        
        $this->info("👥 Found {$total} users for tier evaluation");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();
        
        $processed = 0;
        $tierChanged = 0;
        $tierUpgraded = 0;
        $tierDowngraded = 0;
        $errors = 0;
        
        $tierChanges = [
            'basic' => 0,
            'advance' => 0,
            'ultimate' => 0
        ];
        
        $changeDetails = [
            'upgrades' => [],
            'downgrades' => []
        ];
        
        $users->each(function ($user) use (
            &$processed, &$tierChanged, &$tierUpgraded, &$tierDowngraded, &$errors, 
            &$tierChanges, &$changeDetails, $isDryRun, $progressBar
        ) {
            try {
                $processed++;
                
                $oldTier = $user->getCustomerTier();
                $oldSpending6Months = $user->spending_6_months ?? 0;
                
                // Calculate current 6-month spending
                $newSpending6Months = $user->getSpending6Months();
                $newTier = $this->calculateTierFromSpending($newSpending6Months);
                
                // Check if tier changed
                if ($oldTier !== $newTier) {
                    $tierChanged++;
                    $tierChanges[$newTier]++;
                    
                    if ($this->getTierLevel($newTier) > $this->getTierLevel($oldTier)) {
                        $tierUpgraded++;
                        $changeDetails['upgrades'][] = [
                            'user' => $user->name,
                            'email' => $user->email,
                            'old_tier' => $oldTier,
                            'new_tier' => $newTier,
                            'spending' => $newSpending6Months
                        ];
                    } else {
                        $tierDowngraded++;
                        $changeDetails['downgrades'][] = [
                            'user' => $user->name,
                            'email' => $user->email,
                            'old_tier' => $oldTier,
                            'new_tier' => $newTier,
                            'spending' => $newSpending6Months
                        ];
                    }
                }
                
                // Update user if not dry run
                if (!$isDryRun) {
                    $user->update([
                        'spending_6_months' => $newSpending6Months,
                        'customer_tier' => $newTier,
                        'last_tier_evaluation' => now()
                    ]);
                    
                    // Initialize tier_period_start if not set
                    if (!$user->tier_period_start) {
                        $user->update(['tier_period_start' => now()->subMonths(6)]);
                    }
                }
                
                $progressBar->advance();
                
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to evaluate tier for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Show results
        $this->info("📊 Tier Evaluation Results:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Users Processed', $processed],
                ['Tiers Changed', $tierChanged],
                ['Upgrades', $tierUpgraded],
                ['Downgrades', $tierDowngraded],
                ['Errors', $errors],
                ['Success Rate', round(($processed - $errors) / max($processed, 1) * 100, 1) . '%']
            ]
        );
        
        // Show tier distribution after changes
        if ($tierChanged > 0) {
            $this->newLine();
            $this->info($isDryRun ? "🏆 Would Result In:" : "🏆 New Tier Distribution:");
            foreach ($tierChanges as $tier => $count) {
                if ($count > 0) {
                    $emoji = match($tier) {
                        'ultimate' => '💎',
                        'advance' => '🥇',
                        'basic' => '🥉'
                    };
                    $this->line("  {$emoji} " . ucfirst($tier) . ": {$count} users");
                }
            }
        }
        
        // Show upgrade details
        if ($tierUpgraded > 0 && count($changeDetails['upgrades']) <= 10) {
            $this->newLine();
            $this->info("🎉 Tier Upgrades:");
            foreach ($changeDetails['upgrades'] as $upgrade) {
                $this->line("  📈 {$upgrade['user']} ({$upgrade['email']})");
                $this->line("     {$upgrade['old_tier']} → {$upgrade['new_tier']} (Rp " . number_format($upgrade['spending'], 0, ',', '.') . ")");
            }
        }
        
        // Show downgrade details
        if ($tierDowngraded > 0 && count($changeDetails['downgrades']) <= 10) {
            $this->newLine();
            $this->info("📉 Tier Downgrades:");
            foreach ($changeDetails['downgrades'] as $downgrade) {
                $this->line("  📉 {$downgrade['user']} ({$downgrade['email']})");
                $this->line("     {$downgrade['old_tier']} → {$downgrade['new_tier']} (Rp " . number_format($downgrade['spending'], 0, ',', '.') . ")");
            }
        }
        
        if ($isDryRun) {
            $this->newLine();
            $this->info("💡 Run without --dry-run to apply tier changes");
        } else {
            $this->newLine();
            $this->info("✅ Tier evaluation completed successfully");
            
            // Log summary
            Log::info('Customer tier evaluation completed', [
                'processed' => $processed,
                'tier_changed' => $tierChanged,
                'upgrades' => $tierUpgraded,
                'downgrades' => $tierDowngraded,
                'errors' => $errors
            ]);
        }
    }

    /**
     * Calculate tier from 6-month spending amount
     */
    private function calculateTierFromSpending($spending6Months)
    {
        if ($spending6Months >= 10000000) return 'ultimate';    // 10 juta IDR
        if ($spending6Months >= 5000000) return 'advance';     // 5 juta IDR
        return 'basic';
    }

    /**
     * Get tier level for comparison (higher number = higher tier)
     */
    private function getTierLevel($tier)
    {
        return match($tier) {
            'ultimate' => 3,
            'advance' => 2,
            'basic' => 1,
            default => 0
        };
    }
}