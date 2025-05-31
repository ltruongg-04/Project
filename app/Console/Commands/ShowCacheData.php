<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Brand;

class ShowCacheData extends Command
{
    protected $signature = 'cache:show {key}';

    protected $description = 'Hiển thị dữ liệu cache theo key';

    public function handle()
    {
        $key = $this->argument('key');

        if (!Cache::has($key)) {
            $this->info(' Cache miss → Lấy dữ liệu từ DB');
        }

        $data = Cache::remember($key, 60, function () use ($key) {
            if ($key === 'shop_brands') {
                return Brand::orderBy('name', 'ASC')->get();
            }
            return null;
        });

        $this->info(' Dữ liệu:');
        $this->line(json_encode($data, JSON_PRETTY_PRINT));

    }
    
}
