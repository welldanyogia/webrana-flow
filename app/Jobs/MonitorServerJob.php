<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Server;
use App\Models\ServerMetric;
use phpseclib3\Net\SSH2;

class MonitorServerJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Server $server
    ) {}

    public function handle(): void
    {
        $ssh = new SSH2($this->server->ip_address, $this->server->ssh_port ?? 22);
        
        try {
            // 1. Authenticate
            $user = $this->server->ssh_user ?? 'root';
            $privateKey = $this->server->ssh_private_key;
            
            if ($privateKey) {
                $key = \phpseclib3\Crypt\PublicKeyLoader::load($privateKey);
                if (!$ssh->login($user, $key)) {
                    throw new \Exception("SSH Login Failed with Private Key");
                }
            } else {
                // Fallback to password if no key provided
                if (!$ssh->login($user, $this->server->password)) {
                    throw new \Exception("SSH Login Failed with Password");
                }
            }

            // 2. Execute Commands
            // CPU Usage (100 - idle)
            $cpuCmd = "top -bn1 | grep 'Cpu(s)' | sed 's/.*, *\([0-9.]*\)%* id.*/\\1/' | awk '{print 100 - $1}'";
            $cpuOutput = $ssh->exec($cpuCmd);
            $cpuUsage = is_numeric(trim($cpuOutput)) ? (float) trim($cpuOutput) : null;

            // RAM Usage (used / total * 100)
            $ramCmd = "free -m | awk 'NR==2{printf \"%.2f\", $3*100/$2 }'";
            $ramOutput = $ssh->exec($ramCmd);
            $ramUsage = is_numeric(trim($ramOutput)) ? (float) trim($ramOutput) : null;

            // Disk Usage (Root partition)
            $diskCmd = "df -h / | awk 'NR==2 {print $5}' | sed 's/%//'";
            $diskOutput = $ssh->exec($diskCmd);
            $diskUsage = is_numeric(trim($diskOutput)) ? (float) trim($diskOutput) : null;

            // 3. Save Metrics (Online)
            ServerMetric::create([
                'server_id' => $this->server->id,
                'is_online' => true,
                'cpu_usage' => $cpuUsage,
                'ram_usage' => $ramUsage,
                'disk_usage' => $diskUsage,
            ]);

            // 4. Update Server Status
            $this->server->update(['status' => 'running']);

        } catch (\Exception $e) {
            // 5. Handle Offline/Error
            ServerMetric::create([
                'server_id' => $this->server->id,
                'is_online' => false,
                'cpu_usage' => null,
                'ram_usage' => null,
                'disk_usage' => null,
            ]);

            $this->server->update(['status' => 'unreachable']);
            
            // Log error for debugging
            \Illuminate\Support\Facades\Log::error("MonitorServerJob Error [Server {$this->server->id}]: " . $e->getMessage());
        }
    }
}
