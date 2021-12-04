<?php

namespace Shamaseen\Repository\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepositoryFilesGenerated
{
    use Dispatchable, SerializesModels;

    public string $path;
    public string $modelName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $path, string $modelName)
    {
        $this->path = $path;
        $this->modelName = $modelName;
    }
}
