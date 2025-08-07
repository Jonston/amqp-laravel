<?php

namespace Jonston\AmqpLaravel\Console\Commands;

use Jonston\AmqpLaravel\AMQPService;
use Illuminate\Console\Command;

class AMQPSetupCommand extends Command
{
    protected $signature = 'amqp:setup {--force : Force setup even in production}';
    protected $description = 'Setup AMQP exchanges and queues';

    public function handle(AMQPService $amqpService): int
    {
        try {
            if (app()->environment('production') && !$this->option('force')) {
                $this->warn('Skipping queue setup in production. Use --force to override.');
                return 0;
            }

            $this->info('Setting up AMQP infrastructure...');

            $exchanges = config('amqp.exchanges', []);
            $queues = config('amqp.queues', []);

            $this->info("Creating " . count($exchanges) . " exchanges...");
            $this->info("Creating " . count($queues) . " queues...");

            $amqpService->setupFromConfig();

            $successMessage = 'AMQP setup completed successfully';
            $this->info("✅ {$successMessage}");

            logger()->info('AMQP setup completed', [
                'exchanges_count' => count($exchanges),
                'queues_count' => count($queues)
            ]);

            return 0;

        } catch (\Exception $e) {
            $criticalError = "Critical AMQP setup failure: {$e->getMessage()}";
            $this->error("❌ {$criticalError}");

            logger()->critical('AMQP setup critical failure', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if (app()->environment(['local', 'testing'])) {
                $this->line('<fg=red>Stack trace:</>');
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }
}

