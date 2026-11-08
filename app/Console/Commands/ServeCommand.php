<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use function Illuminate\Support\php_binary;

class ServeCommand extends BaseServeCommand
{
    /**
     * Get the full server command.
     *
     * @return array
     */
    protected function serverCommand()
    {
        $server = file_exists(base_path('server.php'))
            ? base_path('server.php')
            : base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');

        return [
            php_binary(),
            '-d', 'upload_max_filesize=256M',
            '-d', 'post_max_size=260M',
            '-d', 'memory_limit=512M',
            '-d', 'max_execution_time=300',
            '-d', 'max_input_time=300',
            '-S',
            $this->host().':'.$this->port(),
            $server,
        ];
    }
}
