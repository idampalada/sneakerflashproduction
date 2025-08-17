<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncUserSpendingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:sync-spending {--dry-run : Run without making changes} {--user= : Sync specific user ID}';

    /**
     * The console command description.
     */
    protected $description = 'Sync user spending statistics and tier evaluation from orders table';

    /**
     * Valid statuses that count as "paid/completed" orders for revenue calculation
     */
    private array $paidStatuses = ['paid', 'processing', 'shipped', 'delivered'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $userId = $this->option('user');
        
        $this->info("🚀 Starting User Spending & Tier Sync Command");
        $this->info("📊 New Tier System: Basic → Advance → Ultimate");
        $this->info("💰 Paid Statuses: " . implode(', ', $this->paidStatuses));
        
        if ($isDryRun) {
            $this->warn("🔍 DRY RUN MODE - No changes will be made");
        }
        
        if ($userId) {
            $this->info("👤 Syncing specific user ID: {$userId}");
        }
        
        $this->newLine();
        
        // Build query
        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        }
        
        $users = $query->get();
        $total = $users->count();
        
        if ($total === 0) {
            $this->error("❌ No users found to sync");
            return;
        }
        
        $this->info("📈 Found {$total} users to sync");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();
        
        $processed = 0;
        $updated = 0;
        $errors = 0;
        $tierChanges = [
            'basic' => 0,
            'advance' => 0,
            'ultimate' => 0
        ];
        
        $users->each(function ($user) use (&$processed, &$updated, &$errors, &$tierChanges, $isDryRun, $progressBar) {
            try {
                $processed++;
                
                // Get current values
                $oldSpent = $user->total_spent ?? 0;
                $oldOrders = $user->total_orders ?? 0;
                $oldSpending6Months = $user->spending_6_months ?? 0;
                $oldTier = $user->getCustomerTier();
                
                // Calculate new values
                $newTotalSpent = $user->orders()->whereIn('status', $this->paidStatuses)->sum('total_amount');
                $newTotalOrders = $user->orders()->whereIn('status', $this->paidStatuses)->count();
                $newSpending6Months = $user->getSpending6Months(); // Calculate 6-month spending
                $newTier = $this->calculateTierFromSpending($newSpending6Months);
                
                // Check if update is needed
                $needsUpdate = ($oldSpent != $newTotalSpent) || 
                              ($oldOrders != $newTotalOrders) || 
                              ($oldSpending6Months != $newSpending6Months) ||
                              ($oldTier !== $newTier);
                
                if ($needsUpdate && !$isDryRun) {
                    $user->update([
                        'total_spent' => $newTotalSpent,
                        'total_orders' => $newTotalOrders,
                        'spending_6_months' => $newSpending6Months,
                        'customer_tier' => $newTier,
                        'spending_updated_at' => now(),
                        'last_tier_evaluation' => now()
                    ]);
                    
                    // Initialize tier_period_start if not set
                    if (!$user->tier_period_start) {
                        $user->update(['tier_period_start' => now()->subMonths(6)]);
                    }
                    
                    $updated++;
                    $tierChanges[$newTier]++;
                } elseif ($needsUpdate && $isDryRun) {
                    $updated++; // Count what would be updated
                    $tierChanges[$newTier]++;
                }
                
                $progressBar->advance();
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Error syncing user {$user->id}: " . $e->getMessage());
            }
        });
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Show results
        $this->info("📈 Sync Results:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $processed],
                [$isDryRun ? 'Would Update' : 'Actually Updated', $updated],
                ['Errors', $errors],
                ['Success Rate', round(($processed - $errors) / $processed * 100, 1) . '%']
            ]
        );
        
        // Show tier changes
        $totalTierChanges = array_sum($tierChanges);
        if ($totalTierChanges > 0) {
            $this->newLine();
            $this->info($isDryRun ? "🏆 Would Set Tiers:" : "🏆 Tier Distribution:");
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
        
        // Show current tier distribution
        if (!$isDryRun) {
            $this->showTierDistribution();
        }
        
        if ($isDryRun) {
            $this->newLine();
            $this->info("💡 Run without --dry-run to apply changes");
        }
    }

    /**
     * Calculate tier from 6-month spending amount
     */
    private function calculateTierFromSpending($spending6Months)
    {
        if ($spending6Months >= 10000000) return 'ultimate';    // 10 juta IDR dalam 6 bulan
        if ($spending6Months >= 5000000) return 'advance';     // 5 juta IDR dalam 6 bulan
        return 'basic';
    }

    /**
     * Get tier label from tier code
     */
    private function getTierLabelFromTier($tier)
    {
        return match($tier) {
            'ultimate' => 'Ultimate Member',
            'advance' => 'Advance Member',
            'basic' => 'Basic Member',
            default => 'Basic Member'
        };
    }

    /**
     * Show current tier distribution (PostgreSQL optimized)
     */
    private function showTierDistribution()
    {
        $this->newLine();
        $this->info("📊 Current Tier Distribution (PostgreSQL):");
        
        // PostgreSQL specific query for new tier system
        $distribution = DB::select("
            SELECT 
                customer_tier as tier,
                COUNT(*) as count,
                ROUND(AVG(spending_6_months), 2) as avg_spending_6_months,
                MAX(spending_6_months) as max_spending_6_months
            FROM users 
            WHERE customer_tier IS NOT NULL
            GROUP BY customer_tier
            ORDER BY 
                CASE 
                    WHEN customer_tier = 'ultimate' THEN 3
                    WHEN customer_tier = 'advance' THEN 2
                    WHEN customer_tier = 'basic' THEN 1
                    ELSE 0
                END DESC
        ");
        
        $total = array_sum(array_column($distribution, 'count'));
        
        foreach ($distribution as $tier) {
            $percentage = $total > 0 ? round($tier->count / $total * 100, 1) : 0;
            $emoji = match($tier->tier) {
                'ultimate' => '💎',
                'advance' => '🥇',
                'basic' => '🥉',
                default => '❓'
            };
            
            $this->line("  {$emoji} " . str_pad(ucfirst($tier->tier), 8) . ": {$tier->count} users ({$percentage}%)");
            $this->line("     └─ Avg 6-month: Rp " . number_format($tier->avg_spending_6_months ?? 0, 0, ',', '.'));
            $this->line("     └─ Max 6-month: Rp " . number_format($tier->max_spending_6_months ?? 0, 0, ',', '.'));
        }
        
        $this->newLine();
        $this->info("💰 PostgreSQL Spending Statistics (6-Month Focus):");
        
        // PostgreSQL aggregation functions for new system
        $stats = DB::select("
            SELECT 
                COUNT(*) as total_customers,
                ROUND(AVG(spending_6_months), 2) as avg_spending_6_months,
                SUM(spending_6_months) as total_spending_6_months,
                MAX(spending_6_months) as highest_6_month_spender,
                MIN(CASE WHEN spending_6_months > 0 THEN spending_6_months END) as lowest_6_month_spender,
                COUNT(CASE WHEN spending_6_months >= 5000000 THEN 1 END) as advance_eligible,
                COUNT(CASE WHEN spending_6_months >= 10000000 THEN 1 END) as ultimate_eligible,
                COUNT(CASE WHEN points_balance > 0 THEN 1 END) as users_with_points,
                ROUND(AVG(points_balance), 2) as avg_points_balance
            FROM users
        ")[0];
            
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Customers', number_format($stats->total_customers)],
                ['Advance Eligible (5M+)', number_format($stats->advance_eligible)],
                ['Ultimate Eligible (10M+)', number_format($stats->ultimate_eligible)],
                ['Users with Points', number_format($stats->users_with_points)],
                ['Avg 6-Month Spending', 'Rp ' . number_format($stats->avg_spending_6_months ?? 0, 0, ',', '.')],
                ['Total 6-Month Spending', 'Rp ' . number_format($stats->total_spending_6_months ?? 0, 0, ',', '.')],
                ['Highest 6-Month Spender', 'Rp ' . number_format($stats->highest_6_month_spender ?? 0, 0, ',', '.')],
                ['Lowest 6-Month Spender', 'Rp ' . number_format($stats->lowest_6_month_spender ?? 0, 0, ',', '.')],
                ['Avg Points Balance', number_format($stats->avg_points_balance ?? 0, 0, ',', '.')]
            ]
        );
        
        $this->newLine();
        $this->info("🎯 Tier Thresholds (6-Month Spending):");
        $this->line("  🥉 Basic: Rp 0 - Rp 4,999,999 (1% points)");
        $this->line("  🥇 Advance: Rp 5,000,000 - Rp 9,999,999 (2.5% points)");
        $this->line("  💎 Ultimate: Rp 10,000,000+ (5% points)");
    }
}