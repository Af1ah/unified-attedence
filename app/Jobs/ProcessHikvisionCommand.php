<?php

namespace App\Jobs;

use App\Models\DeviceCommand;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shaykhnazar\HikvisionIsapi\Facades\Hikvision;
use Shaykhnazar\HikvisionIsapi\Services\PersonService;
use Shaykhnazar\HikvisionIsapi\Services\DeviceService;

class ProcessHikvisionCommand implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public DeviceCommand $command
    ) {}

    public function handle(): void
    {
        $command = $this->command;
        $device = $command->device;
        
        try {
            Hikvision::registerDevice('device_' . $device->id, [
                'ip' => $device->ip_address,
                'port' => $device->port ?? 80,
                'username' => $device->username,
                'password' => $device->password,
                'protocol' => $device->protocol ?? 'http',
                'timeout' => 30,
                'verify_ssl' => false,
            ]);
            
            $client = Hikvision::device('device_' . $device->id);
            $content = $command->command_content;
            
            // Map ZKTeco style commands to Hikvision API actions
            if (str_starts_with($content, 'DATA QUERY USERINFO')) {
                
                $personService = new PersonService($client);
                
                $page = 0;
                $maxResults = 30;
                $totalFetched = 0;
                
                do {
                    $persons = $personService->search($page, $maxResults);
                    
                    foreach ($persons as $person) {
                        User::updateOrCreate(
                            ['pin' => $person->employeeNo],
                            [
                                'name' => $person->name ?? ('Employee ' . $person->employeeNo),
                                'role' => 'employee',
                                // Can add privilege mapping later if needed
                            ]
                        );
                        $totalFetched++;
                    }
                    
                    $page++;
                } while (count($persons) === $maxResults);
                
                $command->update(['status' => 'acknowledged', 'response' => "Fetched {$totalFetched} users successfully"]);
                
            } elseif (str_starts_with($content, 'REBOOT')) {
                
                $client->put('/ISAPI/System/reboot');
                $command->update(['status' => 'acknowledged', 'response' => 'Reboot command sent']);
                
            } else {
                // Command not fully mapped to ISAPI yet
                $command->update(['status' => 'acknowledged', 'response' => 'Simulated success (Command not strictly mapped for Hikvision)']);
            }
            
        } catch (\Exception $e) {
            $command->update(['status' => 'failed', 'response' => 'Hikvision API Error: ' . $e->getMessage()]);
        }
    }
}
